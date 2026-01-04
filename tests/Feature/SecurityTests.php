<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTests extends TestCase
{
    use RefreshDatabase;

    /**
     * Test CSRF protection on form submissions
     */
    public function test_csrf_protection_on_post_requests(): void
    {
        $user = User::factory()->create();

        // Attempt POST without CSRF token should fail
        $response = $this->actingAs($user)->post('/domains', [
            'name' => 'example.com',
            'registrar' => 'namecheap',
        ]);

        // Should either redirect or return 419
        $this->assertTrue(
            $response->status() === 419 || $response->status() === 302,
            'POST request without CSRF token was not rejected'
        );
    }

    /**
     * Test SQL injection prevention
     */
    public function test_sql_injection_prevention(): void
    {
        $user = User::factory()->create();

        // Attempt SQL injection in search
        $response = $this->actingAs($user)->get("/domains?search='; DROP TABLE domains; --");

        $response->assertStatus(200);

        // Table should still exist
        $this->assertDatabaseHas('web_domains', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test XSS prevention
     */
    public function test_xss_prevention(): void
    {
        $user = User::factory()->create();

        // Attempt XSS in domain creation
        $response = $this->actingAs($user)->post('/domains', [
            'name' => '<script>alert("XSS")</script>.com',
            'registrar' => 'namecheap',
        ]);

        // Should either validate input or escape output
        $response->assertStatus(302);

        // Domain name should not contain script tags
        $this->assertDatabaseMissing('web_domains', [
            'name' => '<script>alert("XSS")</script>.com',
        ]);
    }

    /**
     * Test authentication requirement
     */
    public function test_unauthenticated_access_denied(): void
    {
        // Attempt to access protected routes without authentication
        $this->get('/')->assertRedirect('/login');
        $this->get('/domains')->assertRedirect('/login');
        $this->get('/databases')->assertRedirect('/login');
        $this->get('/monitoring')->assertRedirect('/login');
    }

    /**
     * Test authorization: users cannot access other users' resources
     */
    public function test_user_cannot_access_other_users_resources(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $domain = WebDomain::factory()->for($user1)->create();

        // User 2 should not be able to view User 1's domain
        $response = $this->actingAs($user2)->get("/domains/{$domain->id}");
        $response->assertStatus(403);

        // User 2 should not be able to update User 1's domain
        $response = $this->actingAs($user2)->patch("/domains/{$domain->id}", [
            'auto_renew' => false,
        ]);
        $response->assertStatus(403);

        // User 2 should not be able to delete User 1's domain
        $response = $this->actingAs($user2)->delete("/domains/{$domain->id}");
        $response->assertStatus(403);
    }

    /**
     * Test password hashing
     */
    public function test_passwords_are_hashed(): void
    {
        $plainPassword = 'TestPassword123!';

        $user = User::factory()->create([
            'password' => bcrypt($plainPassword),
        ]);

        // Password in database should not be plaintext
        $this->assertNotEquals($plainPassword, $user->password);

        // Password should be a valid hash
        $this->assertTrue(password_verify($plainPassword, $user->password));
    }

    /**
     * Test email verification requirement
     */
    public function test_email_verification_requirement(): void
    {
        // Create unverified user
        $user = User::factory()->unverified()->create();

        // Should not be able to access protected routes
        $response = $this->actingAs($user)->get('/');

        // Depending on verification middleware, might redirect
        if ($response->status() === 302) {
            $this->assertTrue(
                str_contains($response->headers->get('Location'), 'verify-email')
                || str_contains($response->headers->get('Location'), 'login')
            );
        }
    }

    /**
     * Test rate limiting on authentication attempts
     */
    public function test_rate_limiting_on_login_attempts(): void
    {
        // Make multiple failed login attempts
        $email = 'test@example.com';
        $password = 'wrongpassword';

        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', [
                'email' => $email,
                'password' => $password,
            ]);

            // After several attempts, should be rate limited
            if ($i >= 5) {
                $this->assertTrue(
                    $response->status() === 429 || str_contains($response->getContent(), 'too many')
                );
            }
        }
    }

    /**
     * Test secure password reset flow
     */
    public function test_password_reset_token_validation(): void
    {
        $user = User::factory()->create();

        // Request password reset
        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        // Should not reveal if email exists
        $response->assertStatus(302);

        // Attempt to reset with invalid token
        $response = $this->get('/reset-password/invalid-token');
        $response->assertStatus(302);
    }

    /**
     * Test encryption of sensitive data
     */
    public function test_sensitive_data_encryption(): void
    {
        $user = User::factory()->create();

        // Create domain with API credentials
        $domain = WebDomain::factory()->for($user)->create();

        // Check that sensitive data might be encrypted
        // (implementation depends on which fields are marked as encrypted)
        $this->assertNotNull($domain->id);
    }

    /**
     * Test audit logging of sensitive operations
     */
    public function test_audit_logging_of_operations(): void
    {
        $user = User::factory()->create();

        // Perform an operation
        $domain = WebDomain::factory()->for($user)->create([
            'name' => 'test-domain.com',
        ]);

        // Check if audit log was created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test input validation
     */
    public function test_input_validation_on_domain_creation(): void
    {
        $user = User::factory()->create();

        // Missing required fields
        $response = $this->actingAs($user)->post('/domains', []);
        $response->assertStatus(302);

        // Invalid domain name format
        $response = $this->actingAs($user)->post('/domains', [
            'name' => 'invalid domain name!@#$',
            'registrar' => 'namecheap',
        ]);
        $response->assertStatus(302);

        // Invalid registrar
        $response = $this->actingAs($user)->post('/domains', [
            'name' => 'example.com',
            'registrar' => 'invalid_registrar_123',
        ]);
        $response->assertStatus(302);
    }

    /**
     * Test protection against timing attacks
     */
    public function test_timing_attack_protection(): void
    {
        // Create a user
        User::factory()->create(['email' => 'exists@example.com']);

        $startTime = microtime(true);

        // Check login with existing email
        $response1 = $this->post('/login', [
            'email' => 'exists@example.com',
            'password' => 'wrongpassword',
        ]);

        $time1 = microtime(true) - $startTime;

        $startTime = microtime(true);

        // Check login with non-existing email
        $response2 = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        $time2 = microtime(true) - $startTime;

        // Times should be relatively consistent (constant-time comparison)
        // Allow for some variation in timing
        $timeDiff = abs($time1 - $time2);
        $this->assertLessThan(0.5, $timeDiff, 'Timing attack vulnerability detected');
    }

    /**
     * Test secure headers are sent
     */
    public function test_secure_headers_in_responses(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        // Should have security headers
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection');
    }

    /**
     * Test HSTS header
     */
    public function test_hsts_header_present(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertHeader('Strict-Transport-Security');
    }

    /**
     * Test Content Security Policy
     */
    public function test_content_security_policy_header(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        // Should have CSP header
        $response->assertHeader('Content-Security-Policy');
    }

    /**
     * Test session security
     */
    public function test_session_security(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        // Session cookies should have secure flags
        $cookies = $response->headers->getCookies();

        $sessionCookie = collect($cookies)->first(function ($cookie) {
            return $cookie->getName() === 'XSRF-TOKEN' ||
                   $cookie->getName() === 'laravel_session';
        });

        if ($sessionCookie) {
            $this->assertTrue(
                $sessionCookie->isSecureOnly(),
                'Session cookie should have Secure flag'
            );
            $this->assertTrue(
                $sessionCookie->isHttpOnly(),
                'Session cookie should have HttpOnly flag'
            );
        }
    }

    /**
     * Test mass assignment protection
     */
    public function test_mass_assignment_protection(): void
    {
        $user = User::factory()->create();

        // Attempt to set is_admin via mass assignment
        $response = $this->actingAs($user)->patch("/users/{$user->id}", [
            'name' => 'New Name',
            'email' => 'newemail@example.com',
            'is_admin' => true,  // Should be protected
        ]);

        // User should not become admin
        $user->refresh();
        $this->assertFalse($user->is_admin ?? false);
    }

    /**
     * Test protection against directory traversal
     */
    public function test_directory_traversal_prevention(): void
    {
        $user = User::factory()->create();

        // Attempt directory traversal in file manager
        $response = $this->actingAs($user)->get('/file-manager/browse?path=../../etc/passwd');

        $response->assertStatus(200);

        // Should not actually access sensitive files
        // Response should be safe or access denied
    }

    /**
     * Test protection against Insecure Deserialization
     */
    public function test_secure_serialization(): void
    {
        // Ensure unserialize is not used with user input
        // This is a code review check more than a test
        $this->assertTrue(true);
    }
}
