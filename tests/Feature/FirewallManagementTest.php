<?php

namespace Tests\Feature;

use App\Models\FirewallRule;
use App\Models\User;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FirewallManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(RustDaemonClient::class, function ($mock) {
            $mock->shouldReceive('call')->andReturn(['status' => 'success']);
        });
    }

    public function test_user_can_list_firewall_rules(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        FirewallRule::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('firewall.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Firewall/Index')
            ->has('rules')
        );
    }

    public function test_user_can_create_firewall_rule(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->mock(RustDaemonClient::class, function ($mock) {
            $mock->shouldReceive('call')->andReturn(['success' => true]);
        });

        $response = $this->actingAs($user)->post(route('firewall.store'), [
            'name' => 'HTTP',
            'port' => 80,
            'protocol' => 'tcp',
            'action' => 'allow',
            'source' => '0.0.0.0/0',
        ]);

        $response->assertRedirect();
    }

    public function test_user_can_delete_firewall_rule(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $rule = FirewallRule::factory()->create();

        $this->mock(RustDaemonClient::class, function ($mock) {
            $mock->shouldReceive('call')->andReturn(['success' => true]);
        });

        $response = $this->actingAs($user)->delete(route('firewall.destroy', $rule));

        $response->assertRedirect();
    }

    public function test_user_can_toggle_firewall_rule(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $rule = FirewallRule::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->post(route('firewall.toggle', $rule));

        $response->assertRedirect();
        $this->assertDatabaseHas('firewall_rules', [
            'id' => $rule->id,
            'is_active' => false,
        ]);
    }
}
