<?php

namespace App\Http\Controllers;

use App\Models\ActiveCall;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ActiveCallController extends Controller
{
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

        $date = data_get($validated, Carbon::createFromtimestamp(strtotime('date')), now());
          return response()
            ->json($this->getCallsByDate($date));
    }

    public function getCallsByDate(Carbon $date, int $interval = 15)
    {
        $from = clone($date)->startOfDay();
        $to = clone($date)->endOfDay();

        return ActiveCall::query()
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
