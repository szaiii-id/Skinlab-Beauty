<?php

namespace App\Http\Controllers\Admin;

use App\Exports\Reports\Sales\SalesReportExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Inertia\Inertia;

// Models (Lengkap Sesuai Kode Asli Anda)
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StockHistory;
use App\Models\StockOpname;
use App\Models\User;
use App\Models\UserSkinProfile;
use App\Models\Review;
use App\Models\OrderReturn;
use App\Models\OrderCancellation;
use App\Models\UserReward;
use App\Models\BanRequest;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'sales');
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date'))->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date'))->endOfDay() : Carbon::now()->endOfMonth();

        $data = $this->getEnterpriseData($tab, $startDate, $endDate);

        return Inertia::render('Admin/Reports/Index', [
            'tab' => $tab,
            'data' => $data,
            'filters' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ]
        ]);
    }

    public function print(Request $request)
    {
        $tab = $request->get('tab', 'sales');
        $start = $request->get('start_date') ? Carbon::parse($request->get('start_date'))->startOfDay() : Carbon::now()->startOfMonth();
        $end = $request->get('end_date') ? Carbon::parse($request->get('end_date'))->endOfDay() : Carbon::now()->endOfMonth();

        $data = $this->getEnterpriseData($tab, $start, $end);

        return Inertia::render('Admin/Reports/Print/SalesPrint', [
            'data' => $data,
            'filters' => [
                'tab' => $tab,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
            ],
            'user' => $request->user(),
            'current_time' => Carbon::now()->format('d M Y H:i'),
        ]);
    }

    public function export(Request $request)
    {
        return back()->with('message', 'Export functionality is ready.');
    }

    // --- CENTRAL LOGIC ---
    private function getEnterpriseData($tab, $start, $end)
    {
        return match ($tab) {
            'sales'      => $this->getSalesReport($start, $end),
            'inventory'  => $this->getInventoryReport($start, $end),
            'customers'  => $this->getCustomerInsights($start, $end),
            'loyalty'    => $this->getLoyaltyReport($start, $end),
            'operations' => $this->getOperationsReport($start, $end),
            'audit'      => $this->getAuditReport($start, $end),
            default      => [],
        };
    }

    // 1. SALES REPORT
    private function getSalesReport($start, $end)
    {
        $summary = Order::whereBetween('created_at', [$start, $end])
            ->selectRaw("
                SUM(CASE WHEN order_status = 'completed' THEN total_amount ELSE 0 END) as gross_revenue,
                SUM(CASE WHEN order_status = 'completed' THEN (total_amount - shipping_cost) ELSE 0 END) as net_revenue,
                COUNT(CASE WHEN order_status = 'completed' THEN 1 END) as total_transactions,
                AVG(CASE WHEN order_status = 'completed' THEN total_amount ELSE 0 END) as aov,
                SUM(CASE WHEN order_status = 'completed' THEN shipping_cost ELSE 0 END) as shipping_revenue
            ")->first();

        $chartData = Order::where('order_status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->groupBy('date')->orderBy('date')->get();

        $topProducts = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_variants', 'order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('orders.order_status', 'completed')
            ->whereBetween('orders.created_at', [$start, $end])
            ->select(
                'products.name', 'product_variants.volume',
                DB::raw('SUM(order_items.quantity) as qty_sold'),
                DB::raw('SUM(order_items.price * order_items.quantity) as revenue')
            )
            ->groupBy('product_variants.id', 'products.name', 'product_variants.volume')
            ->orderByDesc('qty_sold')->limit(10)->get();

        $detailedTransactions = Order::with('user')
            ->where('order_status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->latest()->limit(100)->get();

        return compact('summary', 'chartData', 'topProducts', 'detailedTransactions');
    }

    // 2. INVENTORY REPORT (LOGIKA BARU: SKU + IN/OUT + NO RELATION ERROR)
    private function getInventoryReport($start, $end)
    {
        $assetValue = ProductVariant::sum(DB::raw('stock * price'));
        $totalStockCount = ProductVariant::sum('stock');
        
        $lowStockItems = ProductVariant::with('product')
            ->where('stock', '<=', 10)->orderBy('stock', 'asc')->limit(10)->get()
            ->map(fn($v) => [
                'name' => $v->product->name . ' (' . $v->volume . ')',
                'sku' => $v->sku, 'stock' => $v->stock,
            ]);

        $soldVariantIds = OrderItem::whereHas('order', function($q) use ($start, $end) {
            $q->whereBetween('created_at', [$start, $end]);
        })->pluck('product_variant_id')->toArray();

        $deadStockItems = ProductVariant::with('product')
            ->where('stock', '>', 50)->whereNotIn('id', $soldVariantIds)->limit(10)->get()
            ->map(fn($v) => [
                'sku' => $v->sku,
                'name' => $v->product->name . ' (' . $v->volume . ')',
                'stock' => $v->stock,
                'value' => $v->stock * $v->price
            ]);

        // --- FIX LOGIKA MUTASI ---
        $mutations = StockHistory::with(['productVariant.product']) // Hapus 'order' agar tidak error
            ->whereBetween('created_at', [$start, $end])
            ->latest()->limit(50)->get()
            ->map(fn($h) => [
                'date' => $h->created_at->format('d/m/Y H:i'),
                'item' => $h->productVariant ? $h->productVariant->product->name . ' (' . $h->productVariant->volume . ')' : 'Deleted Item',
                
                // TYPE: IN (Hijau) jika +, OUT (Merah) jika -
                // DI DALAM FILE ReportController.php
                // Cari bagian ini:
                'type' => $h->qty_change >= 0 ? 'IN' : 'OUT',

                // GANTI DENGAN INI (3 Kondisi):
                'type' => $h->qty_change == 0 ? 'MATCH' : ($h->qty_change > 0 ? 'IN' : 'OUT'),
                
                // REF: Pakai SKU (Bukan Order No agar aman)
                'ref'  => $h->productVariant ? $h->productVariant->sku : '-',
                
                'change' => $h->qty_change > 0 ? '+'.$h->qty_change : $h->qty_change,
                'current_stock' => $h->current_stock,
            ]);

        return compact('assetValue', 'totalStockCount', 'lowStockItems', 'deadStockItems', 'mutations');
    }

    // 3. CUSTOMER INSIGHTS (LOGIKA DIKEMBALIKAN KE JOIN indonesia_cities)
    private function getCustomerInsights($start, $end)
    {
        // A. Skin Type (Tetap pakai UserSkinProfile)
        $skinStats = DB::table('user_skin_profiles')
            ->select('skin_type', DB::raw('count(*) as count'))
            ->whereNotNull('skin_type')->groupBy('skin_type')->orderByDesc('count')->get();

        // B. Skin Concerns (JSON Parsing)
        $rawConcerns = DB::table('user_skin_profiles')->whereNotNull('skin_concerns')->pluck('skin_concerns');
        $concernsMap = [];
        foreach ($rawConcerns as $json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                foreach ($decoded as $c) {
                    $key = trim(ucwords($c));
                    if(!empty($key)) $concernsMap[$key] = ($concernsMap[$key] ?? 0) + 1;
                }
            }
        }
        arsort($concernsMap);
        $topConcerns = array_slice($concernsMap, 0, 8);

        // C. Top Cities (FIX: MENGGUNAKAN JOIN INDONESIA_CITIES LAGI)
        try {
            $topCities = DB::table('user_addresses')
                ->join('indonesia_cities', 'user_addresses.city_code', '=', 'indonesia_cities.code')
                ->select('indonesia_cities.name', DB::raw('count(*) as total'))
                ->groupBy('indonesia_cities.name')
                ->orderByDesc('total')
                ->limit(9)
                ->get();
        } catch (\Exception $e) { 
            $topCities = collect([]); 
        }

        return compact('skinStats', 'topConcerns', 'topCities');
    }

    // 4. LOYALTY (FITUR LAMA ADA: UserReward)
    private function getLoyaltyReport($start, $end)
    {
        $topSpenders = User::withSum(['orders as total_spent' => function($q) use ($start, $end) {
                $q->where('order_status', 'completed')->whereBetween('created_at', [$start, $end]);
            }], 'total_amount')
            ->withCount(['orders as freq' => function($q) use ($start, $end) {
                $q->where('order_status', 'completed')->whereBetween('created_at', [$start, $end]);
            }])
            ->orderByDesc('total_spent')->limit(10)->get();

        $avgRating = Review::whereBetween('created_at', [$start, $end])->avg('rating');

        // Kembalikan Data Rewards
        $redemptions = UserReward::with('reward')
            ->whereBetween('created_at', [$start, $end])
            ->where('source', 'redeem')
            ->latest()->limit(10)->get();

        return compact('topSpenders', 'avgRating', 'redemptions');
    }

    // 5. OPERATIONS (FITUR LAMA ADA: OrderReturn)
    private function getOperationsReport($start, $end)
    {
        $cancellations = OrderCancellation::whereBetween('created_at', [$start, $end])
            ->select('reason', DB::raw('count(*) as total'))->groupBy('reason')->orderByDesc('total')->get();
        
        $potentialLoss = Order::where('order_status', 'cancelled')->whereBetween('created_at', [$start, $end])->sum('total_amount');

        // Kembalikan Data Returns
        $returns = OrderReturn::with(['order.user'])
            ->whereBetween('created_at', [$start, $end])
            ->latest()->get();

        return compact('cancellations', 'potentialLoss', 'returns');
    }

    // 6. AUDIT (FITUR LAMA ADA: BanRequest)
    private function getAuditReport($start, $end)
    {
        $opnameLoss = DB::table('stock_opname_items')
            ->join('stock_opnames', 'stock_opname_items.stock_opname_id', '=', 'stock_opnames.id')
            ->join('product_variants', 'stock_opname_items.product_variant_id', '=', 'product_variants.id')
            ->where('stock_opnames.status', 'completed')->whereBetween('stock_opnames.completed_at', [$start, $end])
            ->whereRaw('physical_qty < system_qty')
            ->selectRaw('SUM((system_qty - physical_qty) * product_variants.price) as loss_value')
            ->value('loss_value') ?? 0;
        
        // Kembalikan Ban Requests
        $bannedUsers = BanRequest::whereBetween('created_at', [$start, $end])
            ->with(['user', 'requester', 'reviewer'])
            ->latest()->get();

        return compact('opnameLoss', 'bannedUsers');
    }
}