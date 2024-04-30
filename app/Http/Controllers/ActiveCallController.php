<?php

namespace App\Http\Controllers;

use App\Models\ActiveCall;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ActiveCallController extends Controller
{
    public function getConcurrentCallsByDomain(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date|date_format:Y-m-d',
            'resolution' => 'sometimes|nullable|in:1,5,15,30,60'
        ]);

        $start = Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay();
        $end = (clone $start)->endOfDay();
        $res = $validated['resolution'] ?? 5;

        $startStamp = "'$start'::TIMESTAMP";
        $endStamp = "'$end'::TIMESTAMP";
        $resolution = "'$res minutes'::INTERVAL";
        Log::info("Params to query", [
            'start' => $startStamp,
            'end' => $endStamp,
            'resolution' => $resolution
        ]);

        $sql =
            "
WITH ts_data AS (
SELECT
    generate_series AS start_interval,
    generate_series + $resolution - '1 second'::INTERVAL AS end_interval
FROM
    generate_series($startStamp, $endStamp - $resolution, $resolution)
),

call_events AS (
SELECT ts1.xml_cdr_uuid,
    ts1.domain_name,
    ts1.start_stamp AS start_stamp_src,
    ts1.end_stamp AS end_stamp_src,
    ts2.xml_cdr_uuid AS xml_cdr_uuid_dest,
    ts2.start_stamp AS start_stamp_dest,
    ts2.end_stamp AS end_stamp_dest
FROM v_xml_cdr ts1
LEFT join
    v_xml_cdr ts2 on
        ts1.domain_name = ts2.domain_name and
        ts1.xml_cdr_uuid <> ts2.xml_cdr_uuid and
        ts2.start_stamp <= ts1.end_stamp and
        ts2.end_stamp >= ts1.start_stamp
where
    ts1.start_stamp >= $startStamp and
    ts1.end_stamp <= $endStamp and
    ts2.xml_cdr_uuid IS NOT NULL
),

concurrent_call_events AS (
select
    count(*) + 1 AS concurrent_calls,
    call_events.xml_cdr_uuid,
    call_events.domain_name,
    call_events.start_stamp_src,
    call_events.end_stamp_src
from
    call_events
GROUP by
    call_events.xml_cdr_uuid,
    call_events.domain_name,
    call_events.start_stamp_src,
    call_events.end_stamp_src
),

domain_names AS (
SELECT distinct
    v_xml_cdr.domain_name
from
    v_xml_cdr
where
    v_xml_cdr.start_stamp < $endStamp and
    v_xml_cdr.end_stamp > $startStamp
),

template_data AS (
select
    d.domain_name,
    ts.start_interval,
    ts.end_interval,
    0 AS active_calls
from
    ts_data ts
CROSS join
    domain_names d
),

results AS (
select
    td.domain_name,
    td.start_interval,
    td.end_interval,
    COALESCE(max(c.concurrent_calls), 0::bigint) AS active_calls
from
    template_data td
LEFT join
    concurrent_call_events c on
        td.domain_name = c.domain_name and
        c.start_stamp_src >= td.start_interval and
        c.start_stamp_src <= td.end_interval
GROUP by
    td.domain_name,
    td.start_interval,
    td.end_interval
)

select
    domain_name,
    start_interval,
    end_interval,
    active_calls
from
    results;
";
        Log::info("Query to execute", [
            'sql' => $sql
        ]);
        $data = DB::connection("pgsql")->select(DB::raw($sql));
        return response()->json($data);
    }

    public function getCallsByDomain(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date|date_format:Y-m-d',
            'resolution' => 'sometimes|nullable|in:1,5,15,30,60'
        ]);
        $start = Carbon::createFromFormat('Y-m-d', $validated['date']);
        $start->setTime(5, 0, 0);
        $end = (clone $start)->addHours(12);
        $res = $validated['resolution'] ?? 5;
        $resolution = "${res} minutes";

        $sql =
            "
WITH Domains AS (
    -- Get list of domains that have active calls within the date range
    SELECT DISTINCT
        domain_name
    FROM
        v_xml_cdr
    WHERE
        start_stamp < '" .
            $end .
            "' AND
        end_stamp > '" .
            $start .
            "'
),

Timestamps AS (
    -- Get timeseries for active calls
    SELECT
        generate_series AS start_interval,
        generate_series + INTERVAL '" .
            $resolution .
            "' - INTERVAL '1 second' AS end_interval
    FROM
        generate_series('" .
            $start .
            "'::timestamp, '" .
            $end .
            "'::timestamp - '" .
            $resolution .
            "'::interval, '" .
            $resolution .
            "'::interval)
),

DomainIntervals AS (
    SELECT
        t.start_interval,
        t.end_interval,
        d.domain_name,
        COUNT(*) FILTER (WHERE v.direction = 'inbound' AND v.end_stamp > t.start_interval AND v.start_stamp < t.end_interval) AS inbound,
        COUNT(*) FILTER (WHERE v.direction = 'outbound' AND v.end_stamp > t.start_interval AND v.start_stamp < t.end_interval) AS outbound,
        COUNT(*) FILTER (WHERE v.direction = 'internal' AND v.end_stamp > t.start_interval AND v.start_stamp < t.end_interval) AS internal,
        COUNT(*) FILTER (WHERE v.end_stamp > t.start_interval AND v.start_stamp < t.end_interval) AS total_active
    FROM
        Timestamps t
    CROSS JOIN
        Domains d
    LEFT JOIN
        v_xml_cdr v ON v.domain_name = d.domain_name AND v.end_stamp > t.start_interval AND v.start_stamp < t.end_interval
    GROUP BY
        t.start_interval, t.end_interval, d.domain_name
) SELECT
    start_interval,
    end_interval,
    domain_name as domain,
    COALESCE(inbound, 0) AS inbound,
    COALESCE(outbound, 0) AS outbound,
    COALESCE(internal, 0) AS internal,
    COALESCE(total_active, 0) AS total
FROM
    DomainIntervals
ORDER BY
    start_interval, domain;
";
        $data = DB::connection("pgsql")->select(DB::raw($sql));

        return response()->json($data);
    }
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Failed validation, date must be a valid date'
            ]);
        }

        $validated = $validator->validated();

        $date = Carbon::createFromTimestamp(strtotime(data_get($validated, 'date', now())));
        return response()
            ->json($this->getCallsByDate($date));
    }

    public function getCallsByDate(Carbon $date, int $interval = 15)
    {
        $from = clone ($date)->startOfDay();
        $to = clone ($date)->endOfDay();

        return ActiveCall::query()
            ->whereNull('domain')
            ->orderBy('created_at')
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy(function ($call) use ($interval) {
                $timestampInMinutes = $call->created_at->startOfMinute()->timestamp / 60;
                $timestamp = ($timestampInMinutes - ($timestampInMinutes % $interval)) * 60;
                return Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i');
            })
            ->map(fn ($value, $key) => (object)[
                'timestamp' => $key,
                'inbound' => (int)$value->max('inbound') ?? 0,
                'outbound' => (int)$value->max('outbound') ?? 0,
                'samples' => $value->count()
            ])
            ->values();
    }
}
