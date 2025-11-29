<?php

namespace App\Services;

use App\Models\FcmToken;
use Google\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Kirim Notifikasi ke Satu User (Ke Semua Device-nya)
     * * @param int $userId ID User penerima
     * @param string $title Judul Notifikasi
     * @param string $body Isi Pesan
     * @param string $link Link tujuan saat diklik (default ke home)
     */
    public function sendToUser($userId, $title, $body, $link = '/')
    {
        // 1. Ambil semua token device milik user
        $tokens = FcmToken::where('user_id', $userId)->pluck('token')->toArray();

        if (empty($tokens)) return;

        // 2. Dapatkan Akses Token Google (Hanya sekali request)
        $accessToken = $this->getAccessToken();
        $projectId = $this->getProjectId();

        if (!$accessToken || !$projectId) {
            Log::error('FCM Service: Gagal mendapatkan Access Token atau Project ID.');
            return;
        }

        // 3. Kirim ke setiap device
        foreach ($tokens as $token) {
            $this->sendRequest($token, $title, $body, $link, $accessToken, $projectId);
        }
    }

    private function sendRequest($token, $title, $body, $link, $accessToken, $projectId)
    {
        try {
            $response = Http::withToken($accessToken)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'webpush' => [
                            'fcm_options' => [
                                'link' => url($link)
                            ]
                        ]
                    ]
                ]);

            // Hapus token jika sudah tidak valid (User uninstall/logout paksa)
            if ($response->failed()) {
                $err = $response->json();
                if (isset($err['error']['details'][0]['errorCode']) && $err['error']['details'][0]['errorCode'] === 'UNREGISTERED') {
                    FcmToken::where('token', $token)->delete();
                }
            }

        } catch (\Exception $e) {
            Log::error("FCM Send Error: " . $e->getMessage());
        }
    }

    private function getAccessToken()
    {
        $credentialsPath = storage_path('app/firebase_credentials.json');
        if (!file_exists($credentialsPath)) return null;

        try {
            $client = new Client();
            $client->setAuthConfig($credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            return $client->fetchAccessTokenWithAssertion()['access_token'];
        } catch (\Exception $e) {
            Log::error("FCM Auth Error: " . $e->getMessage());
            return null;
        }
    }

    private function getProjectId()
    {
        $credentialsPath = storage_path('app/firebase_credentials.json');
        if (!file_exists($credentialsPath)) return null;
        
        $json = json_decode(file_get_contents($credentialsPath), true);
        return $json['project_id'] ?? null;
    }
}