<?php

namespace App\Services;

use App\Traits\HandlesDaemonErrors;

class LogService
{
    use HandlesDaemonErrors;

    public function __construct(protected RustDaemonClient $daemon) {}

    /**
     * Get logs of a specific type.
     *
     * @param  string  $type  The type of log (daemon, nginx_access, nginx_error, php_error)
     * @param  int  $lines  Number of lines to fetch
     */
    public function getLogs(string $type, int $lines = 100): string
    {
        return $this->handleDaemonCall(function () use ($type, $lines) {
            $response = $this->daemon->call('get_logs', [
                'type' => $type,
                'lines' => $lines,
            ]);

            return $response['result'] ?? 'No logs found or error reading logs.';
        }, "Failed to fetch {$type} logs");
    }

    /**
     * Get available log types.
     */
    public function getLogTypes(): array
    {
        return [
            ['id' => 'daemon', 'name' => 'System Daemon'],
            ['id' => 'nginx_access', 'name' => 'Nginx Access'],
            ['id' => 'nginx_error', 'name' => 'Nginx Error'],
            ['id' => 'php_error', 'name' => 'PHP Error'],
        ];
    }
}
