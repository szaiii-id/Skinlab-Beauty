<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
// GANTI INI: Pakai Custom Channel Anda
use App\Channels\FcmChannel;

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
        // NO EMAIL. Fokus ke Push Notification untuk marketing.
        return ['database', FcmChannel::class];
    }

    /**
     * FIX: Return Array Sederhana untuk Custom Channel
     */
    public function toFcm($notifiable)
    {
        return [
            'title' => 'Skin Recommendation 💖',
            'body'  => 'Based on your skin profile, we recommend: ' . $this->product->name,
            'link'  => url('/product/' . $this->product->slug) // Link langsung ke produk
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'title'      => 'New Recommendation',
            'message'    => 'Check out our recommendation for your skin: ' . $this->product->name,
            'product_id' => $this->product->id,
            'link'       => '/product/' . $this->product->slug,
            'type'       => 'recommendation'
        ];
    }
}