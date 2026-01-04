<?php

namespace Tests\Feature;

use App\Jobs\SyncDnsToCloudflare;
use App\Models\DnsZone;
use App\Models\User;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class CloudflareIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        config(['services.cloudflare.token' => 'fake-token']);

        // Mock the Rust daemon client
        $mockDaemon = Mockery::mock(RustDaemonClient::class);
        $mockDaemon->shouldReceive('call')->andReturn(['success' => true]);
        $this->app->instance(RustDaemonClient::class, $mockDaemon);
    }

    public function test_dns_zone_creation_can_trigger_cloudflare_sync(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user)->post(route('dns-zones.store'), [
            'domain' => 'example.com',
            'sync_cloudflare' => true,
        ]);

        $response->assertRedirect(route('dns-zones.index'));
        $this->assertDatabaseHas('dns_zones', ['domain' => 'example.com']);

        $zone = DnsZone::where('domain', 'example.com')->first();
        Queue::assertPushed(SyncDnsToCloudflare::class, function ($job) use ($zone) {
            return $job->dnsZone->id === $zone->id;
        });
    }

    public function test_dns_record_update_triggers_cloudflare_sync_if_zone_id_present(): void
    {
        Queue::fake();

        $zone = DnsZone::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'example.com',
            'cloudflare_zone_id' => 'fake-zone-id',
        ]);

        $response = $this->actingAs($this->user)->put(route('dns-zones.update-records', $zone), [
            'records' => [
                [
                    'type' => 'A',
                    'name' => '@',
                    'value' => '1.2.3.4',
                    'ttl' => 3600,
                ],
            ],
        ]);

        $response->assertRedirect();
        Queue::assertPushed(SyncDnsToCloudflare::class);
    }

    public function test_purge_cloudflare_cache(): void
    {
        Http::fake([
            'api.cloudflare.com/client/v4/zones/fake-zone-id/purge_cache' => Http::response(['success' => true], 200),
        ]);

        $zone = DnsZone::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'example.com',
            'cloudflare_zone_id' => 'fake-zone-id',
        ]);

        $response = $this->actingAs($this->user)->post(route('dns-zones.purge-cloudflare-cache', $zone));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Cloudflare cache purged successfully.');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.cloudflare.com/client/v4/zones/fake-zone-id/purge_cache' &&
                   $request->method() === 'POST' &&
                   $request->data() === ['purge_everything' => true];
        });
    }

    public function test_toggle_cloudflare_proxy(): void
    {
        Queue::fake();

        $zone = DnsZone::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'example.com',
            'cloudflare_proxy_enabled' => false,
        ]);

        $response = $this->actingAs($this->user)->post(route('dns-zones.toggle-cloudflare-proxy', $zone));

        $response->assertRedirect();
        $this->assertTrue($zone->fresh()->cloudflare_proxy_enabled);
        Queue::assertPushed(SyncDnsToCloudflare::class);
    }
}
