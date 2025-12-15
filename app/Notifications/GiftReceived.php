<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // Required for Queue
use Illuminate\Notifications\Notification;
use App\Channels\FcmChannel; 

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
        // Use your Custom Channel and Database
        return [FcmChannel::class, 'database'];
    }

    /**
     * Data for your Custom FcmChannel
     */
    public function toFcm($notifiable)
    {
        return [
            'title' => 'HOORAY! You Received a Gift 🎁',
            'body'  => 'Congratulations! You got: ' . $this->reward->name,
            'link'  => url('/user/rewards')
        ];
    }

    /**
     * Data for Database Notification
     */
    public function toArray($notifiable)
    {
        return [
            'title'   => 'New Gift',
            'message' => $this->reward->name,
            'link'    => '/user/rewards'
        ];
    }
}