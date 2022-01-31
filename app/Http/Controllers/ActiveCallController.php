<?php

namespace App\Http\Controllers;

use Symfony\Component\Process\Process;

class ActiveCallController extends Controller
{
    public function index()
    {
        $p = new Process(['fs_cli', '-x "show calls as json"']);
        $p->run();

        return response()->json($p->getOutput());
    }
}
