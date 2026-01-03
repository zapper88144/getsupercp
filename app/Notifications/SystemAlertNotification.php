<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $subject,
        public string $message,
        public array $details = [],
        public string $severity = 'warning' // warning, error, info
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("[SuperCP {$this->severity}] {$this->subject}")
            ->greeting('Alert!')
            ->line($this->message);

        if (! empty($this->details)) {
            $mail->line('Details:');
            foreach ($this->details as $key => $value) {
                $mail->line("• {$key}: {$value}");
            }
        }

        return $mail
            ->action('View Dashboard', route('dashboard'))
            ->salutation('Best regards,')
            ->salutation('The SuperCP System');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'system_alert',
            'subject' => $this->subject,
            'message' => $this->message,
            'severity' => $this->severity,
            'details' => $this->details,
        ];
    }
}
