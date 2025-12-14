<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBanRequestCreated extends Notification implements ShouldQueue
{
    use Queueable;
    protected $requestingAdmin;
    protected $count;

    public function __construct($requestingAdmin, $count)
    {
        $this->requestingAdmin = $requestingAdmin;
        $this->count = $count;
    }

    public function via($notifiable)
    {
        // Email is important for Admins who are not always on the dashboard
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Action Required: New Ban Request')
            ->greeting('Hello Super Admin,')
            ->line('Admin ' . $this->requestingAdmin->name . ' has requested to ban ' . $this->count . ' users.')
            ->action('Review Requests', url('/admin/ban-requests'));
    }

    public function toArray($notifiable)
    {
        return ['title' => 'New Ban Request', 'message' => $this->count . ' users pending ban review.'];
    }
}