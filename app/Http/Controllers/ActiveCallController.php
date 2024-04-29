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

        $startStamp = "'${start}'::TIMESTAMP";
        $endStamp = "'${end}'::TIMESTAMP";
        $resolution = "'${res} minutes'::INTERVAL";
        Log::info("Params to query", [
            'start' => $startStamp,
            'end' => $endStamp,
            'resolution' => $resolution
        ]);

        $sql =
            "
WITH
Domains AS (
    SELECT DISTINCT
        domain_name
    FROM
        v_xml_cdr
    WHERE
        start_stamp < $endStamp AND
        end_stamp > $startStamp
),

Timestamps AS (
    SELECT
        generate_series AS start_interval,
        generate_series + $resolution - '1 second'::INTERVAL AS end_interval
    FROM
        generate_series($startStamp, $endStamp - $resolution, $resolution)
),

CallEvents AS (
    SELECT
        v.domain_name,
        v.direction,
        v.start_stamp AS event_time,
        1 AS event_type -- Start of call
    FROM
        v_xml_cdr v
    UNION ALL
    SELECT
        v.domain_name,
        v.direction,
        v.end_stamp AS event_time,
        -1 AS event_type -- End of call
    FROM
        v_xml_cdr v
),

RunningTotals AS (
    SELECT
        t.start_interval,
        t.end_interval,
        d.domain_name,
        e.direction,
        COALESCE(SUM(e.event_type), 0) AS running_total
    FROM
        Timestamps t
    CROSS JOIN
        Domains d
    LEFT JOIN
        CallEvents e ON e.domain_name = d.domain_name AND e.event_time BETWEEN t.start_interval AND t.end_interval
    GROUP BY
        t.start_interval, t.end_interval, d.domain_name, e.direction
),

MaxConcurrentCalls AS (
    SELECT
        start_interval,
        end_interval,
        domain_name,
        direction,
        MAX(running_total) AS max_concurrent
    FROM
        RunningTotals
    GROUP BY
        start_interval, end_interval, domain_name, direction
) SELECT
    start_interval,
    end_interval,
    domain_name AS domain,
    COALESCE(MAX(max_concurrent) FILTER (WHERE direction = 'inbound'), 0) AS inbound,
    COALESCE(MAX(max_concurrent) FILTER (WHERE direction = 'outbound'), 0) AS outbound,
    COALESCE(MAX(max_concurrent) FILTER (WHERE direction = 'internal'), 0) AS internal,
    COALESCE(SUM(max_concurrent), 0) AS total
FROM
    MaxConcurrentCalls
GROUP BY
    start_interval, end_interval, domain_name
ORDER BY
    start_interval, domain_name;
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
        $from = clone($date)->startOfDay();
        $to = clone($date)->endOfDay();

        return ActiveCall::query()
            ->whereNull('domain')
            ->orderBy('created_at')
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->groupBy(function ($call) use ($interval) {
                $timestampInMinutes = $call->created_at->startOfMinute()->timestamp / 60;
                $timestamp = ($timestampInMinutes - ($timestampInMinutes % $interval))*60;
                return Carbon::createFromTimestamp($timestamp)->format('Y-m-d H:i');
            })
            ->map(fn($value, $key) => (object)[
                'timestamp' => $key,
                'inbound' => (int)$value->max('inbound') ?? 0,
                'outbound' => (int)$value->max('outbound') ?? 0,
                'samples' => $value->count()
            ])
            ->values();
    }
}
