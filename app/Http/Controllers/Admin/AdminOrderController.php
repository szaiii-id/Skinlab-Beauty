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
        // Ambil parameter filter dari Frontend
        $search = $request->input('search');
        $status = $request->input('status', 'all');
        $date = $request->input('date');

        // Query Builder
        $query = Order::with(['user', 'items'])
            ->latest();

        // 1. Filter Search (Order ID / Nama User / Email)
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('shipping_tracking_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Filter Status
        if ($status !== 'all') {
            $query->where('order_status', $status);
        }

        // 3. Filter Tanggal
        if ($date) {
            $query->whereDate('created_at', $date);
        }

        // Return ke Vue dengan Pagination
        return Inertia::render('Admin/Orders/Index', [
            'orders' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'date'])
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
            'order_status' => 'required|in:pending,processing,shipped,completed,cancelled',
            'resi_number' => 'nullable|required_if:order_status,shipped|string',
        ]);

        DB::transaction(function () use ($order, $validated) {
            if ($validated['order_status'] === 'shipped' && !empty($validated['resi_number'])) {
                $order->update([
                    'order_status' => 'shipped',
                    'resi_number' => $validated['resi_number'],
                    'shipping_tracking_number' => $validated['resi_number']
                ]);
            } else {
                $order->update(['order_status' => $validated['order_status']]);
            }
        });

        return back()->with('success', 'Order status updated successfully.');
    }

    /**
     * Admin Force Cancel Order
     */
    public function cancel(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|min:5',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::with('items')->findOrFail($id);

            if ($order->order_status === 'completed' || $order->order_status === 'cancelled') {
                return back()->with('error', 'Cannot cancel order with status: ' . $order->order_status);
            }

            // 1. Update Status
            $order->update([
                'order_status' => 'cancelled',
                'notes' => $order->notes . "\n[Admin Cancelled]: " . $request->reason
            ]);

            // 2. Approve cancellation request jika ada
            if ($order->cancellation) {
                $order->cancellation->update(['status' => 'approved']);
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

            // 4. Cancel Kurir (Jika sudah booking)
            if ($order->shipping_tracking_number) {
                try {
                    $this->shippingService->cancelBooking($order);
                } catch (\Exception $e) {
                    Log::warning("Courier cancel failed: " . $e->getMessage());
                }
            }

            DB::commit();
            return back()->with('success', 'Order cancelled and stock restored.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error cancelling: ' . $e->getMessage());
        }
    }

    /**
     * Request Pickup / Booking Kurir (Otomatis)
     */
    public function book(Request $request, $id)
    {
        try {
            $order = Order::with('items.productVariant', 'shippingAddress')->findOrFail($id);

            if ($order->shipping_tracking_number) {
                return back()->with('error', 'Order already booked.');
            }

            $result = $this->shippingService->bookShipment($order);

            return back()->with('success', 'Pickup requested! Tracking No: ' . $result['order_no']);

        } catch (\Exception $e) {
            return back()->with('error', 'Booking Failed: ' . $e->getMessage());
        }
    }

    /**
     * Schedule Pickup (Menentukan Jadwal & Kendaraan)
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
            $this->shippingService->schedulePickup(
                $request->order_ids,
                $request->pickup_date,
                $request->pickup_time,
                $request->pickup_vehicle
            );

            return back()->with('success', 'Pickup scheduled successfully!');

        } catch (\Exception $e) {
            return back()->with('error', 'Schedule Failed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk Request Pickup
     */
    public function bulkBook(Request $request)
    {
        $request->validate(['ids' => 'required|array']);

        $success = 0;
        foreach ($request->ids as $id) {
            try {
                $order = Order::with('items.productVariant', 'shippingAddress')->find($id);
                if (!$order->shipping_tracking_number && $order->order_status === 'processing') {
                    $this->shippingService->bookShipment($order);
                    $success++;
                }
            } catch (\Exception $e) {
                Log::error("Bulk book error order {$id}: " . $e->getMessage());
            }
        }

        return back()->with('success', "{$success} orders booked successfully.");
    }

    /**
     * Print Shipping Label - FIXED VERSION
     */
    public function printLabel($id)
    {
        try {
            $order = Order::findOrFail($id);
            
            // 1. Validasi: Order harus punya tracking number
            if (!$order->shipping_tracking_number) {
                return response("
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Error - No AWB</title>
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
                            <p>Order #{$order->order_number} has no tracking number.</p>
                            <p><strong>Please book shipment first to get AWB number.</strong></p>
                            <button onclick='window.history.back()' class='btn'>← Back to Order</button>
                        </div>
                    </body>
                    </html>
                ", 400);
            }
            
            $awb = $order->shipping_tracking_number;
            
            // 2. Cek apakah file PDF sudah ada di storage (cache)
            $fileName = 'shipping_label_' . $awb . '.pdf';
            $storagePath = 'labels/' . $fileName;
            
            if (Storage::disk('public')->exists($storagePath)) {
                $filePath = storage_path('app/public/' . $storagePath);
                return response()->file($filePath, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="Label-' . $order->order_number . '.pdf"'
                ]);
            }
            
            // 3. Generate via ShippingService
            // ShippingService akan panggil API Komerce:
            // POST https://api-sandbox.collaborator.komerce.id/order/api/v1/orders/print-label?page=page_6&order_no={awb}
            $pdfUrl = $this->shippingService->generateLabel($order);
            
            // 4. Redirect ke file PDF
            return redirect($pdfUrl);
            
        } catch (\Exception $e) {
            Log::error('Label print failed: ' . $e->getMessage());
            
            $orderNumber = $order->order_number ?? 'N/A';
            $awb = $order->shipping_tracking_number ?? 'N/A';
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
                                <p><strong>AWB:</strong> <span class='code'>{$awb}</span></p>
                                <p><em>Please check the AWB number in Komerce dashboard.</em></p>
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
}