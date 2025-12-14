<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountRestored extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->success() // Green color indicating success
                    ->subject('Good News: Your Account Has Been Restored')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('The suspension on your account has been lifted.')
                    ->line('You can now log in and shop as usual.')
                    ->action('Login Now', url('/login'))
                    ->line('Thank you for your patience.');
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Account Restored',
            'message' => 'Your account is active again. Welcome back!',
            'type' => 'system_info'
        ];
    }
}