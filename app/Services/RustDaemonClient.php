<?php

namespace App\Services;

use App\Exceptions\DaemonException;
use Exception;
use Illuminate\Support\Facades\Log;
use JsonException;

class RustDaemonClient
{
    private string $socketPath;

    private int $timeout;

    private int $requestId = 0;

    private int $maxRetries = 3;

    private int $retryDelay = 100; // milliseconds

    private array $mutatingMethods = [
        'create_vhost', 'delete_vhost', 'restart_service', 'create_backup',
        'create_db_backup', 'restore_backup', 'restore_db_backup', 'reload_services',
        'create_database', 'delete_database', 'create_ftp_user', 'delete_ftp_user',
        'update_cron_jobs', 'update_dns_zone', 'delete_dns_zone', 'request_ssl_cert',
        'update_email_account', 'delete_email_account', 'write_file', 'delete_file',
        'create_directory', 'rename_file', 'apply_firewall_rule', 'delete_firewall_rule',
        'toggle_firewall',
    ];

    public function __construct(?string $socketPath = null, int $timeout = 30)
    {
        $this->socketPath = $socketPath ?? storage_path('framework/sockets/super-daemon.sock');
        $this->timeout = $timeout;
    }

    /**
     * Call a JSON-RPC 2.0 method on the Rust daemon
     *
     * @param  string  $method  The JSON-RPC method name
     * @param  array  $params  Parameters to pass to the method
     * @return mixed The result from the daemon
     *
     * @throws DaemonException
     */
    public function call(string $method, array $params = []): mixed
    {
        $this->requestId++;

        $request = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => $this->requestId,
        ];

        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                $response = $this->send(json_encode($request, JSON_THROW_ON_ERROR));

                $result = $this->parseResponse($response);

                if (in_array($method, $this->mutatingMethods)) {
                    \App\Models\AuditLog::log(
                        action: "daemon_{$method}",
                        description: "Called daemon method '{$method}'",
                        result: 'success',
                        changes: $this->sanitizeParams($params)
                    );
                }

