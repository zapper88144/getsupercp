<?php

namespace App\Console\Commands;

use App\Services\RustDaemonClient;
use Illuminate\Console\Command;

class TestRustDaemon extends Command
{
    protected $signature = 'daemon:ping';

    protected $description = 'Ping the Rust super-daemon';

    public function handle(RustDaemonClient $client)
    {
        $this->info('Pinging super-daemon...');
        try {
            $response = $client->call('ping');
            $this->info('Response: '.json_encode($response));
        } catch (\Exception $e) {
            $this->error('Error: '.$e->getMessage());
        }
    }
}
