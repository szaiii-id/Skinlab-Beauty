<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

// Import Models
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\StockHistory;
use App\Models\OrderReturn;
use App\Models\BanRequest;
use App\Models\Review;
use App\Models\StockOpname;
use App\Models\PromoBanner;
use App\Models\User; // Customer

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // --- 1. LOGIKA CHART INTERAKTIF (ANTI-ERROR SQL STRICT MODE) ---
        $range = $request->input('range', '1w');
        $query = Order::where('order_status', 'completed');
        
        switch ($range) {
            case '1d': // 1 HARI (Tampilkan Per Jam)
                $startDate = Carbon::today();
                // Grouping SQL: Jam (0-23)
                $groupBy = 'HOUR(created_at)'; 
                $orderBy = 'HOUR(created_at)';
                // Format PHP nanti: 13:00
                $labelFormat = function($val) { return str_pad($val, 2, '0', STR_PAD_LEFT) . ':00'; };
                break;
                
            case '1w': // 1 MINGGU (Tampilkan Per Hari)
                $startDate = Carbon::now()->subDays(7);
                // Grouping SQL: Tanggal (YYYY-MM-DD)
                $groupBy = 'DATE(created_at)'; 
                $orderBy = 'DATE(created_at)';
                // Format PHP nanti: Mon, 12
                $labelFormat = function($val) { return Carbon::parse($val)->format('D, d'); };
                break;
                
            case '1m': // 1 BULAN (Tampilkan Per Hari)
                $startDate = Carbon::now()->subMonth();
                $groupBy = 'DATE(created_at)'; 
                $orderBy = 'DATE(created_at)';
                // Format PHP nanti: 12 Dec
                $labelFormat = function($val) { return Carbon::parse($val)->format('d M'); };
                break;
                
            case '1y': // 1 TAHUN (Tampilkan Per Bulan)
                $startDate = Carbon::now()->subYear();
                // Grouping SQL: Tahun-Bulan (YYYY-MM)
                $groupBy = 'DATE_FORMAT(created_at, "%Y-%m")'; 
                $orderBy = 'DATE_FORMAT(created_at, "%Y-%m")';
                // Format PHP nanti: Dec 2024
                $labelFormat = function($val) { return Carbon::parse($val . '-01')->format('M Y'); };
                break;
                
            default: // Default 1 Minggu
                $startDate = Carbon::now()->subDays(7);
                $groupBy = 'DATE(created_at)';
                $orderBy = 'DATE(created_at)';
                $labelFormat = function($val) { return Carbon::parse($val)->format('D, d'); };
        }

        // QUERY SQL AMAN (Select Raw sesuai Group By)
        $revenueDataRaw = $query->where('created_at', '>=', $startDate)
            ->selectRaw("{$groupBy} as date_group, SUM(total_amount) as total")
            ->groupBy(DB::raw($groupBy))
            ->orderBy(DB::raw($orderBy), 'asc')
            ->get();

        // FORMATTING DI PHP (Mapping Labels)
        $labels = $revenueDataRaw->map(function ($item) use ($labelFormat) {
            return $labelFormat($item->date_group);
        });

        $revenueChart = [
            'labels' => $labels,
            'data'   => $revenueDataRaw->pluck('total'),
            'range'  => $range 
        ];

        // --- 2. EXECUTIVE SUMMARY ---
        $summary = [
            'revenue_total'    => Order::where('order_status', 'completed')->sum('total_amount'),
            'orders_total'     => Order::where('order_status', 'completed')->count(),
            'total_campaigns'  => PromoBanner::count(), 
            'avg_rating'       => number_format(Review::avg('rating') ?? 0, 1),
            'total_customers'  => User::count(),
            'total_reviews'    => Review::count(),
        ];

        // --- 3. ACTION CENTER ---
        $actions = [
            'pending_returns' => OrderReturn::where('status', 'pending')->count(),
            'pending_bans'    => BanRequest::where('status', 'pending')->count(),
            'active_opname'   => StockOpname::where('status', 'processing')->exists(),
            'low_stock'       => ProductVariant::where('stock', '<=', 10)->count(),
        ];

        // --- 4. SKIN INSIGHTS ---
        $skinStats = DB::table('user_skin_profiles')
            ->select('skin_type', DB::raw('count(*) as total'))
            ->groupBy('skin_type')
            ->orderByDesc('total')
            ->limit(4)
            ->get();

        // --- 5. LIVE STOCK LOG ---
        $stockLog = StockHistory::with(['productVariant.product'])
            ->latest()->limit(5)->get()
            ->map(fn($h) => [
                'item'   => $h->productVariant ? $h->productVariant->product->name : 'Item Deleted',
                'change' => $h->qty_change,
                'type'   => $h->qty_change == 0 ? 'MATCH' : ($h->qty_change > 0 ? 'IN' : 'OUT'),
                'time'   => $h->created_at->diffForHumans(),
            ]);

        // --- 6. ORDER STATS ---
        $orderStats = [
            'processing' => Order::where('order_status', 'processing')->count(),
            'pickup'     => Order::where(function($q) {
                                $q->where('order_status', 'schedule_pickup')
                                  ->orWhere('order_status', 'pickup_scheduled')
                                  ->orWhere('order_status', 'PICKUP_SCHEDULED')
                                  ->orWhere('order_status', 'like', '%pickup%');
                            })->count(),
            'shipped'    => Order::where('order_status', 'shipped')->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'revenueChart'  => $revenueChart,
            'summary'       => $summary,
            'actions'       => $actions,
            'skinStats'     => $skinStats,
            'stockLog'      => $stockLog,
            'orderStats'    => $orderStats
        ]);
    }
}