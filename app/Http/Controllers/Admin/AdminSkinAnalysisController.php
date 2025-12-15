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
     * UPDATE: Menggunakan withTrashed() agar produk soft-delete tetap bisa dipilih admin.
     */
    public function sendRecommendation(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'message' => 'required|string|min:10|max:500'
        ]);

        try {
            // 1. Ambil Profile & User
            $profile = \App\Models\UserSkinProfile::with('user')->findOrFail($id);

            // Cek apakah User-nya masih ada?
            if (!$profile->user) {
                return back()->with('error', "Gagal: User pemilik profil ini sudah dihapus.");
            }

            // 2. Ambil Produk (Termasuk yang Soft Delete agar tidak error 404 jika admin memilih produk lama)
            // Hapus check 'is_active' jika kolom itu memang tidak ada di DB
            $product = \App\Models\Product::withTrashed()->findOrFail($request->product_id);

            // 3. Kirim Notifikasi (Langsung / Realtime)
            $profile->user->notify(new \App\Notifications\SkinCareRecommendation($product, $request->message));
            
            return back()->with('success', "Recommendation for '{$product->name}' sent to {$profile->user->name} successfully!");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Manual Recommend Error: " . $e->getMessage());
            return back()->with('error', "Terjadi kesalahan sistem: " . $e->getMessage());
        }
    }

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
            // Ambil data profil user
            $profiles = \App\Models\UserSkinProfile::with('user')->whereIn('id', $request->ids)->get();

            foreach ($profiles as $profile) {
                if (!$profile->user) continue;

                // 1. GENERATE KEYWORDS (Logic Brute Force)
                $keywords = [];
                // A. Dari Skin Type
                if ($profile->skin_type) {
                    $keywords[] = $profile->skin_type;
                    $keywords[] = explode(' ', $profile->skin_type)[0];
                }
                // B. Dari Concerns
                if (!empty($profile->skin_concerns)) {
                    foreach ($profile->skin_concerns as $concern) {
                        $clean = trim(explode('/', $concern)[0]);
                        $firstWord = trim(explode(' ', $clean)[0]);
                        if (strlen($firstWord) > 2) $keywords[] = $firstWord;
                    }
                }
                $keywords = array_unique($keywords);
                
                // Log Keyword (Opsional, boleh dihapus nanti)
                \Illuminate\Support\Facades\Log::info("Mencari untuk {$profile->user->name}: " . implode(', ', $keywords));

                // 2. QUERY PENCARIAN (VERSI FIX: TANPA IS_ACTIVE)
                $bestProduct = \App\Models\Product::query() // Hapus where is_active
                    ->whereHas('variants', function($q) {
                        // Pastikan stok ada (paksa baca walau soft delete)
                        $q->where('stock', '>', 0)->withTrashed();
                    })
                    ->where(function($q) use ($keywords) {
                        foreach ($keywords as $word) {
                            // Cari kata kunci di tags
                            $q->orWhere('suitability_tags', 'LIKE', "%{$word}%");
                        }
                    })
                    ->inRandomOrder()
                    ->first();

                // 3. EKSEKUSI
                if ($bestProduct) {
                    $autoMessage = "Hi {$profile->user->name}! Based on your skin profile, we recommend: {$bestProduct->name}.";
                    
                    // Kirim Notifikasi
                    $profile->user->notify(new \App\Notifications\SkinCareRecommendation($bestProduct, $autoMessage));
                    
                    $successCount++;
                } else {
                    $failedCount++;
                }
            }
            
            DB::commit();

            if ($successCount > 0) {
                return back()->with('success', "Recommendations sent to {$successCount} users!");
            }
            
            return back()->with('warning', "No matching products found. (Stock checked)");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}