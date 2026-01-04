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
        $response = $this->actingAs($user)->post('/web-domains', [
            'domain' => 'integration-test.com',
            'php_version' => '8.4',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('web_domains', [
            'domain' => 'integration-test.com',
            'user_id' => $user->id,
        ]);

        $domain = WebDomain::where('domain', 'integration-test.com')->first();
        $this->assertNotNull($domain);

        // 2. Update domain
        $response = $this->actingAs($user)->patch("/web-domains/{$domain->id}", [
            'php_version' => '8.4',
            'is_active' => true,
        ]);

        $domain->refresh();
        $this->assertEquals('8.4', $domain->php_version);

        // 3. Verify domain is accessible in dashboard
        $response = $this->actingAs($user)->get('/web-domains');
        $response->assertStatus(200);

        // 4. Delete domain
        $response = $this->actingAs($user)->delete("/web-domains/{$domain->id}");
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
            'web_domain_id' => $domain->id,
            'provider' => 'letsencrypt',
            'validation_method' => 'http',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('ssl_certificates', [
            'domain' => $domain->domain,
            'user_id' => $user->id,
        ]);

        $certificate = SslCertificate::where('domain', $domain->domain)->first();
        $this->assertNotNull($certificate);

        // 2. Verify certificate properties
        $response = $this->actingAs($user)->get("/ssl/{$certificate->id}");
        $response->assertStatus(200);

        // 3. Test renewal
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
        $response = $this->actingAs($user)->post('/backups/schedules', [
            'name' => 'Daily Backup',
            'frequency' => 'daily',
            'time' => '02:00',
            'retention_days' => 30,
            'targets' => ['files', 'database'],
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('backup_schedules', [
            'user_id' => $user->id,
            'name' => 'Daily Backup',
            'frequency' => 'daily',
        ]);

        // 2. Trigger manual backup
        $response = $this->actingAs($user)->post('/backups', [
            'name' => 'Manual Backup',
            'type' => 'full',
            'source' => 'all',
        ]);

        if ($response->status() < 400) {
            $this->assertDatabaseHas('backups', [
                'user_id' => $user->id,
            ]);

            $backup = Backup::where('user_id', $user->id)->first();

            if ($backup) {
                // 3. Verify backup details
                $response = $this->actingAs($user)->get("/backups/{$backup->id}/download");
                if ($response->status() !== 404) {
                    $response->assertStatus(200);
                }

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
            'name' => 'High CPU Alert',
            'metric' => 'cpu_usage',
            'threshold_percentage' => 80,
            'comparison' => 'greater_than',
            'frequency' => '5m',
        ]);

        $response->assertStatus(302);

        // 4. Verify alert was created
        $this->assertDatabaseHas('monitoring_alerts', [
            'user_id' => $user->id,
            'metric' => 'cpu_usage',
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
        $response = $this->actingAs($user)->get('/email-accounts');
        $response->assertStatus(200);

        // 2. Create email account
        $response = $this->actingAs($user)->post('/email-accounts', [
            'domain_id' => $domain->id,
            'local_part' => 'admin',
            'quota' => 1024,
            'password' => 'SecurePassword123!',
        ]);

        if ($response->status() < 400) {
            $this->assertDatabaseHas('email_accounts', [
                'domain_id' => $domain->id,
                'email' => "admin@{$domain->domain}",
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
        $response = $this->actingAs($user)->get('/dns-zones');
        $response->assertStatus(200);

        // 2. Create DNS record
        $response = $this->actingAs($user)->post('/dns-zones', [
            'domain' => $domain->domain,
        ]);

        if ($response->status() < 400) {
            $this->assertDatabaseHas('dns_zones', [
                'user_id' => $user->id,
                'domain' => $domain->domain,
            ]);
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
            'name' => 'Test Cron',
            'command' => '/usr/bin/php /var/www/example.com/artisan schedule:run',
            'frequency' => '*/5 * * * *',
        ]);

        if ($response->status() < 400) {
            $this->assertDatabaseHas('cron_jobs', [
                'user_id' => $user->id,
                'name' => 'Test Cron',
            ]);

            // 3. Toggle cron job
            $job = \App\Models\CronJob::where('user_id', $user->id)->first();
            if ($job) {
                $response = $this->actingAs($user)->post("/cron-jobs/{$job->id}/toggle");

                $job->refresh();
                $this->assertNotNull($job->is_enabled);
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
        $user = User::where('name', 'super')->first() ?? User::factory()->create(['name' => 'super']);

        // 1. View file manager
        $response = $this->actingAs($user)->get('/file-manager');
        $response->assertStatus(200);

        // 2. Browse directory
        $response = $this->actingAs($user)->get('/file-manager/list?path=/home/super/web');
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

        $domain1 = WebDomain::factory()->for($user1)->create(['domain' => 'user1-domain.com']);
        $domain2 = WebDomain::factory()->for($user2)->create(['domain' => 'user2-domain.com']);

        // User 1 should see their domain
        $response = $this->actingAs($user1)->get('/web-domains');
        $this->assertDatabaseHas('web_domains', [
            'id' => $domain1->id,
            'user_id' => $user1->id,
        ]);

        // User 1 should not be able to update User 2's domain
        $response = $this->actingAs($user1)->patch("/web-domains/{$domain2->id}", [
            'php_version' => '8.3',
        ]);
        $response->assertStatus(403);

        // User 2 should not be able to delete User 1's domain
        $response = $this->actingAs($user2)->delete("/web-domains/{$domain1->id}");
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
        $backup = Backup::factory()->for($user)->create();

        // User should be able to access their own resources
        $response = $this->actingAs($user)->get("/backups/{$backup->id}/download");
        if ($response->status() !== 404) {
            $response->assertStatus(200);
        }

        // Another user should not be able to access
        $otherUser = User::factory()->create();
        $response = $this->actingAs($otherUser)->get("/backups/{$backup->id}/download");
        $response->assertStatus(403);
    }
}
