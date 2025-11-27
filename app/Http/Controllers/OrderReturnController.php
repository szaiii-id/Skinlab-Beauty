<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderReturnController extends Controller
{
    public function store(Request $request, $orderId)
    {
        $request->validate([
            'reason' => 'required|string',
            'description' => 'required|string|min:10',
            'solution' => 'required|in:refund,exchange',
            'evidence' => 'required|file|mimes:jpg,jpeg,png,mp4,mov,avi|max:51200', 
        ], [
            'evidence.required' => 'Bukti foto/video wajib diupload.',
            'evidence.mimes' => 'Format harus JPG, PNG, atau Video (MP4, MOV).',
            'evidence.max' => 'Ukuran file maksimal 50MB.',
            'description.min' => 'Jelaskan masalah minimal 10 karakter.'
        ]);

        $user = Auth::user();
        $order = Order::where('id', $orderId)->where('user_id', $user->id)->firstOrFail();

        if ($order->order_status !== 'completed') {
            return back()->with('error', 'Retur hanya bisa dilakukan untuk pesanan yang sudah diterima (Selesai).');
        }

        if (OrderReturn::where('order_id', $order->id)->exists()) {
             return back()->with('error', 'Anda sudah mengajukan retur untuk pesanan ini.');
        }

        DB::transaction(function () use ($request, $order, $user) {
            
            $path = $request->file('evidence')->store('returns', 'public');

            OrderReturn::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'reason' => $request->reason,
                'description' => $request->description,
                'solution' => $request->solution,
                'evidence_file' => $path,
                'status' => 'pending'
            ]);

            $order->update(['order_status' => 'return_requested']);
        });

        return back()->with('success', 'Pengajuan retur dikirim. Mohon tunggu verifikasi Admin.');
    }
}