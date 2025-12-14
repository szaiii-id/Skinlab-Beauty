<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class GiftReceived extends Notification implements ShouldQueue
{
    use Queueable;
    protected $reward;

    public function __construct($reward)
    {
        $this->reward = $reward;
    }

    public function via($notifiable)
    {
        // NO EMAIL. Push notification is enough to make them open the app.
        return ['database', FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        return FcmNotification::create()
            ->setTitle('You Received a Gift! 🎁')
            ->setBody('Congratulations! You got: ' . $this->reward->name)
            ->setData(['type' => 'gift_received', 'click_action' => 'FLUTTER_NOTIFICATION_CLICK']);
    }

    public function toArray($notifiable)
    {
        return ['title' => 'Gift Received', 'message' => 'You received ' . $this->reward->name, 'type' => 'gift'];
    }
}