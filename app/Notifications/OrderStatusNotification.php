<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

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
        // NO EMAIL (To avoid spam)
        return ['database', FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        return FcmNotification::create()
            ->setTitle('Order Update 📦')
            ->setBody('Order #' . $this->order->order_number . ' is now: ' . ucfirst($this->order->order_status))
            ->setData(['type' => 'order_update', 'order_id' => (string)$this->order->id, 'click_action' => 'FLUTTER_NOTIFICATION_CLICK']);
    }

    public function toArray($notifiable)
    {
        return ['title' => 'Order Update', 'message' => 'Status changed to ' . $this->order->order_status, 'order_id' => $this->order->id];
    }
}