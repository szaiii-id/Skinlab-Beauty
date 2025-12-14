<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class ReviewReplied extends Notification implements ShouldQueue
{
    use Queueable;
    protected $review;

    public function __construct($review)
    {
        $this->review = $review;
    }

    public function via($notifiable)
    {
        // NO EMAIL. Interaction update.
        return ['database', FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        return FcmNotification::create()
            ->setTitle('Admin Replied to You 💬')
            ->setBody('Admin: "' . \Illuminate\Support\Str::limit($this->review->admin_reply, 50) . '"')
            ->setData(['type' => 'review_reply', 'product_slug' => $this->review->product->slug, 'click_action' => 'FLUTTER_NOTIFICATION_CLICK']);
    }

    public function toArray($notifiable)
    {
        return ['title' => 'Review Reply', 'message' => 'Admin replied to your review.', 'product_id' => $this->review->product_id];
    }
}