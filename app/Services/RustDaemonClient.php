<?php

namespace App\Services;

use Exception;

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
        $request = json_encode([
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => uniqid(),
        ]);

        $fp = stream_socket_client("unix://{$this->socketPath}", $errno, $errstr, 5);

        if (! $fp) {
            throw new Exception("Could not connect to super-daemon: $errstr ($errno)");
        }

        fwrite($fp, $request."\n");
        $response = fgets($fp);
        fclose($fp);

        return json_decode($response, true);
    }
}
