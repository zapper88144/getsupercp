<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DnsZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DnsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_dns_zone(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/dns-zones', [
            'domain' => 'example.com',
        ]);

        $response->assertRedirect('/dns-zones');
        $this->assertDatabaseHas('dns_zones', [
            'domain' => 'example.com',
            'user_id' => $user->id,
        ]);

        // Check default records
        $zone = DnsZone::where('domain', 'example.com')->first();
        $this->assertCount(4, $zone->dnsRecords); // @ A, www A, @ NS, @ NS
    }

    public function test_user_can_add_dns_record(): void
    {
        $user = User::factory()->create();
        $zone = DnsZone::create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put("/dns-zones/{$zone->id}/records", [
            'records' => [
                [
                    'type' => 'A',
                    'name' => 'test',
                    'value' => '1.2.3.4',
                    'ttl' => 3600,
                ]
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('dns_records', [
            'dns_zone_id' => $zone->id,
            'name' => 'test',
            'value' => '1.2.3.4',
        ]);
    }

    public function test_user_can_delete_dns_zone(): void
    {
        $user = User::factory()->create();
        $zone = DnsZone::create([
            'user_id' => $user->id,
            'domain' => 'example.com',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->delete("/dns-zones/{$zone->id}");

        $response->assertRedirect('/dns-zones');
        $this->assertDatabaseMissing('dns_zones', [
            'id' => $zone->id,
        ]);
    }
}
