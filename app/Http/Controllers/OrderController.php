<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');

        $orders = Order::where('user_id', Auth::id())
            ->with(['items', 'items.productVariant'])
            ->when($status !== 'all', function ($q) use ($status) {
                return $q->where('order_status', $status);
            })
            ->latest()
            ->get();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'currentStatus' => $status
        ]);
    }
}