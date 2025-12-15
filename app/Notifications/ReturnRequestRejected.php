<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
// GANTI INI: Pakai Custom Channel Anda
use App\Channels\FcmChannel;

class ReturnRequestRejected extends Notification implements ShouldQueue
{
    use Queueable;
    protected $returnRequest;

    public function __construct($returnRequest)
    {
        $this->returnRequest = $returnRequest;
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
            'title' => 'Return Rejected ❌',
            'body'  => 'Your return request for Order #' . $this->returnRequest->order->order_number . ' has been rejected.',
            'link'  => url('/user/orders/' . $this->returnRequest->order_id)
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->error() // Warna merah (Ditolak)
            ->subject('Update: Return Request Rejected')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We are sorry to inform you that your return request for Order #' . $this->returnRequest->order->order_number . ' has been REJECTED.')
            ->line('**Reason:** ' . $this->returnRequest->admin_note)
            ->line('Your order status has been reverted to Completed.')
            ->action('View Order', url('/user/orders/' . $this->returnRequest->order_id));
    }

    public function toArray($notifiable)
    {
        return [
            'title'     => 'Return Rejected',
            'message'   => 'Reason: ' . $this->returnRequest->admin_note,
            'return_id' => $this->returnRequest->id,
            'link'      => '/user/orders/' . $this->returnRequest->order_id,
            'type'      => 'return_rejected'
        ];
    }
}