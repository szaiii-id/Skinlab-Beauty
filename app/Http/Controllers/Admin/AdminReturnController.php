<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderReturn;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminReturnController extends Controller
{
    /**
     * Display list of return requests.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $search = $request->input('search');

        $returns = OrderReturn::with(['order', 'order.user'])
            ->when($status !== 'all', function ($q) use ($status) {
                return $q->where('status', $status);
            })
            ->when($search, function ($q) use ($search) {
                $q->whereHas('order', function ($subQ) use ($search) {
                    $subQ->where('order_number', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Returns/Index', [
            'returns' => $returns,
            'filters' => [
                'status' => $status,
                'search' => $search
            ]
        ]);
    }

    /**
     * Show return details & evidence.
     */
    public function show($id)
    {
        $returnRequest = OrderReturn::with([
            'order.user', 
            'order.items.productVariant.product'
        ])->findOrFail($id);

        return Inertia::render('Admin/Returns/Show', [
            'returnRequest' => $returnRequest
        ]);
    }

    /**
     * Handle Approve or Reject action.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'admin_note' => 'nullable|string',
            'restock' => 'boolean' // Menerima input checkbox dari Frontend
        ]);

        $returnRequest = OrderReturn::with('order.items')->findOrFail($id);

        if ($returnRequest->status !== 'pending') {
            return back()->with('error', 'This request has already been processed.');
        }

        DB::beginTransaction();
        try {
            $order = Order::find($returnRequest->order_id);

            if ($request->action === 'approve') {
                // 1. Cek apakah admin memilih untuk restock (Centang Checkbox)
                $shouldRestock = $request->boolean('restock');

                // 2. Update Return Status & Catat Keputusan Restock
                $returnRequest->update([
                    'status' => 'approved',
                    'admin_note' => $request->admin_note,
                    'is_restocked' => $shouldRestock
                ]);

                // 3. Update Order Status
                // Gunakan withoutSyncingToSearch untuk mencegah error jika Elasticsearch mati
                if (method_exists(Order::class, 'withoutSyncingToSearch')) {
                    Order::withoutSyncingToSearch(function () use ($order) {
                        $order->update(['order_status' => 'returned']);
                    });
                } else {
                    $order->update(['order_status' => 'returned']);
                }

                // 4. LOGIKA RESTOCK
                // Hanya jalankan jika Admin mencentang checkbox "Return to Stock?"
                if ($shouldRestock) {
                    foreach ($order->items as $item) {
                        if ($item->product_variant_id) {
                            ProductVariant::where('id', $item->product_variant_id)
                                ->increment('stock', $item->quantity);
                        }
                    }
                    $message = 'Request approved. Stock restored to inventory.';
                } else {
                    $message = 'Request approved. Item marked as damaged (No stock restore).';
                }

                if ($order->user) {
                    $order->user->notify(new \App\Notifications\ReturnRequestApproved($returnRequest));
                }

            } else {
                // --- REJECT LOGIC ---
                
                // 1. Update Return Status
                $returnRequest->update([
                    'status' => 'rejected',
                    'admin_note' => $request->admin_note,
                    'is_restocked' => false // Tidak direstock karena ditolak (barang masih di user)
                ]);

                // 2. Kembalikan Status Order ke Completed
                if (method_exists(Order::class, 'withoutSyncingToSearch')) {
                    Order::withoutSyncingToSearch(function () use ($order) {
                        $order->update(['order_status' => 'completed']);
                    });
                } else {
                    $order->update(['order_status' => 'completed']);
                }

                $message = 'Return request rejected. Order reverted to completed.';

                if ($order->user) {
                    $order->user->notify(new \App\Notifications\ReturnRequestRejected($returnRequest));
                }
            }

            DB::commit();
            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error processing return: ' . $e->getMessage());
        }
    }
}