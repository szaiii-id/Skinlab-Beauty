<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Ambil 5 Order Terakhir (Ini tetap pakai Database Orders)
        $recentOrders = Order::where('user_id', $user->id)
            ->with(['items'])
            ->latest()
            ->take(5)
            ->get();

        // 2. Ambil Data Cart (DARI SESSION, BUKAN DATABASE)
        // Karena Anda tidak pakai tabel carts, kita ambil dari Session Laravel
        $cart = session('cart', []); 
        
        // Hitung total item
        $cartCount = is_array($cart) ? count($cart) : 0;

        // 3. Statistik Dummy (Bisa disesuaikan nanti)
        $stats = [
            'points' => 1250,
            'voucher_count' => 3,
            'skin_score' => 85,
            'routine_streak' => 12
        ];

        return Inertia::render('Dashboard', [
            'auth' => ['user' => $user],
            'recentOrders' => $recentOrders,
            
            // Kirim data cart dari session ke Vue
            'cart' => $cart,      
            'cartCount' => $cartCount,
            
            'stats' => $stats
        ]);
    }
}