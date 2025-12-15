<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
// Import Custom Channel
use App\Channels\FcmChannel;

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
        // Kirim ke Database (Lonceng), Email, DAN Push Notif (HP/Web)
        return ['database', 'mail', FcmChannel::class];
    }

    /**
     * PUSH NOTIFICATION (Untuk Super Admin)
     */
    public function toFcm($notifiable)
    {
        return [
            'title' => 'New Ban Request 🛡️',
            'body'  => "Admin {$this->requestingAdmin->name} requested to ban {$this->count} users.",
            'link'  => url('/admin/ban-requests')
        ];
    }

    /**
     * EMAIL
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Action Required: New Ban Request')
            ->greeting('Hello Super Admin,')
            ->line('Admin ' . $this->requestingAdmin->name . ' has requested to ban ' . $this->count . ' users.')
            ->line('Please review the evidence and approve or reject this request.')
            ->action('Review Requests', url('/admin/ban-requests'));
    }

    /**
     * DATABASE (Lonceng Dashboard Admin)
     */
    public function toArray($notifiable)
    {
        return [
            'title'   => 'New Ban Request',
            'message' => $this->count . ' users pending ban review.',
            'link'    => '/admin/ban-requests', // Tambahkan link agar bisa diklik
            'type'    => 'alert' // Tipe notifikasi untuk icon (opsional)
        ];
    }
}