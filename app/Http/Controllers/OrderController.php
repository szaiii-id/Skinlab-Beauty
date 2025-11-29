<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

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
     * Cancel Order Action
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        try {
            // Call Service to handle cancellation & stock restoration
            $result = $this->orderService->cancelOrder(Auth::user(), (int) $id, $request->reason);

            return back()->with('toast_success', $result['message']);

        } catch (\Exception $e) {
            return back()->with('toast_error', $e->getMessage());
        }
    }
}