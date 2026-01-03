<?php

namespace App\Console\Commands;

use App\Notifications\SystemAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:test-email {email? : Email address to send test to}';

    /**
     * The console command description.
     */
    protected $description = 'Send a test email to verify mail configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?? 'test@example.com';

        $this->info("Sending test email to: {$email}");

        try {
            Notification::route('mail', $email)
                ->notify(new SystemAlertNotification(
                    subject: 'Test Email',
                    message: 'This is a test email from SuperCP to verify your email configuration is working properly.',
                    details: [
                        'Timestamp' => now()->toDateTimeString(),
                        'Environment' => config('app.env'),
                        'Mail Driver' => config('mail.default'),
                    ],
                    severity: 'info'
                ));

            $this->info('✅ Test email sent successfully!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send test email: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
