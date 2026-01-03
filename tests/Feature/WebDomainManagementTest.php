<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebDomainManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_web_domains(): void
    {
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
        $user = User::factory()->create();

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
    }

    public function test_user_can_update_web_domain(): void
    {
        $user = User::factory()->create();
        $domain = WebDomain::create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'root_path' => '/home/super/web/example.com/public',
            'php_version' => '8.4',
        ]);

        $response = $this->actingAs($user)->patch("/web-domains/{$domain->id}", [
            'php_version' => '8.3',
            'is_active' => true,
        ]);

        $response->assertRedirect('/web-domains');
        $this->assertDatabaseHas('web_domains', [
            'id' => $domain->id,
            'php_version' => '8.3',
        ]);
    }

    public function test_user_can_toggle_ssl(): void
    {
        $user = User::factory()->create();
        $domain = WebDomain::create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'root_path' => '/home/super/web/example.com/public',
            'php_version' => '8.4',
            'has_ssl' => false,
        ]);

        $response = $this->actingAs($user)->post("/web-domains/{$domain->id}/toggle-ssl");

        $response->assertRedirect();
        $this->assertTrue($domain->fresh()->has_ssl);
    }

    public function test_user_can_delete_web_domain(): void
    {
        $user = User::factory()->create();
        $domain = WebDomain::create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'root_path' => '/home/super/web/example.com/public',
            'php_version' => '8.4',
        ]);

        $response = $this->actingAs($user)->delete("/web-domains/{$domain->id}");

        $response->assertRedirect('/web-domains');
        $this->assertDatabaseMissing('web_domains', [
            'id' => $domain->id,
        ]);
    }
}
