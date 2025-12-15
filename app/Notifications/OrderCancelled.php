<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
// GANTI INI: Pakai Custom Channel Anda
use App\Channels\FcmChannel;

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
        // Kirim via Database (Lonceng), Email, dan Push Notif (HP/Web)
        return ['database', 'mail', FcmChannel::class];
    }

    /**
     * FIX: Return Array Sederhana untuk Custom Channel
     */
    public function toFcm($notifiable)
    {
        return [
            'title' => 'Order Cancelled 🛑',
            'body'  => 'Order #' . $this->order->order_number . ' has been cancelled by Admin.',
            'link'  => url('/user/orders/' . $this->order->id)
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->error()
            ->subject('IMPORTANT: Order #' . $this->order->order_number . ' Cancelled')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We regret to inform you that your order #' . $this->order->order_number . ' has been CANCELLED.')
            ->line('**Reason/Note:** ' . ($this->order->notes ?? 'No specific reason provided.'))
            ->line('If you have already paid, the refund process will start shortly.')
            ->action('View Order Details', url('/user/orders/' . $this->order->id));
    }

    public function toArray($notifiable)
    {
        return [
            'title'    => 'Order Cancelled',
            'message'  => 'Order #' . $this->order->order_number . ' was cancelled.',
            'order_id' => $this->order->id,
            'link'     => '/user/orders/' . $this->order->id, // Tambahkan link biar bisa diklik di lonceng
            'type'     => 'order_cancelled'
        ];
    }
}