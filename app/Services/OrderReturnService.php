<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;

class OrderReturnService
{
    /**
     * Handle logic pembuatan pengajuan retur
     */
    public function createReturnRequest(User $user, int $orderId, array $data, UploadedFile $evidenceFile): void
    {
        // 1. Cari Order milik User
        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            throw new Exception("Order not found.");
        }

        // 2. Validasi Status Order
        // Hanya boleh retur jika status 'completed' (barang sudah diterima)
        if ($order->order_status !== 'completed') {
            throw new Exception("Returns can only be requested for completed orders.");
        }

        // 3. Cek Duplikasi Retur
        // Mencegah spam request untuk order yang sama
        if (OrderReturn::where('order_id', $order->id)->exists()) {
            throw new Exception("You have already requested a return for this order.");
        }

        // 4. Proses Transaksi Database
        DB::transaction(function () use ($order, $user, $data, $evidenceFile) {
            
            // A. Upload File ke Storage (Public/S3)
            // Disimpan di folder 'returns/{user_id}' agar rapi
            $path = $evidenceFile->store("returns/{$user->id}", 'public');

            // B. Buat Record Retur
            OrderReturn::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'reason' => $data['reason'],
                'description' => $data['description'],
                'solution' => $data['solution'],
                'evidence_file' => $path,
                'status' => 'pending'
            ]);

            // C. Update Status Order Utama
            // Agar user tahu order ini sedang dalam masalah/retur
            $order->update(['order_status' => 'return_requested']);
        });
    }
}