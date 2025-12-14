<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

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
        return ['database', 'mail', FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        return FcmNotification::create()
            ->setTitle('Return Rejected ❌')
            ->setBody('Return request for Order #' . $this->returnRequest->order->order_number . ' was rejected.')
            ->setData(['type' => 'return_rejected', 'return_id' => (string)$this->returnRequest->id, 'click_action' => 'FLUTTER_NOTIFICATION_CLICK']);
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->error()
            ->subject('Update: Return Request Rejected')
            ->line('Your return request for Order #' . $this->returnRequest->order->order_number . ' has been REJECTED.')
            ->line('**Reason:** ' . $this->returnRequest->admin_note)
            ->action('View Order', url('/user/orders/' . $this->returnRequest->order_id));
    }

    public function toArray($notifiable)
    {
        return ['title' => 'Return Rejected', 'message' => 'Reason: ' . $this->returnRequest->admin_note, 'return_id' => $this->returnRequest->id];
    }
}