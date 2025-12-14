<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;

class FcmChannel
{
    protected $fcmService;

    // Inject FcmService yang sudah Anda miliki
    public function __construct(FcmService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        // 1. Cek apakah notifikasi punya method 'toFcm'
        if (!method_exists($notification, 'toFcm')) {
            return;
        }

        // 2. Ambil data dari method toFcm
        $data = $notification->toFcm($notifiable);

        if (empty($data)) {
            return;
        }

        // 3. Panggil fungsi sendToUser di FcmService Anda
        // $notifiable->id otomatis mengambil ID User penerima
        try {
            $this->fcmService->sendToUser(
                $notifiable->id, 
                $data['title'], 
                $data['body'], 
                $data['link'] ?? '/' 
            );
        } catch (\Exception $e) {
            Log::error("FcmChannel Error: " . $e->getMessage());
        }
    }
}