<?php

namespace Tests\Unit;

use App\Models\EmailAccount;
use App\Models\User;
use App\Services\EmailService;
use App\Services\RustDaemonClient;
use App\Services\SystemSyncService;
use Mockery\MockInterface;
use Tests\TestCase;

class EmailServiceTest extends TestCase
{
    private EmailService $emailService;

    private MockInterface $daemonMock;

    private MockInterface $syncServiceMock;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var RustDaemonClient&MockInterface */
        $this->daemonMock = $this->mock(RustDaemonClient::class);
        $this->daemonMock->shouldReceive('call')->andReturn(['success' => true]);

        /** @var SystemSyncService&MockInterface */
        $this->syncServiceMock = $this->mock(SystemSyncService::class);
        $this->syncServiceMock->shouldReceive('syncEmailAccount')->andReturn(true);
        $this->syncServiceMock->shouldReceive('deleteEmailAccount')->andReturn(true);

        $this->emailService = new EmailService($this->daemonMock, $this->syncServiceMock);
    }

    public function test_can_create_email_account(): void
    {
        $user = User::factory()->create();

        $this->daemonMock
            ->shouldReceive('call')
            ->with('create_email_account', [
                'email' => 'test@example.com',
                'password' => \Mockery::any(),
                'quota_mb' => 1024,
            ])
            ->andReturn(['success' => true]);

        $account = $this->emailService->create($user, [
            'email' => 'test@example.com',
            'quota_mb' => 1024,
        ]);

        $this->assertInstanceOf(EmailAccount::class, $account);
        $this->assertEquals('test@example.com', $account->email);
        $this->assertEquals(1024, $account->quota_mb);
        $this->assertEquals('active', $account->status);
        $this->assertEquals($user->id, $account->user_id);
    }

    public function test_rejects_invalid_email(): void
    {
        $user = User::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid email format');

        $this->emailService->create($user, [
            'email' => 'invalid-email',
            'quota_mb' => 1024,
        ]);
    }

    public function test_rejects_duplicate_email(): void
    {
        $user = User::factory()->create();
        EmailAccount::factory()->create(['email' => 'existing@example.com']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Email account already exists');

        $this->emailService->create($user, [
            'email' => 'existing@example.com',
            'quota_mb' => 1024,
        ]);
    }

    public function test_can_update_email_account(): void
    {
        $account = EmailAccount::factory()->create();

        $this->daemonMock
            ->shouldReceive('call')
            ->with('update_email_account', [
                'email' => $account->email,
                'password' => \Mockery::any(),
                'quota_mb' => 2048,
            ])
            ->andReturn(['success' => true]);

        $updated = $this->emailService->update($account, [
            'password' => 'NewPassword123!',
            'quota_mb' => 2048,
        ]);

        $this->assertEquals(2048, $updated->quota_mb);
    }

    public function test_can_delete_email_account(): void
    {
        $account = EmailAccount::factory()->create();

        $this->daemonMock
            ->shouldReceive('call')
            ->with('delete_email_account', [
                'email' => $account->email,
            ])
            ->andReturn(['success' => true]);

        $deleted = $this->emailService->delete($account);

        $this->assertTrue($deleted);
        $this->assertNull(EmailAccount::find($account->id));
    }

    public function test_can_update_quota(): void
    {
        $account = EmailAccount::factory()->create(['quota_mb' => 1024]);

        $this->daemonMock
            ->shouldReceive('call')
            ->with('update_email_quota', [
                'email' => $account->email,
                'quota_mb' => 5120,
            ])
            ->andReturn(['success' => true]);

        $updated = $this->emailService->updateQuota($account, 5120);

        $this->assertEquals(5120, $updated->quota_mb);
    }
}
