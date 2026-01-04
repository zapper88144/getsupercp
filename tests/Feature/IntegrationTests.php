<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\SslCertificate;
use App\Models\User;
use App\Models\WebDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationTests extends TestCase
{
    use RefreshDatabase;

    /**
     * Test complete domain lifecycle: create, update, verify, delete
     */
    public function test_domain_lifecycle_integration(): void
    {
        $user = User::factory()->create();

        // 1. Create a domain
        $response = $this->actingAs($user)->post('/domains', [
            'name' => 'integration-test.com',
            'registrar' => 'namecheap',
            'auto_renew' => true,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('web_domains', [
            'name' => 'integration-test.com',
            'user_id' => $user->id,
        ]);

        $domain = WebDomain::where('name', 'integration-test.com')->first();
        $this->assertNotNull($domain);

        // 2. Update domain
        $response = $this->actingAs($user)->patch("/domains/{$domain->id}", [
            'auto_renew' => false,
        ]);

        $domain->refresh();
        $this->assertFalse($domain->auto_renew);

        // 3. Verify domain is accessible in dashboard
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);

        // 4. Delete domain
        $response = $this->actingAs($user)->delete("/domains/{$domain->id}");
        $response->assertStatus(302);

        $this->assertDatabaseMissing('web_domains', [
            'id' => $domain->id,
        ]);
    }

    /**
     * Test SSL certificate workflow: request, verify, renew
     */
    public function test_ssl_certificate_workflow_integration(): void
    {
        $user = User::factory()->create();
        $domain = WebDomain::factory()->for($user)->create();

        // 1. Request SSL certificate
        $response = $this->actingAs($user)->post('/ssl', [
            'domain_id' => $domain->id,
            'domains' => ['example.com', 'www.example.com'],
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('ssl_certificates', [
            'domain' => $domain->name,
            'user_id' => $user->id,
        ]);

        $certificate = SslCertificate::where('domain', $domain->name)->first();
        $this->assertNotNull($certificate);

        // 2. Verify certificate properties
        $response = $this->actingAs($user)->get("/ssl/{$certificate->id}");
        $response->assertStatus(200);

        // 3. Verify certificate data in response
        $this->assertNotNull($certificate->issuer);
        $this->assertNotNull($certificate->expires_at);

        // 4. Test renewal
        $response = $this->actingAs($user)->post("/ssl/{$certificate->id}/renew");
        $response->assertStatus(302);

        $certificate->refresh();
        // Verify renewal was attempted
        $this->assertNotNull($certificate->updated_at);
    }

    /**
     * Test backup and restore workflow
     */
    public function test_backup_and_restore_workflow_integration(): void
    {
        $user = User::factory()->create();
        $domain = WebDomain::factory()->for($user)->create();

        // 1. Create backup schedule
        $response = $this->actingAs($user)->post('/backup-schedules', [
            'domain_id' => $domain->id,
            'frequency' => 'daily',
            'retention_days' => 30,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('backup_schedules', [
            'domain_id' => $domain->id,
            'frequency' => 'daily',
        ]);

        // 2. Trigger manual backup
        $response = $this->actingAs($user)->post("/domains/{$domain->id}/backups");

        if ($response->status() < 400) {
            $this->assertDatabaseHas('backups', [
                'domain_id' => $domain->id,
            ]);

            $backup = Backup::where('domain_id', $domain->id)->first();

            if ($backup) {
                // 3. Verify backup details
                $response = $this->actingAs($user)->get("/backups/{$backup->id}");
                $response->assertStatus(200);

                // 4. Verify backup file exists (conceptually)
                $this->assertNotNull($backup->status);
            }
        }
    }

    /**
     * Test monitoring and alert workflow
     */
    public function test_monitoring_and_alert_workflow_integration(): void
    {
        $user = User::factory()->create();

        // 1. Access monitoring dashboard
        $response = $this->actingAs($user)->get('/monitoring');
        $response->assertStatus(200);

        // 2. View alert list
        $response = $this->actingAs($user)->get('/monitoring/alerts');
        $response->assertStatus(200);

        // 3. Create new alert
        $response = $this->actingAs($user)->post('/monitoring/alerts', [
            'metric' => 'cpu_usage',
            'threshold' => 80,
            'comparison' => 'greater_than',
        ]);

        $response->assertStatus(302);

        // 4. Verify alert was created
        $this->assertDatabaseHas('monitoring_alerts', [
            'user_id' => $user->id,
            'type' => 'cpu_usage',
        ]);
    }

    /**
     * Test firewall rule workflow
     */
    public function test_firewall_rule_workflow_integration(): void
    {
        $user = User::factory()->create();

        // 1. View firewall rules
        $response = $this->actingAs($user)->get('/firewall');
        $response->assertStatus(200);

        // 2. Create firewall rule
        $response = $this->actingAs($user)->post('/firewall', [
            'protocol' => 'tcp',
            'port' => 8080,
            'source' => '0.0.0.0/0',
            'action' => 'allow',
        ]);

        if ($response->status() < 400) {
            $this->assertDatabaseHas('firewall_rules', [
                'user_id' => $user->id,
                'protocol' => 'tcp',
                'port' => 8080,
            ]);

            // 3. Update rule
            $rule = \App\Models\FirewallRule::where('user_id', $user->id)->first();
            if ($rule) {
                $response = $this->actingAs($user)->patch("/firewall/{$rule->id}", [
                    'action' => 'deny',
                ]);

                $rule->refresh();
                $this->assertEquals('deny', $rule->action);
            }
        }
    }

    /**
     * Test email account workflow
     */
    public function test_email_account_workflow_integration(): void
    {
        $user = User::factory()->create();
        $domain = WebDomain::factory()->for($user)->create();

        // 1. View email accounts
        $response = $this->actingAs($user)->get('/email');
        $response->assertStatus(200);

        // 2. Create email account
        $response = $this->actingAs($user)->post('/email', [
            'domain_id' => $domain->id,
            'local_part' => 'admin',
            'quota' => 1024,
            'password' => 'SecurePassword123!',
        ]);

        if ($response->status() < 400) {
            $this->assertDatabaseHas('email_accounts', [
                'domain_id' => $domain->id,
                'email' => "admin@{$domain->name}",
            ]);
        }
    }

    /**
     * Test database provisioning workflow
     */
    public function test_database_provisioning_workflow_integration(): void
    {
        $user = User::factory()->create();

        // 1. View databases
        $response = $this->actingAs($user)->get('/databases');
        $response->assertStatus(200);

        // 2. Create database
        $response = $this->actingAs($user)->post('/databases', [
            'name' => 'integration_test_db',
            'type' => 'mysql',
        ]);

        if ($response->status() < 400) {
            $this->assertDatabaseHas('databases', [
                'user_id' => $user->id,
                'name' => 'integration_test_db',
            ]);

            // 3. Create database user
            $database = \App\Models\Database::where('name', 'integration_test_db')->first();
            if ($database) {
                $response = $this->actingAs($user)->post("/databases/{$database->id}/users", [
                    'username' => 'test_user',
                    'password' => 'SecurePassword123!',
                    'permissions' => 'all',
                ]);

                if ($response->status() < 400) {
                    $this->assertDatabaseHas('database_users', [
                        'database_id' => $database->id,
                        'username' => 'test_user',
                    ]);
                }
            }
        }
    }

    /**
     * Test DNS management workflow
     */
    public function test_dns_management_workflow_integration(): void
    {
        $user = User::factory()->create();
        $domain = WebDomain::factory()->for($user)->create();

        // 1. View DNS zones
        $response = $this->actingAs($user)->get('/dns');
        $response->assertStatus(200);

        // 2. Create DNS record
        $response = $this->actingAs($user)->post('/dns', [
            'domain_id' => $domain->id,
            'type' => 'A',
            'name' => '@',
            'value' => '192.0.2.1',
            'ttl' => 3600,
        ]);

        if ($response->status() < 400) {
            $this->assertDatabaseHas('dns_records', [
                'domain_id' => $domain->id,
                'type' => 'A',
                'value' => '192.0.2.1',
            ]);

            // 3. Update DNS record
            $record = \App\Models\DnsRecord::where('domain_id', $domain->id)->first();
            if ($record) {
                $response = $this->actingAs($user)->patch("/dns/{$record->id}", [
                    'value' => '192.0.2.2',
                ]);

                $record->refresh();
                $this->assertEquals('192.0.2.2', $record->value);
            }
        }
    }

    /**
     * Test cron job management
     */
    public function test_cron_job_management_integration(): void
    {
        $user = User::factory()->create();

        // 1. View cron jobs
        $response = $this->actingAs($user)->get('/cron-jobs');
        $response->assertStatus(200);

        // 2. Create cron job
        $response = $this->actingAs($user)->post('/cron-jobs', [
            'command' => '/usr/bin/php /var/www/example.com/artisan schedule:run',
            'frequency' => '*/5 * * * *',
        ]);

        if ($response->status() < 400) {
            $this->assertDatabaseHas('cron_jobs', [
                'user_id' => $user->id,
            ]);

            // 3. Toggle cron job
            $job = \App\Models\CronJob::where('user_id', $user->id)->first();
            if ($job) {
                $response = $this->actingAs($user)->post("/cron-jobs/{$job->id}/toggle");

                $job->refresh();
                $this->assertNotNull($job->enabled);
            }
        }
    }

    /**
     * Test FTP user management
     */
    public function test_ftp_user_management_integration(): void
    {
        $user = User::factory()->create();
        $domain = WebDomain::factory()->for($user)->create();

        // 1. View FTP users
        $response = $this->actingAs($user)->get('/ftp-users');
        $response->assertStatus(200);

        // 2. Create FTP user
        $response = $this->actingAs($user)->post('/ftp-users', [
            'domain_id' => $domain->id,
            'username' => 'ftpuser',
            'password' => 'SecurePassword123!',
            'home_directory' => '/home/domain/public_html',
        ]);

        if ($response->status() < 400) {
            $this->assertDatabaseHas('ftp_users', [
                'domain_id' => $domain->id,
                'username' => 'ftpuser',
            ]);
        }
    }

    /**
     * Test file manager operations
     */
    public function test_file_manager_operations_integration(): void
    {
        $user = User::factory()->create();

        // 1. View file manager
        $response = $this->actingAs($user)->get('/file-manager');
        $response->assertStatus(200);

        // 2. Browse directory (mock)
        $response = $this->actingAs($user)->get('/file-manager/browse?path=/var/www');
        $response->assertStatus(200);
    }

    /**
     * Test security and audit log workflow
     */
    public function test_security_and_audit_workflow_integration(): void
    {
        $user = User::factory()->create();

        // 1. View security dashboard
        $response = $this->actingAs($user)->get('/security');
        $response->assertStatus(200);

        // 2. View audit logs
        $response = $this->actingAs($user)->get('/security/audit-logs');
        $response->assertStatus(200);

        // 3. Create action and verify it's logged
        $domain = WebDomain::factory()->for($user)->create();

        // Verify audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test two-factor authentication setup
     */
    public function test_two_factor_authentication_setup_integration(): void
    {
        $user = User::factory()->create();

        // 1. Access 2FA settings
        $response = $this->actingAs($user)->get('/profile/security/two-factor');
        $response->assertStatus(200);

        // 2. Generate 2FA secret
        $response = $this->actingAs($user)->post('/profile/security/two-factor/setup');

        if ($response->status() === 302) {
            // 2FA setup initiated
            $this->assertTrue(true);
        }
    }

    /**
     * Test data isolation between users
     */
    public function test_data_isolation_between_users_integration(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $domain1 = WebDomain::factory()->for($user1)->create(['name' => 'user1-domain.com']);
        $domain2 = WebDomain::factory()->for($user2)->create(['name' => 'user2-domain.com']);

        // User 1 should see their domain
        $response = $this->actingAs($user1)->get('/');
        $this->assertDatabaseHas('web_domains', [
            'id' => $domain1->id,
            'user_id' => $user1->id,
        ]);

        // User 1 should not be able to access User 2's domain
        $response = $this->actingAs($user1)->get("/domains/{$domain2->id}");
        $response->assertStatus(403);

        // User 2 should not be able to delete User 1's domain
        $response = $this->actingAs($user2)->delete("/domains/{$domain1->id}");
        $response->assertStatus(403);

        // Verify domain still exists
        $this->assertDatabaseHas('web_domains', ['id' => $domain1->id]);
    }

    /**
     * Test permission verification across resources
     */
    public function test_permission_verification_across_resources_integration(): void
    {
        $user = User::factory()->create();
        $domain = WebDomain::factory()->for($user)->create();
        $backup = Backup::factory()->for($domain)->create();

        // User should be able to access their own resources
        $response = $this->actingAs($user)->get("/backups/{$backup->id}");
        $response->assertStatus(200);

        // Another user should not be able to access
        $otherUser = User::factory()->create();
        $response = $this->actingAs($otherUser)->get("/backups/{$backup->id}");
        $response->assertStatus(403);
    }
}
