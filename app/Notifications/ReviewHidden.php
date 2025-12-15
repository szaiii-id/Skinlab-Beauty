<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
// GANTI INI: Pakai Custom Channel Anda
use App\Channels\FcmChannel;

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
        // NO EMAIL (Sesuai request Anda, cukup Push Notif & Database sebagai peringatan)
        return ['database', FcmChannel::class];
    }

    /**
     * FIX: Return Array Sederhana untuk Custom Channel
     */
    public function toFcm($notifiable)
    {
        return [
            'title' => 'Review Hidden ⚠️',
            'body'  => 'Your review on "' . $this->review->product->name . '" was hidden due to community guidelines.',
            'link'  => url('/product/' . $this->review->product->slug) // Arahkan ke produk terkait
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'title'   => 'Review Hidden',
            'message' => 'Your review on "' . $this->review->product->name . '" was hidden.',
            'link'    => '/product/' . $this->review->product->slug,
            'type'    => 'violation'
        ];
    }
}