<?php

namespace App\Http\Controllers;

use App\Models\Cdr;
use App\Models\Domain;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CdrController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'domain_name' => 'required|exists:pgsql.v_domains,domain_name'
        ]);
        return Cdr::where('domain_name', $validated['domain_name'])->get();
    }

    public function range(Request $request)
    {
        $validated = $request->validate([
            'domain_name' => 'required|exists:pgsql.v_domains,domain_name',
            'from' => 'required|date',
            'to' => 'required|date'
        ]);

        return Cdr::where('domain_name', $validated['domain_name'])->whereBetween('end_stamp', [$validated['from'], $validated['to']])->get();

    }
}
