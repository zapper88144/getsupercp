<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_access_services_index(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('services.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Services/Index'));
    }

    public function test_user_can_get_services_status(): void
    {
        $mockStatus = [
            'nginx' => 'running',
            'php8.4-fpm' => 'running',
            'mysql' => 'running',
            'redis-server' => 'stopped',
            'daemon' => 'running'
        ];

        $this->mock(RustDaemonClient::class, function ($mock) use ($mockStatus) {
            $mock->shouldReceive('call')
                ->with('get_status')
                ->once()
                ->andReturn(['result' => $mockStatus]);
        });

        $response = $this->actingAs($this->user)
            ->get(route('services.status'));

        $response->assertStatus(200);
        $response->assertJson($mockStatus);
    }

    public function test_user_can_restart_service(): void
    {
        $this->mock(RustDaemonClient::class, function ($mock) {
            $mock->shouldReceive('call')
                ->with('restart_service', ['service' => 'nginx'])
                ->once()
                ->andReturn(['result' => 'Service nginx restarted successfully']);
        });

        $response = $this->actingAs($this->user)
            ->post(route('services.restart'), [
                'service' => 'nginx'
            ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Service nginx restarted successfully']);
    }

    public function test_user_cannot_restart_unauthorized_service(): void
    {
        $this->mock(RustDaemonClient::class, function ($mock) {
            $mock->shouldReceive('call')
                ->with('restart_service', ['service' => 'invalid-service'])
                ->once()
                ->andReturn(['error' => ['message' => 'Service not allowed']]);
        });

        $response = $this->actingAs($this->user)
            ->post(route('services.restart'), [
                'service' => 'invalid-service'
            ]);

        $response->assertStatus(500);
        $response->assertJson(['message' => 'Service not allowed']);
    }
}
