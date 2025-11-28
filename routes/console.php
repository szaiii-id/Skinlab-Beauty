<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\SkincareRoutine;
use App\Models\FcmToken;
use Illuminate\Support\Facades\Http;
use Google\Client;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::call(function () {
    // Cek jam server sekarang (Pastikan APP_TIMEZONE=UTC di .env)
    $nowUTC = now('UTC')->format('H:i:00'); // Pakai detik 00 biar pas

    // 1. Cari jadwal yang waktunya SAMA dengan jam UTC sekarang
    $routines = SkincareRoutine::where('reminder_time', $nowUTC)
        ->where('is_reminder_active', true)
        ->get();

    if ($routines->isEmpty()) return;

    // 2. Setup Firebase
    $client = new Client();
    $client->setAuthConfig(storage_path('app/firebase_credentials.json'));
    $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
    $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];
    
    $json = json_decode(file_get_contents(storage_path('app/firebase_credentials.json')), true);
    $projectId = $json['project_id'];

    foreach ($routines as $routine) {
        $isDone = $routine->currentMonthCompletion()
            ->whereJsonContains('completed_days', now()->day)
            ->exists();

        if (!$isDone) {
            $tokens = FcmToken::where('user_id', $routine->user_id)->pluck('token')->toArray();
            $productName = $routine->product ? $routine->product->name : $routine->custom_product_name;

            foreach ($tokens as $token) {
                try {
                    Http::withToken($accessToken)
                        ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                            'message' => [
                                'token' => $token,
                                'notification' => [
                                    'title' => 'Waktunya Skincare! ✨',
                                    'body' => "Yuk pakai {$productName} sekarang.",
                                ],
                                'webpush' => [
                                    'fcm_options' => ['link' => url('/my-routine')]
                                ]
                            ]
                        ]);
                } catch (\Exception $e) {}
            }
        }
    }
})->everyMinute();