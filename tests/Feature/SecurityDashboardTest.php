<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Tests\TestCase;

class SecurityDashboardTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_security_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get('/security');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Security/Dashboard'));
    }

    public function test_audit_logs_displayed(): void
    {
        AuditLog::factory(5)->for($this->user)->create(['action' => 'login']);

        $response = $this->actingAs($this->user)->get('/security');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->has('recentLogs', 5));
    }

    public function test_failed_login_count(): void
    {
        AuditLog::factory(3)
            ->for($this->user)
            ->create(['action' => 'login', 'result' => 'failed']);

        AuditLog::factory(2)
            ->for($this->user)
            ->create(['action' => 'login', 'result' => 'success']);

        $response = $this->actingAs($this->user)->get('/security');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('failedLogins', 3));
    }

    public function test_user_can_view_audit_logs(): void
    {
        AuditLog::factory(15)->for($this->user)->create();

        $response = $this->actingAs($this->user)->get('/security/audit-logs');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Security/AuditLogs'));
    }

    public function test_audit_log_filters(): void
    {
        AuditLog::factory(5)->for($this->user)->create(['action' => 'create', 'result' => 'success']);
        AuditLog::factory(3)->for($this->user)->create(['action' => 'delete', 'result' => 'failed']);

        $this->assertEquals(5, AuditLog::byUser($this->user->id)->where('action', 'create')->count());
        $this->assertEquals(3, AuditLog::byUser($this->user->id)->failures()->count());
    }

    public function test_suspicious_activity_detection(): void
    {
        AuditLog::factory(6)
            ->for($this->user)
            ->create(['action' => 'login', 'result' => 'failed']);

        $response = $this->actingAs($this->user)->get('/security');

        $response->assertInertia(fn ($page) => $page->where('suspiciousActivity', true));
    }
}