                return $result;
            } catch (DaemonException $e) {
                // Only retry on communication errors (codes -1 to -8)
                // JSON-RPC errors (like -32000, -32601) should not be retried
                if ($e->getCode() < -8 || $e->getCode() >= 0) {
                    if (in_array($method, $this->mutatingMethods)) {
                        \App\Models\AuditLog::log(
                            action: "daemon_{$method}",
                            description: "Failed to call daemon method '{$method}': {$e->getMessage()}",
                            result: 'failed',
                            changes: $this->sanitizeParams($params)
                        );
                    }
                    throw $e;
                }
                $lastException = $e;
            } catch (Exception $e) {
                $lastException = $e;
            }

            $attempt++;
            if ($attempt < $this->maxRetries) {
                $delay = $this->retryDelay * pow(2, $attempt - 1);
                usleep($delay * 1000);
            }
        }

        if (in_array($method, $this->mutatingMethods)) {
            \App\Models\AuditLog::log(
                action: "daemon_{$method}",
                description: "Failed to call daemon method '{$method}' after {$this->maxRetries} attempts",
                result: 'failed',
                changes: $this->sanitizeParams($params)
            );
        }

        Log::error('RustDaemonClient failed after retries', [
            'method' => $method,
            'attempts' => $attempt,
            'error' => $lastException?->getMessage(),
        ]);

        if ($lastException instanceof DaemonException) {
            throw $lastException;
        }

        throw new DaemonException("Failed to call daemon method '{$method}' after {$this->maxRetries} attempts: ".($lastException?->getMessage() ?? 'Unknown error'), 0, null, $lastException);
    }

    /**
     * Send a JSON-RPC request to the daemon and get the response
     *
     * @param  string  $request  JSON-RPC request string
     * @return string Response from daemon
     *
     * @throws DaemonException
     */
    private function send(string $request): string
    {
        // Check if daemon socket exists
        if (! file_exists($this->socketPath)) {
            throw new DaemonException("Daemon socket not found at {$this->socketPath}. Is the daemon running?", -1);
        }

        // Create Unix domain socket
        $socket = @socket_create(AF_UNIX, SOCK_STREAM, 0);
        if ($socket === false) {
            throw new DaemonException('Failed to create socket: '.socket_strerror(socket_last_error()), -2);
        }

        try {
            // Connect to daemon
            if (! @socket_connect($socket, $this->socketPath)) {
                throw new DaemonException('Failed to connect to daemon: '.socket_strerror(socket_last_error($socket)), -3);
            }

            // Set timeout
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $this->timeout, 'usec' => 0]);
            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => $this->timeout, 'usec' => 0]);

            // Send request
            $bytes = socket_send($socket, $request."\n", strlen($request) + 1, MSG_EOR);
            if ($bytes === false) {
                throw new DaemonException('Failed to send request: '.socket_strerror(socket_last_error($socket)), -4);
            }

            // Read response
            $response = '';
            while (true) {
                $chunk = @socket_read($socket, 8192);
                if ($chunk === false) {
                    throw new DaemonException('Failed to read response: '.socket_strerror(socket_last_error($socket)), -5);
                }
                if ($chunk === '') {
                    break; // Connection closed
                }
                $response .= $chunk;
            }

            if (empty($response)) {
                throw new DaemonException('Empty response from daemon', -6);
            }

            return trim($response);
        } finally {
            socket_close($socket);
        }
    }

    /**
     * Parse and validate JSON-RPC response
     *
     * @param  string  $response  JSON response string
     * @return mixed The result or error data
     *
     * @throws DaemonException
     */
    private function parseResponse(string $response): mixed
    {
        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new DaemonException("Invalid JSON response from daemon: {$e->getMessage()}", -7);
        }

        // Check for JSON-RPC error
        if (isset($data['error'])) {
            $error = $data['error'];
            $message = $error['message'] ?? 'Unknown error';
            $code = $error['code'] ?? -32000;
            throw new DaemonException($message, $code);
        }

        // Check for result
        if (isset($data['result'])) {
            return $data['result'];
        }

        throw new DaemonException('Invalid JSON-RPC response: missing result or error', -8);
    }

    /**
     * Check if daemon is running
     */
    public function isRunning(): bool
    {
        try {
            $this->call('ping');

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get system statistics
     */
    public function getSystemStats(): array
    {
        return (array) $this->call('get_system_stats');
    }

    /**
     * Get service status
     */
    public function getStatus(): array
    {
        return (array) $this->call('get_status');
    }

    /**
     * Restart a service
     */
    public function restartService(string $service): string
    {
        return (string) $this->call('restart_service', ['service' => $service]);
    }

    /**
     * Get firewall status
     */
    public function getFirewallStatus(): array
    {
        return (array) $this->call('get_firewall_status');
    }

    /**
     * Apply firewall rule
     */
    public function applyFirewallRule(int $port, string $protocol = 'tcp', string $action = 'allow', string $source = 'any'): string
    {
        return (string) $this->call('apply_firewall_rule', [
            'port' => $port,
            'protocol' => $protocol,
            'action' => $action,
            'source' => $source,
        ]);
    }

    /**
     * Delete firewall rule
     */
    public function deleteFirewallRule(int $port, string $protocol = 'tcp', string $action = 'allow'): string
    {
        return (string) $this->call('delete_firewall_rule', [
            'port' => $port,
            'protocol' => $protocol,
            'action' => $action,
        ]);
    }

    /**
     * Toggle firewall
     */
    public function toggleFirewall(bool $enable): string
    {
        return (string) $this->call('toggle_firewall', ['enable' => $enable]);
    }

    /**
     * Create vhost (web domain)
     */
    public function createVhost(array $params): string
    {
        return (string) $this->call('create_vhost', $params);
    }

    /**
     * Delete vhost
     */
    public function deleteVhost(string $domain, string $user, string $phpVersion): string
    {
        return (string) $this->call('delete_vhost', [
            'domain' => $domain,
            'user' => $user,
            'php_version' => $phpVersion,
        ]);
    }

    /**
     * List vhosts
     */
    public function listVhosts(): array
    {
        return (array) $this->call('list_vhosts');
    }

    /**
     * Create database
     */
    public function createDatabase(string $name, string $user, string $password, string $type = 'mysql'): string
    {
        return (string) $this->call('create_database', [
            'name' => $name,
            'user' => $user,
            'password' => $password,
            'type' => $type,
        ]);
    }

    /**
     * Delete database
     */
    public function deleteDatabase(string $name): string
    {
        return (string) $this->call('delete_database', ['name' => $name]);
    }

    /**
     * List databases
     */
    public function listDatabases(): array
    {
        return (array) $this->call('list_databases');
    }

    /**
     * Create FTP user
     */
    public function createFtpUser(string $username, string $password, string $homedir): string
    {
        return (string) $this->call('create_ftp_user', [
            'username' => $username,
            'password' => $password,
            'homedir' => $homedir,
        ]);
    }

    /**
     * Delete FTP user
     */
    public function deleteFtpUser(string $username): string
    {
        return (string) $this->call('delete_ftp_user', ['username' => $username]);
    }

    /**
     * List FTP users
     */
    public function listFtpUsers(): array
    {
        return (array) $this->call('list_ftp_users');
    }

    /**
     * Update cron jobs for a user
     */
    public function updateCronJobs(string $user, array $jobs): string
    {
        return (string) $this->call('update_cron_jobs', [
            'user' => $user,
            'jobs' => $jobs,
        ]);
    }

    /**
     * List cron jobs for a user
     */
    public function listCronJobs(string $user): array
    {
        return (array) $this->call('list_cron_jobs', ['user' => $user]);
    }

    /**
     * Update DNS zone
     */
    public function updateDnsZone(string $domain, array $records): string
    {
        return (string) $this->call('update_dns_zone', [
            'domain' => $domain,
            'records' => $records,
        ]);
    }

    /**
     * Delete DNS zone
     */
    public function deleteDnsZone(string $domain): string
    {
        return (string) $this->call('delete_dns_zone', ['domain' => $domain]);
    }

    /**
     * Request SSL certificate
     */
    public function requestSslCert(string $domain, string $email = 'admin@example.com'): string
    {
        return (string) $this->call('request_ssl_cert', [
            'domain' => $domain,
            'email' => $email,
        ]);
    }

    /**
     * Update email account
     */
    public function updateEmailAccount(string $email, string $password, int $quotaMb = 0): string
    {
        return (string) $this->call('update_email_account', [
            'email' => $email,
            'password' => $password,
            'quota_mb' => $quotaMb,
        ]);
    }

    /**
     * Delete email account
     */
    public function deleteEmailAccount(string $email): string
    {
        return (string) $this->call('delete_email_account', ['email' => $email]);
    }

    /**
     * Create backup
     */
    public function createBackup(string $name, string $sourcePath): string
    {
        return (string) $this->call('create_backup', [
            'name' => $name,
            'source_path' => $sourcePath,
        ]);
    }

    /**
     * Create database backup
     */
    public function createDbBackup(string $dbName): string
    {
        return (string) $this->call('create_db_backup', ['db_name' => $dbName]);
    }

    /**
     * Restore backup
     */
    public function restoreBackup(string $path, string $targetPath): string
    {
        return (string) $this->call('restore_backup', [
            'path' => $path,
            'target_path' => $targetPath,
        ]);
    }

    /**
     * Restore database backup
     */
    public function restoreDbBackup(string $path, string $dbName): string
    {
        return (string) $this->call('restore_db_backup', [
            'path' => $path,
            'db_name' => $dbName,
        ]);
    }

    /**
     * Reload services
     */
    public function reloadServices(): string
    {
        return (string) $this->call('reload_services');
    }

    /**
     * Get logs
     */
    public function getLogs(string $type = 'daemon', int $lines = 50): string
    {
        return (string) $this->call('get_logs', [
            'type' => $type,
            'lines' => $lines,
        ]);
    }

    /**
     * Get service logs
     */
    public function getServiceLogs(string $service, int $lines = 50): string
    {
        return (string) $this->call('get_service_logs', [
            'service' => $service,
            'lines' => $lines,
        ]);
    }

    /**
     * List files in directory
     */
    public function listFiles(string $path): array
    {
        return (array) $this->call('list_files', ['path' => $path]);
    }

    /**
     * Read file content
     */
    public function readFile(string $path): string
    {
        return (string) $this->call('read_file', ['path' => $path]);
    }

    /**
     * Write file content
     */
    public function writeFile(string $path, string $content): string
    {
        return (string) $this->call('write_file', [
            'path' => $path,
            'content' => $content,
        ]);
    }

    /**
     * Delete file or directory
     */
    public function deleteFile(string $path): string
    {
        return (string) $this->call('delete_file', ['path' => $path]);
    }

    /**
     * Create directory
     */
    public function createDirectory(string $path): string
    {
        return (string) $this->call('create_directory', ['path' => $path]);
    }

    /**
     * Rename file or directory
     */
    public function renameFile(string $from, string $to): string
    {
        return (string) $this->call('rename_file', [
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * Sanitize parameters for logging (mask passwords, keys, etc.)
     */
    private function sanitizeParams(array $params): array
    {
        $sensitiveKeys = ['password', 'key', 'secret', 'token', 'content'];

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $params[$key] = $this->sanitizeParams($value);
            } elseif (in_array(strtolower($key), $sensitiveKeys) || str_contains(strtolower($key), 'password')) {
                $params[$key] = '********';
            }
        }

        return $params;
    }
}
