<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
// GANTI INI: Pakai Custom Channel Anda
use App\Channels\FcmChannel;

class ReturnRequestApproved extends Notification implements ShouldQueue
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
            'title' => 'Return Approved ✅',
            'body'  => 'Your return request for Order #' . $this->returnRequest->order->order_number . ' has been approved.',
            'link'  => url('/user/returns/' . $this->returnRequest->id)
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->success() // Warna hijau (berita bagus)
            ->subject('Action Required: Return Request Approved')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Good news! Your return request for Order #' . $this->returnRequest->order->order_number . ' has been APPROVED.')
            ->line('**Next Step:** Please ship the item(s) to our warehouse within 3 days.')
            ->action('View Instructions', url('/user/returns/' . $this->returnRequest->id));
    }

    public function toArray($notifiable)
    {
        return [
            'title'     => 'Return Approved',
            'message'   => 'Return for Order #' . $this->returnRequest->order->order_number . ' approved.',
            'return_id' => $this->returnRequest->id,
            'link'      => '/user/returns/' . $this->returnRequest->id,
            'type'      => 'return_approved'
        ];
    }
}