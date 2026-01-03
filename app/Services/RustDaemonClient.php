<?php

namespace App\Services;

class RustDaemonClient
{
    protected string $socketPath;

    public function __construct()
    {
        // Path to the Unix socket created by the Rust daemon
        $this->socketPath = storage_path('framework/sockets/super-daemon.sock');
    }

    /**
     * Send a JSON-RPC request to the Rust daemon.
     */
    public function call(string $method, array $params = []): array
    {
        try {
            $request = json_encode([
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
                'id' => uniqid(),
            ]);

            $fp = @stream_socket_client("unix://{$this->socketPath}", $errno, $errstr, 5);

            if (! $fp) {
                // Return a graceful error response instead of throwing
                return [
                    'jsonrpc' => '2.0',
                    'error' => [
                        'code' => -32603,
                        'message' => 'Daemon connection failed: '.$errstr,
                    ],
                ];
            }

            fwrite($fp, $request."\n");
            $response = fgets($fp);
            fclose($fp);

            return json_decode($response, true) ?? [];
        } catch (\Exception $e) {
            return [
                'jsonrpc' => '2.0',
                'error' => [
                    'code' => -32603,
                    'message' => 'Daemon error: '.$e->getMessage(),
                ],
            ];
        }
    }
}
