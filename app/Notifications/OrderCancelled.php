<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class OrderCancelled extends Notification implements ShouldQueue
{
    use Queueable;
    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        // Email is crucial here (Money/Refund issue)
        return ['database', 'mail', FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        return FcmNotification::create()
            ->setTitle('Order Cancelled 🛑')
            ->setBody('Order #' . $this->order->order_number . ' has been cancelled by Admin.')
            ->setData(['type' => 'order_cancelled', 'order_id' => (string)$this->order->id, 'click_action' => 'FLUTTER_NOTIFICATION_CLICK']);
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->error()
            ->subject('IMPORTANT: Order #' . $this->order->order_number . ' Cancelled')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We regret to inform you that your order #' . $this->order->order_number . ' has been CANCELLED.')
            ->line('**Note:** ' . ($this->order->notes ?? 'No specific reason provided.'))
            ->line('If you have paid, the refund process will start shortly.')
            ->action('View Order', url('/user/orders/' . $this->order->id));
    }

    public function toArray($notifiable)
    {
        return ['title' => 'Order Cancelled', 'message' => 'Order #' . $this->order->order_number . ' was cancelled.', 'order_id' => $this->order->id];
    }
}