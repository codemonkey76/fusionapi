<?php

namespace App\Console\Commands;

use App\Models\ActiveCall;
use Illuminate\Console\Command;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GetActiveCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'active_calls:get';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get active calls';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $process = new Process(['/bin/fs_cli', '-x show calls as json']);
        try {
            $process->mustRun();

            $calls = json_decode($process->getOutput());

            if (!property_exists($calls, 'rows')) {
                $active_calls = (object)null;
                $active_calls->inbound = 0;
                $active_calls->outbound = 0;
                $this->info(json_encode($active_calls));
                ActiveCall::create((array)$active_calls);
                return 0;
            }

            $active_calls = (object)collect($calls->rows)
                ->groupBy(fn($call) => $call->direction)
                ->map(fn($call, $direction) => collect($call)->count())->toArray();

            if (!property_exists($active_calls, 'outbound')) $active_calls->outbound = 0;
            if (!property_exists($active_calls, 'inbound')) $active_calls->inbound = 0;
            $this->info(json_encode($active_calls));
            ActiveCall::create((array)$active_calls);

            return 0;
        } catch (ProcessFailedException $exception) {
            $this->error("Could not retrieve active calls!");
            $this->error($exception->getMessage());
            return 1;
        }
    }
}
