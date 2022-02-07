<?php

namespace App\Console\Commands;

use App\Models\ActiveCall;
use Illuminate\Console\Command;

class PruneActiveCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'active_calls:prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune active call stats';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        ActiveCall::where('created_at', '<', now()->subDay())->delete();
        return 0;
    }
}
