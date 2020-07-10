<?php

namespace App\Http\Controllers;

use App\Cdr;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CdrController extends Controller
{
    public function index()
    {
        if (request()->has('date')) {
            $date = Carbon::parse(request('date'));
            return Cdr::whereDate('start_stamp', '=', $date)->pluck('direction');
        }

        return Cdr::where('direction', 'outbound')->get();
    }
}
