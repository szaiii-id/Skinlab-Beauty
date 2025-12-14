<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\ShippingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminOrderController extends Controller
{
    protected ShippingService $shippingService;

    public function __construct(ShippingService $shippingService)
    {
        $this->shippingService = $shippingService;
    }

   /**
     * Display listing of orders (Index).
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all');
        $date = $request->input('date');

        $query = Order::with(['user', 'items'])
            ->latest();

        // 1. Filter Search (Order ID / Nama User / Email)
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                ->orWhere('shipping_tracking_number', 'like', "%{$search}%")
                ->orWhere('resi_number', 'like', "%{$search}%")
                ->orWhereHas('user', function($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            });
        }

        // 2. Filter Status - Handle semua variasi pickup_scheduled
        if ($status !== 'all') {
            if ($status === 'schedule_pickup') {
                $query->where(function($q) {
                    $q->where('order_status', 'schedule_pickup')
                    ->orWhere('order_status', 'PICKUP_SCHEDULED')
                    ->orWhere('order_status', 'pickup_scheduled')
                    ->orWhere('order_status', 'like', '%pickup%')
                    ->orWhere('order_status', 'like', '%PICKUP%');
                });
            } else {
                $query->where('order_status', $status);
            }
        }

        // 3. Filter Tanggal
        if ($date) {
            $query->whereDate('created_at', $date);
        }

        $counts = [
            'pending' => Order::where('order_status', 'pending')->count(),
            'processing' => Order::where('order_status', 'processing')->count(),
            // Menghitung semua variasi status pickup
            'schedule_pickup' => Order::where(function($q) {
                $q->where('order_status', 'schedule_pickup')
                ->orWhere('order_status', 'pickup_scheduled')
                ->orWhere('order_status', 'PICKUP_SCHEDULED');
            })->count(),
            'shipped' => Order::where('order_status', 'shipped')->count(),
            'cancellation_requested' => Order::where('order_status', 'cancellation_requested')->count(),
        ];

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'date']),
            'counts' => $counts
        ]);
    }

    /**
     * Show order details.
     */
    public function show($id)
    {
        $order = Order::with([
            'user', 
            'items.productVariant.product',
            'shippingAddress.province',
            'shippingAddress.city',
            'shippingAddress.district',
            'cancellation',
            'returns'
        ])->findOrFail($id);

        return Inertia::render('Admin/Orders/Show', [
            'order' => $order
        ]);
    }

   /**
     * Update Order Status (Manual Resi or State Change).
     */
    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'order_status' => 'required|in:pending,processing,schedule_pickup,pickup_scheduled,PICKUP_SCHEDULED,shipped,completed,cancelled',
            'resi_number' => 'nullable|string',
        ]);

        Log::info('=== UPDATE ORDER STATUS ===');
        Log::info('Order ID: ' . $id);
        Log::info('Current Status: ' . $order->order_status);
        Log::info('New Status: ' . $validated['order_status']);
        Log::info('===========================');

        // Normalize untuk validasi
        $currentStatus = strtolower($order->order_status);
        $newStatus = strtolower($validated['order_status']);

        // Validasi: Completed hanya bisa jika status shipped
        if ($newStatus === 'completed' && $currentStatus !== 'shipped') {
            Log::error('Validation failed: Order must be shipped before completing');
            return back()->with('error', 'Order must be shipped before completing.');
        }

        // Validasi: Shipped hanya bisa jika status schedule_pickup atau pickup_scheduled
        $allowedForShipped = ['schedule_pickup', 'pickup_scheduled'];
        if ($newStatus === 'shipped' && !in_array($currentStatus, $allowedForShipped)) {
            Log::error('Validation failed: Please schedule pickup first. Current: ' . $currentStatus);
            return back()->with('error', 'Please schedule pickup first before marking as shipped.');
        }

        try {
            DB::transaction(function () use ($order, $validated) {
                $updateData = ['order_status' => $validated['order_status']];
                
                // Jika update ke shipped dan ada resi number, update tracking number juga
                if (strtolower($validated['order_status']) === 'shipped' && !empty($validated['resi_number'])) {
                    $updateData['resi_number'] = $validated['resi_number'];
                }
                
                $order->update($updateData);

                if ($order->user) {
                    $order->user->notify(new \App\Notifications\OrderStatusUpdated($order));
                }
                
                Log::info('Order updated successfully', [
                    'order_id' => $order->id,
                    'old_status' => $order->getOriginal('order_status'),
                    'new_status' => $validated['order_status']
                ]);
            });

            return back()->with('success', 'Order status updated successfully.');

        } catch (\Exception $e) {
            Log::error('Update failed: ' . $e->getMessage());
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    /**
     * Admin Force Cancel Order / Approve Cancellation
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with(['items', 'cancellation'])->findOrFail($id);

            if ($order->order_status === Order::STATUS_COMPLETED || $order->order_status === Order::STATUS_CANCELLED) {
                return back()->with('error', 'Cannot cancel finished order.');
            }

            // 1. Update Tabel OrderCancellation (PENTING!)
            // Cek apakah user sudah pernah request?
            if ($order->cancellation) {
                // Jika sudah ada request, kita Approve & isi Admin Note
                $order->cancellation->update([
                    'status' => 'approved',
                    'admin_note' => $request->reason // Alasan admin menyetujui/membatalkan
                ]);
            } else {
                // Jika User TIDAK request (Admin batal paksa), BUAT RECORD BARU
                // Agar tercatat di history pembatalan
                \App\Models\OrderCancellation::create([
                    'order_id' => $order->id,
                    'reason' => 'Force Cancelled by Admin', // Alasan default user side
                    'status' => 'approved',
                    'admin_note' => $request->reason // Alasan real admin
                ]);
            }

            // 2. Update Status Utama
            $order->update([
                'order_status' => Order::STATUS_CANCELLED,
                // Opsional: Append note ke tabel order utama juga
                'notes' => $order->notes . "\n[Admin Cancelled]: " . $request->reason
            ]);

            if ($order->user) {
                $order->user->notify(new \App\Notifications\OrderCancelled($order));
            }

            // 3. Kembalikan Stok (Restock)
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    $variant = ProductVariant::find($item->product_variant_id);
                    if ($variant) {
                        $variant->increment('stock', $item->quantity);
                    }
                }
            }

            // 4. [TODO] Refund Dana (PENTING!)
            if ($order->payment_status === Order::PAYMENT_PAID) {
                // Panggil Service Refund disini
                // $this->paymentService->processRefund($order);
                Log::info("Perlu Refund Manual untuk Order #{$order->order_number}");
            }

            // 5. Cancel Kurir (Jika sudah booking)
            if ($order->shipping_tracking_number) {
                try {
                    $this->shippingService->cancelBooking($order);
                } catch (\Exception $e) {
                    Log::warning("Courier cancel warning: " . $e->getMessage());
                }
            }

            DB::commit();
            return back()->with('success', 'Order cancelled successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error cancelling: ' . $e->getMessage());
        }
    }

    /**
     * Request Pickup / Booking Kurir (Get Order No Komerce)
     */
    public function book(Request $request, $id)
    {
        try {
            $order = Order::with('items.productVariant', 'shippingAddress')->findOrFail($id);

            if ($order->shipping_tracking_number) {
                return back()->with('error', 'Order already booked.');
            }

            $result = $this->shippingService->bookShipment($order);

            return back()->with('success', 'Booking Successful! Order No: ' . ($result['order_no'] ?? 'N/A'));

        } catch (\Exception $e) {
            return back()->with('error', 'Booking Failed: ' . $e->getMessage());
        }
    }

    /**
     * Schedule Pickup (Dapat AWB)
     */
    public function schedulePickup(Request $request)
    {
        $request->validate([
            'order_ids'      => 'required|array',
            'pickup_date'    => 'required|date',
            'pickup_time'    => 'required',
            'pickup_vehicle' => 'required|in:Motor,Mobil,Truk'
        ]);

        try {
            $result = $this->shippingService->schedulePickup(
                $request->order_ids,
                $request->pickup_date,
                $request->pickup_time,
                $request->pickup_vehicle
            );

            return back()->with('success', 'Pickup scheduled successfully! AWB generated.');

        } catch (\Exception $e) {
            return back()->with('error', 'Schedule Failed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk Request Pickup (Get Order No Komerce)
     */
    public function bulkBook(Request $request)
    {
        $request->validate(['ids' => 'required|array']);

        $success = 0;
        $failed = 0;
        
        foreach ($request->ids as $id) {
            try {
                $order = Order::with('items.productVariant', 'shippingAddress')->find($id);
                if ($order && !$order->shipping_tracking_number && $order->order_status === 'processing') {
                    $this->shippingService->bookShipment($order);
                    $success++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                Log::error("Bulk book error order {$id}: " . $e->getMessage());
                $failed++;
            }
        }

        $message = "{$success} orders booked successfully.";
        if ($failed > 0) {
            $message .= " {$failed} failed.";
        }

        return back()->with('success', $message);
    }

    /**
     * Bulk Schedule Pickup (Get AWB)
     */
    public function bulkSchedulePickup(Request $request)
    {
        $request->validate([
            'order_ids'      => 'required|array',
            'pickup_date'    => 'required|date',
            'pickup_time'    => 'required',
            'pickup_vehicle' => 'required|in:Motor,Mobil,Truk'
        ]);

        try {
            $result = $this->shippingService->schedulePickup(
                $request->order_ids,
                $request->pickup_date,
                $request->pickup_time,
                $request->pickup_vehicle
            );

            return back()->with('success', 'Bulk pickup scheduled successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Bulk schedule failed: ' . $e->getMessage());
        }
    }

    /**
     * Print Shipping Label
     */
    public function printLabel($id)
    {
        try {
            $order = Order::findOrFail($id);
            
            // 1. Validasi: Order harus punya order_no Komerce
            if (!$order->shipping_tracking_number) {
                return response("
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Error - No Order Number</title>
                        <style>
                            body { font-family: Arial, sans-serif; padding: 40px; text-align: center; }
                            .error-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 30px; max-width: 500px; margin: 0 auto; }
                            h2 { color: #dc2626; margin-top: 0; }
                            .btn { display: inline-block; background: #3b82f6; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin: 10px; border: none; cursor: pointer; }
                            .btn:hover { background: #2563eb; }
                        </style>
                    </head>
                    <body>
                        <div class='error-box'>
                            <h2>❌ Cannot Print Label</h2>
                            <p>Order #{$order->order_number} has no Komerce order number.</p>
                            <p><strong>Please book shipment first to get order number.</strong></p>
                            <button onclick='window.history.back()' class='btn'>← Back to Order</button>
                        </div>
                    </body>
                    </html>
                ", 400);
            }
            
            $orderNo = $order->shipping_tracking_number;
            
            // 2. Cek apakah file PDF sudah ada di storage (cache)
            $fileName = 'shipping_label_' . $orderNo . '.pdf';
            $storagePath = 'labels/' . $fileName;
            
            if (Storage::disk('public')->exists($storagePath)) {
                $filePath = storage_path('app/public/' . $storagePath);
                return response()->file($filePath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="Label-' . $order->order_number . '.pdf"'
                ]);
            }
            
            // 3. Generate via ShippingService
            $pdfUrl = $this->shippingService->generateLabel($order);
            
            // 4. Redirect ke file PDF
            return redirect($pdfUrl);
            
        } catch (\Exception $e) {
            Log::error('Label print failed: ' . $e->getMessage());
            
            $orderNumber = $order->order_number ?? 'N/A';
            $orderNo = $order->shipping_tracking_number ?? 'N/A';
            $error = htmlspecialchars($e->getMessage());
            
            return response("
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Label Error</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 40px; }
                        .container { max-width: 600px; margin: 0 auto; }
                        .error-card { background: white; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 30px; }
                        .error-header { color: #dc2626; border-bottom: 2px solid #fecaca; padding-bottom: 15px; }
                        .info-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; margin: 20px 0; }
                        .btn { display: inline-block; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin: 10px 5px; cursor: pointer; border: none; }
                        .btn-primary { background: #3b82f6; color: white; }
                        .btn-secondary { background: #6b7280; color: white; }
                        .code { font-family: monospace; background: #f1f5f9; padding: 5px 10px; border-radius: 4px; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='error-card'>
                            <div class='error-header'>
                                <h2>⚠️ Label Generation Failed</h2>
                                <p><strong>Error:</strong> {$error}</p>
                            </div>
                            
                            <div class='info-box'>
                                <p><strong>Order:</strong> #{$orderNumber}</p>
                                <p><strong>Komerce Order No:</strong> <span class='code'>{$orderNo}</span></p>
                                <p><em>Please check the order number in Komerce dashboard.</em></p>
                            </div>
                            
                            <div style='margin-top: 30px;'>
                                <button onclick='window.history.back()' class='btn btn-primary'>← Back to Order</button>
                                <button onclick='window.location.reload()' class='btn btn-secondary'>🔄 Retry</button>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ", 500);
        }
    }

    /**
     * Bulk Print Labels
     */
    public function bulkPrintLabels(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id'
        ]);

        $orderIds = $request->order_ids;
        
        // Generate PDF untuk masing-masing order
        foreach ($orderIds as $orderId) {
            try {
                $order = Order::find($orderId);
                if ($order && $order->shipping_tracking_number) {
                    $this->shippingService->generateLabel($order);
                }
            } catch (\Exception $e) {
                Log::error("Bulk print label failed for order {$orderId}: " . $e->getMessage());
            }
        }

        return back()->with('success', 'Labels generation initiated for ' . count($orderIds) . ' orders.');
    }

    /**
     * Bulk Cancel Orders
     */
    public function bulkCancel(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'reason' => 'required|string|min:5',
        ]);

        $success = 0;
        $failed = 0;
        
        foreach ($request->ids as $id) {
            try {
                $order = Order::with('items')->find($id);
                
                if (!$order || in_array($order->order_status, ['completed', 'cancelled'])) {
                    $failed++;
                    continue;
                }
                
                // Proses cancel
                $order->update(['order_status' => 'cancelled']);
                $order->notes = ($order->notes ?? '') . "\n[Bulk Cancelled]: " . $request->reason;
                $order->save();
                
                // Restock items
                foreach ($order->items as $item) {
                    if ($item->product_variant_id) {
                        ProductVariant::where('id', $item->product_variant_id)
                            ->increment('stock', $item->quantity);
                    }
                }
                
                // Cancel kurir jika sudah booking
                if ($order->shipping_tracking_number) {
                    try {
                        $this->shippingService->cancelBooking($order);
                    } catch (\Exception $e) {
                        Log::warning("Courier cancel failed: " . $e->getMessage());
                    }
                }
                
                $success++;
                
            } catch (\Exception $e) {
                Log::error("Bulk cancel failed for order {$id}: " . $e->getMessage());
                $failed++;
            }
        }

        $message = "{$success} orders cancelled successfully.";
        if ($failed > 0) {
            $message .= " {$failed} failed.";
        }

        return back()->with('success', $message);
    }

    /**
     * Bulk Update Order Status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
            'status' => 'required|in:processing,pickup_scheduled,shipped,completed'
        ]);

        $count = Order::whereIn('id', $request->order_ids)
            ->update(['order_status' => $request->status]);
            
        return back()->with('success', "{$count} orders updated to {$request->status}.");
    }

    /**
     * Track Shipment
     */
    public function track($id)
    {
        try {
            $order = Order::findOrFail($id);
            $trackingData = $this->shippingService->trackShipment($order);
            
            return response()->json([
                'success' => true,
                'data' => $trackingData
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Order Detail from Komerce
     */
    public function getKomerceDetail($id)
    {
        try {
            $order = Order::findOrFail($id);
            $detail = $this->shippingService->getOrderDetail($order);
            
            return response()->json([
                'success' => true,
                'data' => $detail
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject Cancellation Request (Revert status to Processing)
     */
    public function rejectCancellation(Request $request, $id)
    {
        $request->validate(['admin_note' => 'required|string|min:5']);

        DB::transaction(function () use ($request, $id) {
            $order = Order::with('cancellation')->findOrFail($id);

            // 1. Update Cancellation Table
            if ($order->cancellation) {
                $order->cancellation->update([
                    'status' => 'rejected',
                    'admin_note' => $request->admin_note
                ]);
            }

            // 2. Revert Order Status (Usually back to 'processing' since it was paid)
            $order->update(['order_status' => 'processing']);

            if ($order->user) {
                $order->user->notify(new \App\Notifications\CancellationRejected($order));
            }

            // 3. Optional: Send Notification to User
            // $this->fcmService->send(...)
        });

        return back()->with('success', 'Cancellation request rejected. Order continued.');
    }
}