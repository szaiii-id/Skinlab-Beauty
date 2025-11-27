<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'order_id'   => 'required|exists:orders,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'nullable|string|max:500',
        ]);

        $user = Auth::user();

        // 1. Validasi Keamanan: Pastikan Order ini milik User yang login
        $order = Order::where('id', $request->order_id)
                      ->where('user_id', $user->id)
                      ->where('order_status', 'completed') // Hanya order selesai
                      ->firstOrFail();

        // 2. Cek Duplikasi: Apakah sudah pernah review barang ini di order ini?
        $exists = Review::where('user_id', $user->id)
                        ->where('order_id', $request->order_id)
                        ->where('product_id', $request->product_id)
                        ->exists();

        if ($exists) {
            return back()->withErrors(['message' => 'Anda sudah mengulas produk ini.']);
        }

        // 3. Simpan Review
        Review::create([
            'user_id'    => $user->id,
            'product_id' => $request->product_id,
            'order_id'   => $request->order_id,
            'rating'     => $request->rating,
            'comment'    => $request->comment
        ]);

        // --- TODO NANTI: LOGIC TAMBAH POIN DISINI ---
        // (Nanti kita akan panggil PointService di sini setelah fitur rewards jadi)
        
        return back()->with('success', 'Terima kasih atas ulasan Anda!');
    }
}