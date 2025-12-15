<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Channels\FcmChannel; // Import Custom Channel

class AccountRestored extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via($notifiable)
    {
        // Kirim lewat Email, Database, DAN Push Notif (FCM)
        return ['mail', 'database', FcmChannel::class];
    }

    /**
     * PUSH NOTIFICATION (FCM)
     */
    public function toFcm($notifiable)
    {
        return [
            'title' => 'Welcome Back! 👋',
            'body'  => 'Your account has been restored. Happy shopping!',
            'link'  => url('/') 
        ];
    }

    /**
     * EMAIL
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->success() // Warna Hijau (Sukses)
                    ->subject('Good News: Your Account Has Been Restored')
                    ->greeting('Hello ' . $notifiable->name . ',')
                    ->line('The suspension on your account has been lifted.')
                    ->line('You can now log in and shop as usual.')
                    ->action('Login Now', url('/login'))
                    ->line('Thank you for your patience.');
    }

    /**
     * DATABASE (Lonceng di Web)
     */
    public function toArray($notifiable)
    {
        return [
            'title'   => 'Account Restored',
            'message' => 'Your account is active again. Welcome back!',
            'type'    => 'system_info',
            'link'    => '/'
        ];
    }
}