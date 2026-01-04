<?php

namespace App\Services;

use App\Models\EmailServerConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EmailServerConfigService
{
    public const CACHE_KEY = 'email_server_config';

    public const CACHE_TTL = 3600; // 1 hour

    public function __construct() {}

    /**
     * Get or create the email server configuration
     */
    public function getConfig(): EmailServerConfig
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return EmailServerConfig::first() ?? EmailServerConfig::create([
                'smtp_host' => env('MAIL_HOST', 'localhost'),
                'smtp_port' => env('MAIL_PORT', 587),
                'smtp_secure' => env('MAIL_ENCRYPTION', 'tls'),
                'smtp_username' => null,
                'smtp_password' => null,
                'imap_host' => env('IMAP_HOST', 'localhost'),
                'imap_port' => env('IMAP_PORT', 993),
                'imap_secure' => 'ssl',
                'pop3_host' => env('POP3_HOST', 'localhost'),
                'pop3_port' => env('POP3_PORT', 995),
                'pop3_secure' => 'ssl',
                'max_user_mailboxes' => 10000,
                'max_mailbox_size_mb' => 5000,
                'enable_spam_filter' => true,
                'enable_antivirus' => true,
                'sender_domain' => config('app.url') ? parse_url(config('app.url'), PHP_URL_HOST) : 'localhost',
            ]);
        });
    }

    /**
     * Update email server configuration
     */
    public function updateConfig(array $data): EmailServerConfig
    {
        $config = EmailServerConfig::first() ?? new EmailServerConfig;

        $updateData = array_intersect_key($data, array_flip([
            'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_username', 'smtp_password',
            'imap_host', 'imap_port', 'imap_secure',
            'pop3_host', 'pop3_port', 'pop3_secure',
            'max_user_mailboxes', 'max_mailbox_size_mb',
            'enable_spam_filter', 'enable_antivirus', 'sender_domain',
        ]));

        $config->fill($updateData)->save();

        // Clear cache
        Cache::forget(self::CACHE_KEY);

        Log::info('Email server configuration updated', [
            'keys' => array_keys($updateData),
        ]);

        return $config;
    }

    /**
     * Test SMTP connection
     */
    public function testSmtpConnection(): bool
    {
        try {
            $config = $this->getConfig();

            $command = sprintf(
                'timeout 5 bash -c "echo | nc -w 3 %s %d" 2>/dev/null',
                escapeshellarg($config->smtp_host),
                $config->smtp_port
            );

            $result = shell_exec($command);

            return (bool) $result;
        } catch (\Throwable $e) {
            Log::error('SMTP connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Test IMAP connection
     */
    public function testImapConnection(): bool
    {
        try {
            $config = $this->getConfig();

            $command = sprintf(
                'timeout 5 bash -c "echo | nc -w 3 %s %d" 2>/dev/null',
                escapeshellarg($config->imap_host),
                $config->imap_port
            );

            $result = shell_exec($command);

            return (bool) $result;
        } catch (\Throwable $e) {
            Log::error('IMAP connection test failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get SMTP configuration for Laravel Mail
     */
    public function getSmtpConfig(): array
    {
        $config = $this->getConfig();

        return [
            'host' => $config->smtp_host,
            'port' => $config->smtp_port,
            'encryption' => $config->smtp_secure,
            'username' => $config->smtp_username,
            'password' => $config->smtp_password,
        ];
    }

    /**
     * Get IMAP configuration for client access
     */
    public function getImapConfig(): array
    {
        $config = $this->getConfig();

        return [
            'host' => $config->imap_host,
            'port' => $config->imap_port,
            'security' => $config->imap_secure,
        ];
    }

    /**
     * Get POP3 configuration for client access
     */
    public function getPop3Config(): array
    {
        $config = $this->getConfig();

        return [
            'host' => $config->pop3_host,
            'port' => $config->pop3_port,
            'security' => $config->pop3_secure,
        ];
    }
}
