<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserSkinProfile;
use App\Models\Product;
use App\Models\Reward; // <--- WAJIB IMPORT MODEL INI
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminSkinAnalysisController extends Controller
{
    /**
     * Display a listing of skin profiles.
     */
    public function index(Request $request)
    {
        $query = UserSkinProfile::with('user')
            ->latest('updated_at');

        // Filter: Skin Type
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('skin_type', $request->type);
        }

        // Search: User Name or Email
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        // --- TAMBAHAN: AMBIL DATA GIFT REWARD ---
        // Ambil reward yang aktif, ada stok, dan khusus klaim admin
        $giftRewards = Reward::where('is_active', true)
            ->where('stock', '>', 0)
            ->where('is_claim_only', true) // Pastikan kolom ini ada di DB Rewards
            ->select('id', 'name', 'stock', 'description') // Ambil yg perlu saja biar ringan
            ->get();
        // ---------------------------------------

        return Inertia::render('Admin/SkinAnalysis/Index', [
            'profiles' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search', 'type']),
            'counts' => [
                'total' => UserSkinProfile::count(),
                'oily' => UserSkinProfile::where('skin_type', 'Oily Skin')->count(),
                'dry' => UserSkinProfile::where('skin_type', 'Dry Skin')->count(),
                'combination' => UserSkinProfile::where('skin_type', 'Combination Skin')->count(),
            ],
            'giftRewards' => $giftRewards // <--- KIRIM KE VUE (Index.vue)
        ]);
    }

    /**
     * Show the detailed skin profile ("Medical Record").
     */
    public function show($id)
    {
        $profile = UserSkinProfile::with(['user.orders' => function($q) {
            $q->where('order_status', 'completed')->latest()->limit(5);
        }])->findOrFail($id);

        $products = Product::with(['variants' => function($q) {
                $q->where('stock', '>', 0);
            }])
            ->whereHas('variants', function($q) {
                $q->where('stock', '>', 0);
            })
            ->get()
            ->map(function($product) {
                $variant = $product->variants->first(); 
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $variant ? $variant->price : 0, 
                    'stock' => $product->variants->sum('stock'),
                    'image_url' => $product->image_url
                ];
            });

        // --- TAMBAHAN: AMBIL DATA GIFT REWARD (SAMA DENGAN INDEX) ---
        $giftRewards = Reward::where('is_active', true)
            ->where('stock', '>', 0)
            ->where('is_claim_only', true)
            ->select('id', 'name', 'stock', 'description')
            ->get();
        // ------------------------------------------------------------

        return Inertia::render('Admin/SkinAnalysis/Show', [
            'profile' => $profile,
            'products' => $products,
            'giftRewards' => $giftRewards // <--- KIRIM KE VUE (Show.vue)
        ]);
    }

    /**
     * Send manual product recommendation.
     */
    public function sendRecommendation(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'message' => 'required|string|min:10|max:500'
        ]);

        $profile = UserSkinProfile::with('user')->findOrFail($id);
        $product = Product::find($request->product_id);

        if ($profile->user) {
            $profile->user->notify(new \App\Notifications\SkinCareRecommendation($product, $request->message));
        }
        
        return back()->with('success', "Recommendation for '{$product->name}' sent to {$profile->user->name}.");
    }

    /**
     * Bulk Auto-Recommend Product based on Skin Type
     */
    public function bulkRecommend(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:user_skin_profiles,id'
        ]);

        $successCount = 0;
        $failedCount = 0;

        DB::beginTransaction();
        try {
            $profiles = UserSkinProfile::with('user')->whereIn('id', $request->ids)->get();

            foreach ($profiles as $profile) {
                // Cari produk yang sesuai tag kulit user
                $bestProduct = Product::where('is_active', true)
                    ->whereHas('variants', function($q) { 
                        $q->where('stock', '>', 0); 
                    })
                    ->whereJsonContains('suitability_tags', $profile->skin_type)
                    ->inRandomOrder()
                    ->first();

                if ($bestProduct) {
                    
                    if ($profile->user) {
                        $autoMessage = "Based on your {$profile->skin_type} profile, our experts highly recommend this product for your daily routine.";
                        $profile->user->notify(new \App\Notifications\SkinCareRecommendation($bestProduct, $autoMessage));
                    }

                    $successCount++;
                } else {
                    $failedCount++;
                }
            }
            
            DB::commit();

            $message = "Successfully sent automated recommendations to {$successCount} users.";
            if ($failedCount > 0) {
                $message .= " ({$failedCount} skipped due to no matching product found).";
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'System error occurred: ' . $e->getMessage());
        }
    }
}