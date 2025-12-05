<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GiftReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $rewardName;
    protected $code;

    public function __construct($rewardName, $code)
    {
        $this->rewardName = $rewardName;
        $this->code = $code;
    }

    // Tentukan channel pengiriman
    public function via($notifiable)
    {
        return ['database']; // Simpan ke tabel 'notifications'
    }

    // Format data yang disimpan ke kolom 'data' di database (JSON)
    public function toArray($notifiable)
    {
        return [
            'title' => '🎁 You got a Gift!',
            'message' => "Admin sent you a special voucher: {$this->rewardName}.",
            'code' => $this->code,
            'action_url' => '/rewards', // Link saat diklik
            'type' => 'gift',
            'icon' => 'Gift', // Penanda icon untuk frontend
            'created_at' => now() // Timestamp
        ];
    }
}