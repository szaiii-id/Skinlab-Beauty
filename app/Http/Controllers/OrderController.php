<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use App\Models\OrderCancellation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
            ->paginate(10);

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'currentStatus' => $status
        ]);
    }

    /**
     * Handle User Cancel Request
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        $user = Auth::user();

        $order = Order::where('id', $id)
                      ->where('user_id', $user->id)
                      ->firstOrFail();

        if (!in_array($order->order_status, ['pending', 'paid'])) {
            return back()->with('error', 'Pesanan tidak dapat dibatalkan pada tahap ini.');
        }

        DB::transaction(function () use ($order, $request) {
            
            OrderCancellation::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'reason' => $request->reason,
                    'status' => 'pending' 
                ]
            );

            if ($order->order_status === 'pending') {
                $order->update(['order_status' => 'canceled']);
                
                $order->cancellation()->update(['status' => 'approved']);

                foreach ($order->items as $item) {
                    if ($item->productVariant) {
                        $item->productVariant->increment('stock', $item->quantity);
                    }
                }
            }
            
            elseif ($order->order_status === 'paid') {
                $order->update(['order_status' => 'cancellation_requested']);
                
            }
        });

        if ($order->refresh()->order_status === 'canceled') {
            return back()->with('success', 'Pesanan berhasil dibatalkan.');
        } else {
            return back()->with('success', 'Pengajuan pembatalan dikirim. Menunggu persetujuan Admin.');
        }
    }
}