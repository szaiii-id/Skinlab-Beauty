<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
// GANTI INI: Pakai Custom Channel Anda
use App\Channels\FcmChannel;

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
        // NO EMAIL. Cukup notifikasi interaksi via Push & Lonceng
        return ['database', FcmChannel::class];
    }

    /**
     * FIX: Return Array Sederhana untuk Custom Channel
     */
    public function toFcm($notifiable)
    {
        return [
            'title' => 'Admin Replied to You 💬',
            'body'  => 'Admin: "' . Str::limit($this->review->admin_reply, 50) . '"',
            'link'  => url('/product/' . $this->review->product->slug) // Link ke produk
        ];
    }

    public function toArray($notifiable)
    {
        return [
            'title'      => 'Review Reply',
            'message'    => 'Admin replied to your review: "' . Str::limit($this->review->admin_reply, 30) . '"',
            'product_id' => $this->review->product_id,
            'link'       => '/product/' . $this->review->product->slug,
            'type'       => 'interaction'
        ];
    }
}