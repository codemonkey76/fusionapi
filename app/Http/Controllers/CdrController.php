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
        } else {
            $date = Carbon::now()->subDay();
        }

        return Cdr::whereDate('end_stamp', '=', $date)->get();
    }
}
