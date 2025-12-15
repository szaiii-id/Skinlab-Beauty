<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
// GANTI INI: Pakai Custom Channel Anda
use App\Channels\FcmChannel; 

class OrderStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;
    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        // NO EMAIL (Sesuai request Anda, biar gak nyepam email)
        // Kirim ke Database (Lonceng) & Push Notif (HP/Web)
        return ['database', FcmChannel::class];
    }

    /**
     * FIX: Return Array Sederhana untuk Custom Channel
     */
    public function toFcm($notifiable)
    {
        return [
            'title' => 'Order Update 📦',
            'body'  => 'Order #' . $this->order->order_number . ' is now: ' . ucfirst($this->order->order_status),
            'link'  => url('/user/orders/' . $this->order->id)
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'title'    => 'Order Update',
            'message'  => 'Status changed to ' . ucfirst($this->order->order_status),
            'order_id' => $this->order->id,
            'link'     => '/user/orders/' . $this->order->id, // Tambahkan link
            'type'     => 'order_update'
        ];
    }
}