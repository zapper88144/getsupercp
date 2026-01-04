<?php

namespace App\Notifications\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public User $user, public string $reason) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        return (new MailMessage)
            ->subject('Your SuperCP Account has been Suspended')
            ->greeting("Hello {$this->user->name},")
            ->line('We are writing to inform you that your SuperCP account has been suspended.')
            ->line("Reason for suspension: {$this->reason}")
            ->line('While your account is suspended, you will not be able to log in or access any services.')
            ->line('If you believe this is a mistake, please contact our support team.')
            ->salutation('Best regards,')
            ->salutation('The SuperCP Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'user_suspended',
            'reason' => $this->reason,
            'message' => 'Your account has been suspended.',
        ];
    }
}
