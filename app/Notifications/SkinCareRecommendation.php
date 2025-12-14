<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class SkinCareRecommendation extends Notification implements ShouldQueue
{
    use Queueable;
    protected $product;
    protected $message;

    public function __construct($product, $message)
    {
        $this->product = $product;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        // NO EMAIL. Marketing works better via Push.
        return ['database', FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        return FcmNotification::create()
            ->setTitle('Skin Recommendation 💖')
            ->setBody('Based on your skin profile, we recommend: ' . $this->product->name)
            ->setImage($this->product->image_url)
            ->setData(['type' => 'recommendation', 'product_slug' => $this->product->slug, 'click_action' => 'FLUTTER_NOTIFICATION_CLICK']);
    }

    public function toArray($notifiable)
    {
        return ['title' => 'New Recommendation', 'message' => 'Check out our recommendation for your skin.', 'product_id' => $this->product->id];
    }
}