<?php

namespace App\Notifications\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserUnsuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public User $user) {}

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
            ->subject('Your SuperCP Account has been Reinstated')
            ->greeting("Hello {$this->user->name},")
            ->line('We are pleased to inform you that your SuperCP account has been unsuspended.')
            ->line('You can now log in and access your account as usual.')
            ->action('Log In to SuperCP', url('/login'))
            ->line('Thank you for your patience.')
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
            'type' => 'user_unsuspended',
            'message' => 'Your account has been unsuspended.',
        ];
    }
}
