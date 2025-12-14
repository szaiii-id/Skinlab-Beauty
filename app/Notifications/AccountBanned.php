<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountBanned extends Notification implements ShouldQueue
{
    use Queueable;
    protected $reason;
    protected $description;

    public function __construct($reason, $description)
    {
        $this->reason = $reason;
        $this->description = $description;
    }

    public function via($notifiable)
    {
        // ONLY EMAIL because the user cannot access the app
        return ['mail']; 
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->error()
            ->subject('URGENT: Your Account Has Been Suspended')
            ->greeting('Dear ' . $notifiable->name . ',')
            ->line('Your account has been suspended due to a violation of our terms of service.')
            ->line('**Reason:** ' . $this->reason)
            ->line('**Description:** ' . $this->description)
            ->line('If you believe this is a mistake, please contact our support team.');
    }
}