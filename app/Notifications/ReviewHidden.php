<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class ReviewHidden extends Notification implements ShouldQueue
{
    use Queueable;
    protected $review;

    public function __construct($review)
    {
        $this->review = $review;
    }

    public function via($notifiable)
    {
        // NO EMAIL unless you want to be very strict. Push is usually enough for a warning.
        return ['database', FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        return FcmNotification::create()
            ->setTitle('Review Hidden ⚠️')
            ->setBody('Your review on ' . $this->review->product->name . ' was hidden due to community guidelines.')
            ->setData(['type' => 'review_hidden', 'click_action' => 'FLUTTER_NOTIFICATION_CLICK']);
    }

    public function toArray($notifiable)
    {
        return ['title' => 'Review Hidden', 'message' => 'Your review was hidden.', 'type' => 'violation'];
    }
}