<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
// Import Custom Channel yang sudah sukses tadi
use App\Channels\FcmChannel;

class AccountBanned extends Notification implements ShouldQueue
{
    use Queueable;
    protected $reason;
    protected $description;

    public function __construct($reason, $description)
    {
        $this->reason = $reason;
        $this->description = $description;
    }

    public function via($notifiable)
    {
        // Karena user MASIH BISA LOGIN, kita kirim ke semua channel
        // Agar notif muncul di HP dan di Lonceng notifikasi web
        return ['mail', 'database', FcmChannel::class]; 
    }

    /**
     * 1. PUSH NOTIFIKASI KE HP (FCM)
     * Langsung muncul di layar HP user
     */
    public function toFcm($notifiable)
    {
        return [
            'title' => 'Account Restricted ⚠️',
            'body'  => 'Your account has been restricted. You cannot checkout at this time.',
            'link'  => url('/') // Arahkan ke home atau halaman support
        ];
    }

    /**
     * 2. NOTIFIKASI DATABASE (Lonceng Web)
     * Agar tersimpan di list notifikasi user
     */
    public function toArray($notifiable)
    {
        return [
            'title'   => 'Account Restricted',
            'message' => 'Your purchasing privileges have been suspended due to: ' . $this->reason,
            'type'    => 'account_ban',
            'link'    => '#' 
        ];
    }

    /**
     * 3. EMAIL
     * Penjelasan detail via email
     */
    public function toMail($notifiable)
    {
        // Merapikan tulisan reason (misal: "payment_issue" jadi "Payment Issue")
        $formattedReason = ucwords(str_replace(['_', '-'], ' ', $this->reason));

        return (new MailMessage)
            ->error() // Warna merah tanda bahaya
            ->subject('URGENT: Your Account Has Been Restricted')
            ->greeting('Dear ' . $notifiable->name . ',')
            ->line('Your account has been restricted due to a violation of our Terms of Service.')
            ->line('You can still log in, but you will not be able to checkout or make purchases.')
            ->line('**Reason:** ' . $formattedReason)
            ->line('**Description:** ' . $this->description)
            ->line('If you believe this is a mistake, please contact our support team.');
    }
}