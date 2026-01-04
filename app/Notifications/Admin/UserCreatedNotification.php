<?php

namespace App\Notifications\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification implements ShouldQueue
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
            ->subject('Welcome to SuperCP')
            ->greeting("Hello {$this->user->name}!")
            ->line('An account has been created for you on SuperCP by an administrator.')
            ->line("Your account email is: {$this->user->email}")
            ->line('You can now log in to your account using the link below.')
            ->action('Log In to SuperCP', url('/login'))
            ->line('If you did not expect this, please contact our support team.')
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
            'type' => 'user_created',
            'user_id' => $this->user->id,
            'message' => 'Your account has been created by an administrator.',
        ];
    }
}
