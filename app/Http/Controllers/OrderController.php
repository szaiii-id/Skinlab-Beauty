<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display the user's order list.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $userId = Auth::id();

        // 1. QUERY ORDER UTAMA (Tampilan List Data)
        // Logika ini TETAP menampilkan semua history (biar user bisa lihat sejarahnya)
        $orders = Order::where('user_id', $userId)
            ->with(['items.productVariant.product', 'returnRequest']) 
            ->when($status !== 'all', function ($q) use ($status) {
                // Grouping Processing
                if ($status === 'processing') {
                    return $q->whereIn('order_status', ['processing', 'pickup_scheduled']);
                }
                // Grouping Return (Tampilkan semua history retur di list)
                if ($status === 'return_requested') {
                    return $q->whereIn('order_status', ['return_requested', 'return_approved', 'returned', 'return_rejected']);
                }
                // Grouping Cancelled (Tampilkan semua history cancel di list)
                if ($status === 'cancelled') {
                    return $q->whereIn('order_status', ['cancelled', 'canceled', 'cancellation_requested']);
                }
                return $q->where('order_status', $status);
            })
            ->latest()
            ->paginate(10);

        // 2. TRANSFORM DATA (Cek status review per item untuk tombol)
        $orders->getCollection()->transform(function ($order) use ($userId) {
            foreach ($order->items as $item) {
                $productId = $item->productVariant->product_id ?? null;
                if ($productId) {
                    $item->is_reviewed = \App\Models\Review::where('order_id', $order->id)
                        ->where('product_id', $productId)
                        ->where('user_id', $userId)
                        ->exists();
                } else {
                    $item->is_reviewed = false;
                }
            }
            return $order;
        });

        // 3. LOGIKA BADGE/COUNTER (Sesuai Permintaan Anda)
        $counts = [
            // A. Pending & Shipped (Tetap Muncul Selama Ada Isinya)
            'pending'    => Order::where('user_id', $userId)->where('order_status', 'pending')->count(),
            'processing' => Order::where('user_id', $userId)->whereIn('order_status', ['processing', 'pickup_scheduled'])->count(),
            'shipped'    => Order::where('user_id', $userId)->where('order_status', 'shipped')->count(),
            
            // B. Completed (HANYA MUNCUL JIKA BELUM DI-REVIEW)
            'completed'  => Order::where('user_id', $userId)
                            ->where('order_status', 'completed')
                            ->whereDoesntHave('reviews') // <--- Logika Cerdas: Cek jika TIDAK punya review
                            ->count(),
            
            // C. Cancelled (HANYA MUNCUL JIKA SEDANG REQUEST)
            // Jika status sudah 'cancelled' (final), angka hilang.
            'cancelled'  => Order::where('user_id', $userId)
                            ->where('order_status', 'cancellation_requested') 
                            ->count(),
                            
            // D. Return (HANYA MUNCUL JIKA SEDANG REQUEST)
            // Jika status sudah 'returned' atau 'rejected', angka hilang.
            'return_requested' => Order::where('user_id', $userId)
                            ->where('order_status', 'return_requested')
                            ->count(),
        ];

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'currentStatus' => $status,
            'counts' => $counts
        ]);
    }

    /**
     * User initiates order cancellation.
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        try {
            $result = $this->orderService->cancelOrder(Auth::user(), (int) $id, $request->reason);
            return back()->with('toast_success', $result['message']);
        } catch (\Exception $e) {
            return back()->with('toast_error', $e->getMessage());
        }
    }

    /**
     * Mark order as completed (User confirms receipt).
     */
    public function complete(Request $request, $id)
    {
        $order = Order::where('user_id', Auth::id())->findOrFail($id);

        if ($order->order_status !== 'shipped') {
            return back()->with('toast_error', 'Order must be shipped before marking as completed.');
        }

        // Use withoutSyncingToSearch to prevent Elasticsearch errors if service is down
        if (method_exists(Order::class, 'withoutSyncingToSearch')) {
            Order::withoutSyncingToSearch(function () use ($order) {
                $order->update(['order_status' => 'completed']);
            });
        } else {
            $order->update(['order_status' => 'completed']);
        }

        return back()->with('toast_success', 'Thank you! Order marked as completed.');
    }

    /**
     * GENERATE FAKE TRACKING HISTORY (SANDBOX SIMULATION)
     * Simulates a realistic shipment timeline with Time Travel logic (Day+1, Day+2).
     */
    public function track($id)
    {
        // 1. Load Order with Address Relations (City & District)
        $order = Order::with([
            'shippingAddress.city', 
            'shippingAddress.province', 
            'shippingAddress.district'
        ])->findOrFail($id);

        $history = [];
        
        // Base Time: Order Creation Time
        $t0 = Carbon::parse($order->created_at);
        
        // Location Data
        $districtName = $order->shippingAddress->district->name ?? null;
        $cityName = $order->shippingAddress->city->name ?? 'Destination City';
        $receiverName = $order->shippingAddress->receiver_name ?? 'Customer';
        $courierName = strtoupper($order->shipping_courier ?? 'Express Courier');

        // Logic Hub Location: "Hub [District], [City]"
        $finalHubLocation = $districtName ? "Hub {$districtName}, {$cityName}" : "Hub {$cityName}";

        // --- PHASE 1: ORDER PLACED (T0) ---
        $history[] = [
            'date' => $t0->format('Y-m-d H:i'),
            'description' => 'Order successfully placed.',
            'location' => 'System'
        ];

        // --- PHASE 2: PAYMENT (T0 + 15 Mins) ---
        if ($order->payment_status === 'paid') {
            $history[] = [
                'date' => $t0->copy()->addMinutes(15)->format('Y-m-d H:i'),
                'description' => 'Payment verified. Seller is processing order.',
                'location' => 'System'
            ];
        }

        // --- PHASE 3: PICKUP SCHEDULED (T0 + 4 Hours) ---
        if (in_array($order->order_status, ['pickup_scheduled', 'shipped', 'completed'])) {
            $history[] = [
                'date' => $t0->copy()->addHours(4)->format('Y-m-d H:i'),
                'description' => 'Seller has scheduled pickup.',
                'location' => 'Seller Warehouse'
            ];
            $history[] = [
                'date' => $t0->copy()->addHours(5)->format('Y-m-d H:i'),
                'description' => 'Waiting for courier to pick up.',
                'location' => 'Seller Warehouse'
            ];
        }

        // --- PHASE 4: SHIPPED (The Long Journey) ---
        if (in_array($order->order_status, ['shipped', 'completed'])) {
            
            // 1. Picked Up: Next Day (H+1) at 10:00 AM
            $t1_pickup = $t0->copy()->addDay()->setHour(10)->setMinute(rand(0, 59));
            $history[] = [
                'date' => $t1_pickup->format('Y-m-d H:i'),
                'description' => "Package picked up by {$courierName}.",
                'location' => 'Origin Gateway'
            ];

            // 2. Sorting Center: Same Day (H+1) at 08:00 PM
            $t2_sorting = $t1_pickup->copy()->setHour(20)->setMinute(rand(0, 30));
            $history[] = [
                'date' => $t2_sorting->format('Y-m-d H:i'),
                'description' => 'Arrived at Central Sorting Hub.',
                'location' => 'Sorting Center (Jakarta)'
            ];

            // 3. Depart to City: Next Day (H+2) at 04:00 AM
            $t3_transit = $t2_sorting->copy()->addDay()->setHour(4)->setMinute(0);
            $history[] = [
                'date' => $t3_transit->format('Y-m-d H:i'),
                'description' => "Departed from Transit Hub to {$cityName}.",
                'location' => 'Transit Hub'
            ];

            // 4. Arrived at District Hub: Day After (H+3) at 07:00 AM
            $t4_arrived = $t3_transit->copy()->addDay()->setHour(7)->setMinute(30);
            $history[] = [
                'date' => $t4_arrived->format('Y-m-d H:i'),
                'description' => "Arrived at Delivery Point in {$districtName}.",
                'location' => $finalHubLocation
            ];
            
            // 5. Out for Delivery: Same Day (H+3) at 09:30 AM
            $t5_out = $t4_arrived->copy()->addHours(2);
            $history[] = [
                'date' => $t5_out->format('Y-m-d H:i'),
                'description' => 'Courier is out for delivery.',
                'location' => $finalHubLocation
            ];
        }

        // --- PHASE 5: COMPLETED (H+3 Afternoon) ---
        if ($order->order_status === 'completed') {
            // Recalculate T5 base to prevent variable scope issues
            $tBaseForDelivery = $t0->copy()->addDays(3)->setHour(9)->setMinute(30); 
            
            $history[] = [
                'date' => $tBaseForDelivery->addHours(4)->format('Y-m-d H:i'),
                'description' => "Delivered. Received by {$receiverName}.",
                'location' => 'Recipient Address'
            ];
        }

        // Reverse to show latest first
        $history = array_reverse($history);

        return response()->json([
            'success' => true,
            'data' => [
                'waybill' => $order->shipping_tracking_number ?? 'ON-PROCESS',
                'courier' => $courierName,
                'status' => strtoupper($order->order_status),
                'history' => $history
            ]
        ]);
    }
}