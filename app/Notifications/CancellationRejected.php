<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // Wajib agar tidak bikin loading lama
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
// Import Custom Channel Anda
use App\Channels\FcmChannel;

class CancellationRejected extends Notification implements ShouldQueue
{
    use Queueable;
    protected $order;

    /**
     * Create a new notification instance.
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        // Kirim ke Database (Lonceng), Email, dan Push Notif (HP)
        return ['database', 'mail', FcmChannel::class];
    }

    /**
     * 1. PUSH NOTIFIKASI (FCM / HP)
     * Menggunakan format Array simpel sesuai Custom Channel Anda
     */
    public function toFcm($notifiable)
    {
        return [
            'title' => 'Cancellation Rejected ❌',
            'body'  => 'Your request to cancel Order #' . $this->order->order_number . ' has been rejected. The order will be processed.',
            'link'  => url('/user/orders/' . $this->order->id)
        ];
    }

    /**
     * 2. EMAIL NOTIFICATION
     * Memberikan penjelasan detail kenapa ditolak
     */
    public function toMail($notifiable)
    {
        // Ambil catatan admin dari relasi cancellation (jika ada)
        // Kita pakai null coalescing operator (??) biar gak error kalau null
        $reason = $this->order->cancellation->admin_note ?? 'Order is already in processing stage.';

        return (new MailMessage)
            ->error() // Warna Merah (Penting)
            ->subject('Update: Cancellation Request Rejected')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('We are writing to inform you that your request to cancel Order #' . $this->order->order_number . ' has been **REJECTED**.')
            ->line('**Reason:** ' . $reason)
            ->line('Your order status has been reverted to **Processing** and will be prepared for shipment shortly.')
            ->action('View Order Details', url('/user/orders/' . $this->order->id))
            ->line('If you have any questions, please contact our support.');
    }

    /**
     * 3. DATABASE NOTIFICATION (Lonceng Web)
     */
    public function toArray($notifiable)
    {
        return [
            'title'    => 'Cancellation Rejected',
            'message'  => 'Your cancel request for Order #' . $this->order->order_number . ' was rejected.',
            'order_id' => $this->order->id,
            'link'     => '/user/orders/' . $this->order->id,
            'type'     => 'order_update' // Tipe icon/kategori
        ];
    }
}