<?php

namespace App\Http\Controllers;

use App\Cdr;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class CdrController extends Controller
{
    public function index()
    {
        $data = request()->validate([
            'date' => 'sometimes|date',
            'hours' => 'sometimes|numeric'
        ]);

        if (request()->has('date')) {
            $date = CarbonImmutable::parse(request('date'));
        } else {
            $date = CarbonImmutable::now();
        }

        if (request()->has('hours')) {
            $hours = intval(request('hours'));
        } else {
            $hours = 4;
        }

        return Cdr::whereBetween('end_stamp', [$date->subHours($hours), $date])->get();
    }
}
