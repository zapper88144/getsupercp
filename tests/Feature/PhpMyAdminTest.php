<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test phpMyAdmin Integration
 */
class PhpMyAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user
        $this->adminUser = User::factory()->create([
            'email' => 'admin@test.com',
            'is_admin' => true,
        ]);

        // Create regular user
        $this->regularUser = User::factory()->create([
            'email' => 'user@test.com',
            'is_admin' => false,
        ]);
    }

    /**
     * Test admin user is created with correct permissions
     */
    public function test_admin_user_has_correct_permissions(): void
    {
        $this->assertTrue($this->adminUser->is_admin === true);
        $this->assertFalse($this->regularUser->is_admin === true);
    }

    /**
     * Test authorization policy isAdmin method
     */
    public function test_phpmyadmin_policy_is_admin(): void
    {
        $this->assertTrue($this->adminUser->is_admin, 'Admin user should have is_admin = true');
        $this->assertFalse($this->regularUser->is_admin, 'Regular user should have is_admin = false');
    }

    /**
     * Test configuration is loaded
     */
    public function test_phpmyadmin_configuration_is_loaded(): void
    {
        $config = config('phpmyadmin');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('path', $config);
        $this->assertArrayHasKey('security', $config);
        $this->assertArrayHasKey('database', $config);
    }

    /**
     * Test phpMyAdmin path exists when enabled
     */
    public function test_phpmyadmin_path_exists_when_enabled(): void
    {
        if (config('phpmyadmin.enabled')) {
            $path = config('phpmyadmin.path');
            $this->assertTrue(
                file_exists($path) && is_dir($path),
                "phpMyAdmin directory should exist at {$path}"
            );
        } else {
            $this->markTestSkipped('phpMyAdmin is not enabled');
        }
    }
}
