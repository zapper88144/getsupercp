<?php

namespace App\Services;

use App\Models\DnsZone;
use App\Models\EmailAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SystemSyncService
{
    /**
     * Sync a DNS zone to PowerDNS tables
     */
    public function syncDnsZone(DnsZone $zone): void
    {
        try {
            DB::transaction(function () use ($zone) {
                // 1. Ensure domain exists in 'domains' table
                $domainId = DB::table('domains')->updateOrInsert(
                    ['name' => $zone->domain],
                    [
                        'type' => 'NATIVE',
                        'last_check' => null,
                        'account' => 'supercp',
                    ]
                );

                // Get the actual ID (updateOrInsert doesn't return it)
                $pdnsDomain = DB::table('domains')->where('name', $zone->domain)->first();
                $pdnsDomainId = $pdnsDomain->id;

                // 2. Clear existing records for this domain in PDNS
                DB::table('records')->where('domain_id', $pdnsDomainId)->delete();

                // 3. Add SOA record (required for PowerDNS)
                $soaContent = "ns1.{$zone->domain} admin.{$zone->domain} ".date('Ymd').'01 10800 3600 604800 3600';
                DB::table('records')->insert([
                    'domain_id' => $pdnsDomainId,
                    'name' => $zone->domain,
                    'type' => 'SOA',
                    'content' => $soaContent,
                    'ttl' => 3600,
                    'prio' => 0,
                    'auth' => 1,
                ]);

                // 4. Add NS records
                DB::table('records')->insert([
                    'domain_id' => $pdnsDomainId,
                    'name' => $zone->domain,
                    'type' => 'NS',
                    'content' => "ns1.{$zone->domain}",
                    'ttl' => 3600,
                    'prio' => 0,
                    'auth' => 1,
                ]);

                DB::table('records')->insert([
                    'domain_id' => $pdnsDomainId,
                    'name' => $zone->domain,
                    'type' => 'NS',
                    'content' => "ns2.{$zone->domain}",
                    'ttl' => 3600,
                    'prio' => 0,
                    'auth' => 1,
                ]);

                // 5. Add all records from DnsRecord model
                foreach ($zone->records as $record) {
                    $name = $record->name === '@' ? $zone->domain : "{$record->name}.{$zone->domain}";

                    DB::table('records')->insert([
                        'domain_id' => $pdnsDomainId,
                        'name' => $name,
                        'type' => $record->type,
                        'content' => $record->value,
                        'ttl' => $record->ttl,
                        'prio' => $record->priority ?? 0,
                        'auth' => 1,
                    ]);
                }
            });

            Log::info("Synced DNS zone to PowerDNS: {$zone->domain}");
        } catch (\Exception $e) {
            Log::error("Failed to sync DNS zone to PowerDNS: {$zone->domain}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete a DNS zone from PowerDNS tables
     */
    public function deleteDnsZone(string $domain): void
    {
        try {
            DB::transaction(function () use ($domain) {
                $pdnsDomain = DB::table('domains')->where('name', $domain)->first();
                if ($pdnsDomain) {
                    DB::table('records')->where('domain_id', $pdnsDomain->id)->delete();
                    DB::table('domains')->where('id', $pdnsDomain->id)->delete();
                }
            });
            Log::info("Deleted DNS zone from PowerDNS: {$domain}");
        } catch (\Exception $e) {
            Log::error("Failed to delete DNS zone from PowerDNS: {$domain}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync an email account to Postfix/Dovecot tables
     */
    public function syncEmailAccount(EmailAccount $account): void
    {
        try {
            $parts = explode('@', $account->email);
            if (count($parts) !== 2) {
                return;
            }
            $domainName = $parts[1];

            DB::transaction(function () use ($account, $domainName) {
                // 1. Ensure domain exists in 'virtual_domains'
                DB::table('virtual_domains')->updateOrInsert(
                    ['name' => $domainName],
                    ['name' => $domainName]
                );

                $domain = DB::table('virtual_domains')->where('name', $domainName)->first();

                // 2. Ensure user exists in 'virtual_users'
                // Note: We use the password from the account model.
                // Postfix/Dovecot usually expect a specific hash format (e.g. SHA512-CRYPT).
                // For now we'll use the bcrypt hash from Laravel, but in production
                // we might need to adjust this based on Dovecot config.
                DB::table('virtual_users')->updateOrInsert(
                    ['email' => $account->email],
                    [
                        'domain_id' => $domain->id,
                        'password' => $account->password,
                        'email' => $account->email,
                    ]
                );
            });

            Log::info("Synced email account to Postfix/Dovecot: {$account->email}");
        } catch (\Exception $e) {
            Log::error("Failed to sync email account to Postfix/Dovecot: {$account->email}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete an email account from Postfix/Dovecot tables
     */
    public function deleteEmailAccount(string $email): void
    {
        try {
            DB::table('virtual_users')->where('email', $email)->delete();
            Log::info("Deleted email account from Postfix/Dovecot: {$email}");
        } catch (\Exception $e) {
            Log::error("Failed to delete email account from Postfix/Dovecot: {$email}", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync an FTP user to Pure-FTPd tables
     */
    public function syncFtpUser(\App\Models\FtpUser $ftpUser): void
    {
        try {
            DB::table('ftp_users')->updateOrInsert(
                ['user' => $ftpUser->username],
                [
                    'user' => $ftpUser->username,
                    'password' => $ftpUser->password, // Laravel bcrypt hash
                    'dir' => $ftpUser->home_dir,
                    'active' => $ftpUser->status === 'active',
                ]
            );
            Log::info("Synced FTP user to Pure-FTPd: {$ftpUser->username}");
        } catch (\Exception $e) {
            Log::error("Failed to sync FTP user to Pure-FTPd: {$ftpUser->username}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Delete an FTP user from Pure-FTPd tables
     */
    public function deleteFtpUser(string $username): void
    {
        try {
            DB::table('ftp_users')->where('user', $username)->delete();
            Log::info("Deleted FTP user from Pure-FTPd: {$username}");
        } catch (\Exception $e) {
            Log::error("Failed to delete FTP user from Pure-FTPd: {$username}", [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
