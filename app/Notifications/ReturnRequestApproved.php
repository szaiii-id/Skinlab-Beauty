<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

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
        return ['database', 'mail', FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        return FcmNotification::create()
            ->setTitle('Return Approved ✅')
            ->setBody('Your return request for Order #' . $this->returnRequest->order->order_number . ' has been approved.')
            ->setData(['type' => 'return_approved', 'return_id' => (string)$this->returnRequest->id, 'click_action' => 'FLUTTER_NOTIFICATION_CLICK']);
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Action Required: Return Request Approved')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Good news! Your return request for Order #' . $this->returnRequest->order->order_number . ' has been APPROVED.')
            ->line('**Next Step:** Please ship the item(s) to our warehouse.')
            ->action('View Instructions', url('/user/returns/' . $this->returnRequest->id));
    }

    public function toArray($notifiable)
    {
        return ['title' => 'Return Approved', 'message' => 'Return for Order #' . $this->returnRequest->order->order_number . ' approved.', 'return_id' => $this->returnRequest->id];
    }
}