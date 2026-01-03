<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\PasswordResetNotification;
use App\Notifications\SystemAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_notification_sends_email(): void
    {
        Mail::fake();
        Notification::fake();

        $user = User::factory()->create();
        $resetUrl = 'https://example.com/reset?token=abc123';

        $user->notify(new PasswordResetNotification($resetUrl));

        Notification::assertSentTo(
            [$user],
            PasswordResetNotification::class,
            function (PasswordResetNotification $notification) use ($resetUrl) {
                return $notification->resetUrl === $resetUrl;
            }
        );
    }

    public function test_password_reset_notification_stores_in_database(): void
    {
        $user = User::factory()->create();
        $resetUrl = 'https://example.com/reset?token=abc123';

        $user->notify(new PasswordResetNotification($resetUrl));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'notifiable_type' => User::class,
            'type' => PasswordResetNotification::class,
        ]);
    }

    public function test_system_alert_notification_sends_email(): void
    {
        Mail::fake();
        Notification::fake();

        $user = User::factory()->create();

        $user->notify(new SystemAlertNotification(
            subject: 'Database Size Alert',
            message: 'Database usage exceeds threshold',
            details: ['current_size' => '85%', 'threshold' => '80%'],
            severity: 'warning'
        ));

        Notification::assertSentTo(
            [$user],
            SystemAlertNotification::class,
            function (SystemAlertNotification $notification) {
                return $notification->severity === 'warning' &&
                       $notification->subject === 'Database Size Alert';
            }
        );
    }

    public function test_system_alert_stores_severity_in_database(): void
    {
        $user = User::factory()->create();

        $user->notify(new SystemAlertNotification(
            subject: 'Server Error',
            message: 'Critical service failure detected',
            details: ['service' => 'php-fpm', 'status' => 'down'],
            severity: 'error'
        ));

        $notification = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->first();

        $this->assertNotNull($notification);
        $data = json_decode($notification->data, true);
        $this->assertEquals('error', $data['severity']);
        $this->assertEquals('Server Error', $data['subject']);
    }

    public function test_multiple_users_receive_system_alert(): void
    {
        $users = User::factory(3)->create();

        foreach ($users as $user) {
            $user->notify(new SystemAlertNotification(
                subject: 'Maintenance Window',
                message: 'System maintenance scheduled for tonight',
                details: ['window' => '2:00 AM - 4:00 AM'],
                severity: 'info'
            ));
        }

        $this->assertDatabaseCount('notifications', 3);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'type' => SystemAlertNotification::class,
        ]);
    }

    public function test_notification_can_be_marked_as_read(): void
    {
        $user = User::factory()->create();

        $user->notify(new SystemAlertNotification(
            subject: 'Test Alert',
            message: 'Test message',
            details: [],
            severity: 'info'
        ));

        $notification = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->first();

        $this->assertNull($notification->read_at);

        DB::table('notifications')
            ->where('id', $notification->id)
            ->update(['read_at' => now()]);

        $readNotification = DB::table('notifications')
            ->where('id', $notification->id)
            ->first();

        $this->assertNotNull($readNotification->read_at);
    }
}
