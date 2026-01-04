<?php

namespace Tests\Unit;

use App\Models\FirewallRule;
use App\Services\FirewallService;
use App\Services\RustDaemonClient;
use Mockery\MockInterface;
use Tests\TestCase;

class FirewallServiceTest extends TestCase
{
    private FirewallService $firewallService;

    private MockInterface $daemonMock;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var RustDaemonClient&MockInterface */
        $this->daemonMock = $this->mock(RustDaemonClient::class);
        $this->firewallService = new FirewallService($this->daemonMock);
    }

    public function test_can_get_firewall_status(): void
    {
        $statusData = ['enabled' => true, 'rules_count' => 5];
        $this->daemonMock
            ->shouldReceive('call')
            ->with('get_firewall_status')
            ->andReturn($statusData);

        $status = $this->firewallService->getStatus();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('enabled', $status);
    }

    public function test_can_enable_firewall(): void
    {
        $this->daemonMock
            ->shouldReceive('call')
            ->with('toggle_firewall', ['enable' => true])
            ->andReturn('Firewall enabled');

        $result = $this->firewallService->enable();

        $this->assertTrue($result);
    }

    public function test_can_disable_firewall(): void
    {
        $this->daemonMock
            ->shouldReceive('call')
            ->with('toggle_firewall', ['enable' => false])
            ->andReturn('Firewall disabled');

        $result = $this->firewallService->disable();

        $this->assertTrue($result);
    }

    public function test_can_create_firewall_rule(): void
    {
        $this->daemonMock
            ->shouldReceive('call')
            ->with('apply_firewall_rule', \Mockery::any())
            ->andReturn('Rule applied');

        $rule = $this->firewallService->createRule([
            'name' => 'HTTP',
            'port' => 80,
            'protocol' => 'tcp',
            'action' => 'allow',
            'source' => '0.0.0.0/0',
        ]);

        $this->assertInstanceOf(FirewallRule::class, $rule);
        $this->assertEquals(80, $rule->port);
        $this->assertEquals('tcp', $rule->protocol);
        $this->assertDatabaseHas('firewall_rules', [
            'port' => 80,
            'protocol' => 'tcp',
            'action' => 'allow',
        ]);
    }

    public function test_rejects_invalid_port(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Port must be between');

        $this->firewallService->createRule([
            'port' => 99999,
            'protocol' => 'tcp',
            'action' => 'allow',
            'source' => 'any',
        ]);
    }

    public function test_rejects_invalid_protocol(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid protocol');

        $this->firewallService->createRule([
            'port' => 80,
            'protocol' => 'icmp',
            'action' => 'allow',
            'source' => 'any',
        ]);
    }

    public function test_rejects_invalid_action(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid action');

        $this->firewallService->createRule([
            'port' => 80,
            'protocol' => 'tcp',
            'action' => 'block',
            'source' => 'any',
        ]);
    }

    public function test_can_update_firewall_rule(): void
    {
        $rule = FirewallRule::factory()->create([
            'port' => 80,
            'protocol' => 'tcp',
            'action' => 'allow',
        ]);

        $this->daemonMock->shouldReceive('call')->andReturn('success');

        $updated = $this->firewallService->updateRule($rule, [
            'port' => 8080,
            'protocol' => 'tcp',
            'action' => 'allow',
            'source' => '192.168.1.0/24',
        ]);

        $this->assertEquals(8080, $updated->port);
        $this->assertDatabaseHas('firewall_rules', [
            'id' => $rule->id,
            'port' => 8080,
        ]);
    }

    public function test_can_delete_firewall_rule(): void
    {
        $rule = FirewallRule::factory()->create();

        $this->daemonMock
            ->shouldReceive('call')
            ->with('delete_firewall_rule', \Mockery::any())
            ->andReturn('Rule deleted');

        $result = $this->firewallService->deleteRule($rule);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('firewall_rules', ['id' => $rule->id]);
    }

    public function test_daemon_running(): void
    {
        $this->daemonMock
            ->shouldReceive('isRunning')
            ->andReturn(true);

        $result = $this->firewallService->isDaemonRunning();

        $this->assertTrue($result);
    }
}
