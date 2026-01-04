<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebDomainManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_web_domains(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        WebDomain::create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'root_path' => '/home/super/web/example.com/public',
            'php_version' => '8.4',
        ]);

        $response = $this->actingAs($user)->get('/web-domains');

        $response->assertStatus(200);
        $response->assertSee('example.com');
    }

    public function test_user_can_create_web_domain(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->mock(RustDaemonClient::class, function ($mock) {
            $mock->shouldReceive('call')->andReturn(['result' => 'success']);
        });

        $response = $this->actingAs($user)->post('/web-domains', [
            'domain' => 'newdomain.com',
            'php_version' => '8.4',
        ]);

        $response->assertRedirect('/web-domains');
        $this->assertDatabaseHas('web_domains', [
            'domain' => 'newdomain.com',
            'user_id' => $user->id,
            'php_version' => '8.4',
        ]);

        // Verify DNS zone was created automatically
        $this->assertDatabaseHas('dns_zones', [
            'domain' => 'newdomain.com',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('dns_records', [
            'type' => 'A',
            'name' => '@',
            'value' => config('dns.default_ip', '127.0.0.1'),
        ]);
    }

    public function test_user_can_update_web_domain(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $domain = WebDomain::create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'root_path' => '/home/super/web/example.com/public',
            'php_version' => '8.4',
        ]);

        $this->mock(RustDaemonClient::class, function ($mock) {
            $mock->shouldReceive('call')->andReturn(['result' => 'success']);
        });

        $response = $this->actingAs($user)->patch("/web-domains/{$domain->id}", [
            'php_version' => '8.4',
            'is_active' => true,
        ]);

        $response->assertRedirect('/web-domains');
        $this->assertDatabaseHas('web_domains', [
            'id' => $domain->id,
            'php_version' => '8.4',
        ]);
    }

    public function test_user_can_toggle_ssl(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $domain = WebDomain::create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'root_path' => '/home/super/web/example.com/public',
            'php_version' => '8.4',
            'has_ssl' => false,
        ]);

        $this->mock(RustDaemonClient::class, function ($mock) {
            $mock->shouldReceive('call')->andReturn(['result' => 'success']);
        });

        $response = $this->actingAs($user)->post("/web-domains/{$domain->id}/toggle-ssl");

        $response->assertRedirect();
        $this->assertTrue($domain->fresh()->has_ssl);
    }

    public function test_user_can_delete_web_domain(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $domain = WebDomain::create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'root_path' => '/home/super/web/example.com/public',
            'php_version' => '8.4',
        ]);

        $this->mock(RustDaemonClient::class, function ($mock) {
            $mock->shouldReceive('call')->andReturn(['result' => 'success']);
        });

        $response = $this->actingAs($user)->delete("/web-domains/{$domain->id}");

        $response->assertRedirect('/web-domains');
        $this->assertDatabaseMissing('web_domains', [
            'id' => $domain->id,
        ]);
    }
}
