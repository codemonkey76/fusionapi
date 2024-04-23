<?php

namespace App\Console\Commands;

use App\Models\ActiveCall;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class GetActiveCallsByDomain extends Command
{
    protected $signature = 'active_calls:by_domain';
    protected $description = 'Get active calls by domain';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $process = new Process(['/bin/fs_cli', '-x show calls as json']);
        try {
            $process->mustRun();

            $json = $process->getOutput();
            $jsonArray = json_decode($json, true);

            if (isset($jsonArray['rows'])) {
                $data = collect($jsonArray['rows']);
                
                $results = $data->reduce(fn($carry, $item) => $this->reducer($carry, $item));
                collect($results)
                    ->each(fn($counts, $domain) => ActiveCall::create([
                        'domain' => $domain,
                        'inbound' => $counts['inbound'],
                        'outbound' => $counts['outbound']
                    ]));
            } else {
                $this->info("No active calls to process.");
            }

            return 0;
        } catch (ProcessFailedException $exception) {
            $this->error("Could not retrieve active calls!");
            $this->error($exception->getMessage());
            return 1;
        }
    }

    // Used to summarize active call records into just the domain name and total inbound / outbound calls.
    private function reducer($carry, $item) {
        $domain = empty($item['accountcode']) ? $item['b_accountcode'] : $item['accountcode'];

        if (!isset($carry[$domain])) {
            $carry[$domain] = ['inbound' => 0, 'outbound' => 0];
        }

        if ($item['direction'] === 'inbound') {
            $carry[$domain]['inbound']++;
        } else {
            $carry[$domain]['outbound']++;
        }
        return $carry;
    }
}


