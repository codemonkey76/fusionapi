<?php

namespace App\Http\Controllers;

use App\Models\ActiveCall;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ActiveCallController extends Controller
{
    public function getCallsByDomain(request $request): JsonResponse
    {
        $start = now()->subDay();
        $start->setTime(0, 0, 0);
        $end = (clone $start)->addHours(24);

        $sql = "
WITH minute_intervals AS (
    SELECT generate_series AS interval
    FROM generate_series('" . $start->toDateTimeString() . "', '" . $end->toDateTimeString() . "', INTERVAL '5 minute')
), active_domains AS (
    SELECT DISTINCT domain_name
    FROM v_xml_cdr
    WHERE start_stamp <= '" . $end->toDateTimeString() . "' AND end_stamp >= '" . $start->toDateTimeString() . "'
)
SELECT
    mi.interval,
    ad.domain_name,
    COUNT(*) FILTER (WHERE c.direction = 'inbound' AND c.start_stamp <= mi.interval AND c.end_stamp >= mi.interval) AS inbound,
    COUNT(*) FILTER (WHERE c.direction = 'outbound' AND c.start_stamp <= mi.interval AND c.end_stamp >= mi.interval) AS outbound,
    COUNT(*) FILTER (WHERE c.start_stamp <= mi.interval AND c.end_stamp >= mi.interval) AS total
FROM 
    minute_intervals mi
CROSS JOIN 
    active_domains ad
LEFT JOIN 
    v_xml_cdr c ON c.domain_name = ad.domain_name AND c.start_stamp <= mi.interval AND c.end_stamp >= mi.interval
GROUP BY 
    mi.interval, ad.domain_name
ORDER BY 
    mi.interval ASC, ad.domain_name
";

        return response()->json(DB::connection("pgsql")->select(DB::raw($sql)));

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
