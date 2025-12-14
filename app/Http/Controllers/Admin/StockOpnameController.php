<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StockOpnameController extends Controller
{
    /**
     * 1. List Opname History
     */
    public function index()
    {
        $opnames = StockOpname::orderBy('created_at', 'desc')->get();
        return Inertia::render('Admin/StockOpname/Index', ['opnames' => $opnames]);
    }

    /**
     * 2. START NEW SESSION (SNAPSHOT)
     * Freezes the current system stock into the opname table.
     */
    public function store(Request $request)
    {
        DB::transaction(function () use ($request) {
            // A. Create Opname Header
            $opname = StockOpname::create([
                'opname_number' => 'SO-' . time(), // Generate unique number
                'opname_date'   => now(),
                'status'        => 'processing',
                'created_by'    => Auth::id(),
                'notes'         => $request->notes,
            ]);

            // B. Get ALL Product Variants
            $variants = ProductVariant::all();

            // C. Snapshot Data (Lock current system stock)
            foreach ($variants as $variant) {
                StockOpnameItem::create([
                    'stock_opname_id'    => $opname->id,
                    'product_variant_id' => $variant->id,
                    'system_qty'         => $variant->stock, // <--- IMPORTANT: Copy current stock
                    'physical_qty'       => null, // Default null (not counted yet)
                ]);
            }
        });

        return redirect()->back()->with('success', 'Stock Opname session started.');
    }

    /**
     * 3. Worksheet Page (Input Form)
     */
    public function edit($id)
    {
        $opname = StockOpname::with(['items.variant.product'])->findOrFail($id);
        
        return Inertia::render('Admin/StockOpname/Form', [
            'opname' => $opname
        ]);
    }

    /**
     * 4. SAVE DRAFT
     * Saves physical counts temporarily without updating master stock.
     * FIX: Accepts array format [ID => QTY] for accuracy.
     */
    public function update(Request $request, $id)
    {
        // $request->items format: { "101": 20, "102": 5, ... }
        $itemsMap = $request->items ?? [];

        foreach ($itemsMap as $itemId => $qty) {
            StockOpnameItem::where('id', $itemId)->update([
                'physical_qty' => $qty
            ]);
        }

        return redirect()->back()->with('success', 'Draft saved successfully.');
    }

    /**
     * 5. FINALIZE (EXECUTION)
     * Updates master stock, creates history, and syncs ElasticSearch.
     * FIX: Reads Direct Input & Auto-Matches null values.
     */
    public function finish(Request $request, $id)
    {
        $opname = StockOpname::with('items')->findOrFail($id);

        if ($opname->status === 'completed') {
            return back()->withErrors('Stock Opname is already completed.');
        }

        // ARRAY TO HOLD IDs: To track which products changed for bulk Elastic update
        $updatedVariantIds = [];

        DB::transaction(function () use ($opname, $request, &$updatedVariantIds) {
            
            // Get latest input from user
            $submittedItems = $request->items ?? [];

            foreach ($opname->items as $item) {
                
                // Get user input by ID
                if (array_key_exists($item->id, $submittedItems)) {
                    $inputVal = $submittedItems[$item->id];
                } else {
                    $inputVal = $item->physical_qty;
                }

                // LOGIC: Null/Empty = Match (Same as System Qty)
                if ($inputVal === null || $inputVal === '') {
                    $physical = $item->system_qty;
                } else {
                    $physical = $inputVal;
                }
                
                // 1. Save to opname item database
                $item->physical_qty = $physical;
                $item->save();

                // 2. Calculate Difference
                $diff = $physical - $item->system_qty;

                // 3. Update Master Stock if there is a difference
                if ($diff !== 0) {
                    $variant = ProductVariant::lockForUpdate()->find($item->product_variant_id);
                    
                    if ($variant) {
                        // --- ELASTICSEARCH OPTIMIZATION ---
                        // withoutSyncingToSearch: Update DB without syncing Elastic immediately (for speed)
                        $variant->withoutSyncingToSearch(function () use ($variant, $physical) {
                            $variant->stock = $physical; 
                            $variant->save();
                        });

                        // Record this variant ID to the array
                        $updatedVariantIds[] = $variant->id;

                        // Record History
                        StockHistory::create([
                            'product_variant_id' => $variant->id,
                            'type'               => 'opname',
                            'qty_change'         => $diff,
                            'current_stock'      => $physical,
                            'reference_id'       => $opname->id,
                            'user_id'            => Auth::id(),
                            'note'               => 'Stock Opname Adjustment'
                        ]);
                    }
                }
            }

            $opname->update(['status' => 'completed', 'completed_at' => now()]);
        });

        // --- BULK UPDATE ELASTICSEARCH (OUTSIDE TRANSACTION) ---
        // Send 1 large request to update all changed products at once.
        if (count($updatedVariantIds) > 0) {
            // Ensure ProductVariant model uses Searchable trait
            ProductVariant::whereIn('id', $updatedVariantIds)->searchable();
        }

        return redirect()->route('admin.stock-opname.index')
                         ->with('success', 'Stock Opname completed! Data & Search Index updated.');
    }

    /**
     * 6. DELETE SESSION
     * Only allow deleting sessions that are still in progress (Processing/Draft).
     */
    public function destroy($id)
    {
        $opname = StockOpname::findOrFail($id);

        // SAFEGUARD: Prevent deletion of completed sessions to maintain audit integrity
        if ($opname->status === 'completed') {
            return back()->withErrors('Completed sessions cannot be deleted for audit security.');
        }

        // Delete related items first (Manual cleanup for safety)
        $opname->items()->delete();
        
        // Delete the Opname Header
        $opname->delete();

        return redirect()->back()->with('success', 'Stock Opname session deleted successfully.');
    }
}