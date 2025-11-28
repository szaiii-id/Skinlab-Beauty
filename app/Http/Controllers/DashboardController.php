<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\UserReward;
use App\Models\SkincareRoutine;
use App\Models\RoutineCompletion;
use App\Models\UserSkinProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // 1. DATA CART (DARI SESSION - Sesuai kode lama Anda)
        $cart = session('cart', []); 
        $cartCount = is_array($cart) ? count($cart) : 0;

        // 2. DATA ORDER (5 Transaksi Terakhir)
        $recentOrders = Order::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        // 3. STATISTIK REAL-TIME (Integrasi Fitur Baru)
        
        // A. Hitung Progress Routine Hari Ini
        $totalRoutines = SkincareRoutine::where('user_id', $user->id)->count();
        $completedToday = 0;
        
        if ($totalRoutines > 0) {
            $routineIds = SkincareRoutine::where('user_id', $user->id)->pluck('id');
            
            // Hitung berapa item yang sudah dicentang hari ini (tgl sekarang)
            $completedToday = RoutineCompletion::whereIn('skincare_routine_id', $routineIds)
                ->where('year_month', now()->format('Y-m'))
                ->whereJsonContains('completed_days', now()->day)
                ->count();
        }

        // B. Ambil Profil Kulit
        $skinProfile = UserSkinProfile::where('user_id', $user->id)->latest()->first();

        // C. Susun Objek Stats
        $stats = [
            // Poin diambil dari tabel user (hasil migrasi rewards)
            'points' => $user->current_points ?? 0,
            
            // Voucher yang dimiliki user dan belum dipakai
            'voucher_count' => UserReward::where('user_id', $user->id)
                ->where('is_used', false)
                ->count(),
            
            // Jenis kulit dari hasil analisis (jika ada)
            'skin_type' => $skinProfile ? $skinProfile->skin_type : null,
            
            // Persentase progress harian
            'routine_progress' => $totalRoutines > 0 ? round(($completedToday / $totalRoutines) * 100) : 0,
            
            // String hitungan (misal: "3/5")
            'routine_count' => "{$completedToday}/{$totalRoutines}"
        ];

        return Inertia::render('Dashboard', [
            'auth' => ['user' => $user],
            'recentOrders' => $recentOrders,
            'cart' => $cart,      
            'cartCount' => $cartCount,
            'stats' => $stats,
            // Penting untuk tombol "Bayar Sekarang" di dashboard
            'midtrans_client_key' => config('midtrans.client_key') 
        ]);
    }
}