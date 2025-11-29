<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\CartService; // Reuse service yang sudah ada
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;
    protected CartService $cartService;

    public function __construct(
        DashboardService $dashboardService, 
        CartService $cartService
    ) {
        $this->dashboardService = $dashboardService;
        $this->cartService = $cartService;
    }

    public function index()
    {
        $user = Auth::user();

        // 1. DATA CART (Konsisten dengan CartController)
        // Kita pakai getCart() supaya kalau admin hapus produk/ubah harga, 
        // dashboard user juga otomatis update (Self-Healing).
        $cart = $this->cartService->getCart();
        $cartCount = count($cart);

        // 2. DATA DASHBOARD (Via Service)
        $recentOrders = $this->dashboardService->getRecentOrders($user->id);
        $stats = $this->dashboardService->getUserStats($user);

        return Inertia::render('Dashboard', [
            'auth' => ['user' => $user],
            
            // Data Orders
            'recentOrders' => $recentOrders,
            
            // Data Cart (Sekarang pakai array_values agar jadi Array di JS, bukan Object)
            'cart' => array_values($cart),      
            'cartCount' => $cartCount,
            
            // Statistik
            'stats' => $stats,
            
            // Config Midtrans
            'midtrans_client_key' => config('midtrans.client_key') 
        ]);
    }
}