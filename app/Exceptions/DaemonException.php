<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class DaemonException extends Exception
{
    protected ?string $recoverySuggestion = null;

    public function __construct(string $message = '', int $code = 0, ?string $recoverySuggestion = null, ?Throwable $previous = null)
    {
        if ($previous && ! str_contains($message, $previous->getMessage())) {
            $message = "{$message}: {$previous->getMessage()}";
        }

        parent::__construct($message, $code, $previous);
        $this->recoverySuggestion = $recoverySuggestion;
    }

    public function getRecoverySuggestion(): ?string
    {
        if ($this->recoverySuggestion) {
            return $this->recoverySuggestion;
        }

        return match ($this->code) {
            -32601 => 'The requested method is not implemented in the daemon. Please check if you are using the latest version of SuperCP.',
            -32000 => $this->inferSuggestionFromMessage($this->message),
            default => 'An unexpected error occurred while communicating with the system daemon.',
        };
    }

    protected function inferSuggestionFromMessage(string $message): ?string
    {
        if (str_contains($message, 'Permission denied') || str_contains($message, 'sudo access')) {
            return 'The daemon does not have sufficient permissions. Ensure it is running with correct privileges and sudo access.';
        }

        if (str_contains($message, 'Connection refused') || str_contains($message, 'socket not found')) {
            return 'The system daemon is not running. Try restarting it with: sudo systemctl restart super-daemon';
        }

        if (str_contains($message, 'Nginx config') || str_contains($message, 'Nginx enabled')) {
            return 'There was an error with the Nginx configuration. Check the Nginx logs for more details.';
        }

        if (str_contains($message, 'PHP-FPM') || str_contains($message, 'PHP pool')) {
            return 'There was an error with the PHP-FPM configuration. Ensure the requested PHP version is installed and running.';
        }

        if (str_contains($message, 'MySQL') || str_contains($message, 'database') || str_contains($message, 'mysqldump')) {
            return 'There was a database error. Ensure MySQL is running and the credentials are correct.';
        }

        if (str_contains($message, 'certbot') || str_contains($message, 'SSL')) {
            return 'SSL certificate request failed. Ensure your domain is pointing to this server and port 80 is open.';
        }

        if (str_contains($message, 'User') && str_contains($message, 'does not exist')) {
            return 'The system user associated with this action does not exist. Please contact support.';
        }

        if (str_contains($message, 'Only MySQL is supported')) {
            return 'PostgreSQL support is currently being implemented. Please use MySQL for now.';
        }

        if (str_contains($message, 'Backup file not found')) {
            return 'The requested backup file could not be located on the server.';
        }

        return 'Please check the system logs for more information or contact support.';
    }
}
