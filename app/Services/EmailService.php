<?php

namespace App\Services;

use App\Models\EmailAccount;
use App\Models\User;
use App\Traits\HandlesDaemonErrors;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailService
{
    use HandlesDaemonErrors;

    public function __construct(
        private RustDaemonClient $daemon,
        private SystemSyncService $syncService
    ) {}

    /**
     * Create an email account
     */
    public function create(User $user, array $data): EmailAccount
    {
        // Validate email format
        if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Check if email already exists
        if (EmailAccount::where('email', $data['email'])->exists()) {
            throw new Exception('Email account already exists');
        }

        return $this->handleDaemonCall(function () use ($user, $data) {
            $password = $data['password'] ?? Str::random(16);

            // Create email account on daemon (uses update_email_account)
            $this->daemon->call('update_email_account', [
                'email' => $data['email'],
                'password' => $password,
                'quota_mb' => $data['quota_mb'] ?? 1024,
            ]);

            // Create database record
            $account = EmailAccount::create([
                'user_id' => $user->id,
                'email' => $data['email'],
                'password' => bcrypt($password),
                'quota_mb' => $data['quota_mb'] ?? 1024,
                'status' => 'active',
            ]);

            // Sync to Postfix/Dovecot system tables
            $this->syncService->syncEmailAccount($account);

            return $account;
        }, "Failed to create email account: {$data['email']}");
    }

    /**
     * Update an email account
     */
    public function update(EmailAccount $account, array $data): EmailAccount
    {
        return $this->handleDaemonCall(function () use ($account, $data) {
            // If password is being updated
            if (isset($data['password'])) {
                $password = $data['password'];
                $this->daemon->call('update_email_account', [
                    'email' => $account->email,
                    'password' => $password,
                    'quota_mb' => $data['quota_mb'] ?? $account->quota_mb,
                ]);
                $data['password'] = bcrypt($password);
            }

            $account->update($data);

            // Sync to Postfix/Dovecot system tables
            $this->syncService->syncEmailAccount($account);

            Log::info('Email account updated', [
                'email' => $account->email,
                'data' => array_keys($data),
            ]);

            return $account->fresh();
        }, "Failed to update email account: {$account->email}");
    }

    /**
     * Delete an email account
     */
    public function delete(EmailAccount $account): bool
    {
        return $this->handleDaemonCall(function () use ($account) {
            $email = $account->email;

            // Delete from daemon
            $this->daemon->call('delete_email_account', [
                'email' => $email,
            ]);

            Log::info('Email account deleted from daemon', ['email' => $email]);

            // Delete from Postfix/Dovecot system tables
            $this->syncService->deleteEmailAccount($email);

            // Delete from database
            return $account->delete();
        }, "Failed to delete email account: {$account->email}");
    }

    /**
     * Update email quota
     */
    public function updateQuota(EmailAccount $account, int $quotaMb): EmailAccount
    {
        return $this->handleDaemonCall(function () use ($account, $quotaMb) {
            $this->daemon->call('update_email_account', [
                'email' => $account->email,
                'password' => '', // Password not changing
                'quota_mb' => $quotaMb,
            ]);

            $account->update(['quota_mb' => $quotaMb]);

            Log::info('Email quota updated', [
                'email' => $account->email,
                'quota_mb' => $quotaMb,
            ]);

            return $account->fresh();
        }, "Failed to update email quota: {$account->email}");
    }

    /**
     * Check if daemon is responding
     */
    public function isDaemonRunning(): bool
    {
        return $this->daemon->isRunning();
    }
}
