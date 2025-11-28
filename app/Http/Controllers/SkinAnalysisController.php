<?php

namespace App\Http\Controllers;

use App\Models\UserSkinProfile;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ProductResource;

class SkinAnalysisController extends Controller
{
    /**
     * Menampilkan halaman dan produk rekomendasi.
     */
    public function index()
    {
        $user = Auth::user();
        $profile = UserSkinProfile::where('user_id', $user->id)->first();
        
        $recommendedProducts = [];

        if ($profile) {
            $query = Product::with(['variants', 'category', 'brand']);

            // 1. Filter Skin Type (Wajib)
            $query->whereJsonContains('suitability_tags', $profile->skin_type);

            // 2. Filter Concerns (Gabungan Checkbox + Input Manual)
            if (!empty($profile->skin_concerns)) {
                $query->orWhere(function($q) use ($profile) {
                    foreach ($profile->skin_concerns as $concern) {
                        $q->orWhereJsonContains('suitability_tags', $concern);
                    }
                });
            }

            $productsRaw = $query->inRandomOrder()->take(4)->get();
            $recommendedProducts = ProductResource::collection($productsRaw);
        }

        return Inertia::render('SkinAnalysis/Index', [
            'existingProfile' => $profile,
            'recommendedProducts' => $recommendedProducts 
        ]);
    }

    /**
     * Menyimpan data dengan logika Keyword Mapping.
     */
    public function store(Request $request)
    {
        // 1. Validasi
        $validated = $request->validate([
            'answers' => 'required|array',
            'concerns' => 'array',         // Boleh kosong jika custom_concern diisi
            'custom_concern' => 'nullable|string|max:500' // Input teks bebas
        ]);

        // 2. Hitung Skin Type
        $counts = array_count_values($validated['answers']);
        $maxKey = !empty($counts) ? array_keys($counts, max($counts))[0] : 'B';
        
        $skinType = match ($maxKey) {
            'A' => 'Dry Skin',
            'B' => 'Normal Skin',
            'C' => 'Oily Skin',
            'D' => 'Combination Skin',
            default => 'Normal Skin',
        };

        // 3. LOGIKA KEYWORD MAPPING (User Text -> Database Tag)
        $finalConcerns = $validated['concerns'] ?? [];

        if (!empty($request->custom_concern)) {
            $text = strtolower($request->custom_concern);

            // KAMUS: [Kata User => Tag Database]
            // Pastikan Tag di kanan SAMA dengan yang ada di Database Produk Anda
            $keywords = [
                'bruntusan' => 'Texture',
                'kasar' => 'Texture',
                'tekstur' => 'Texture',
                'mata panda' => 'Dark Circles',
                'hitam di mata' => 'Dark Circles',
                'flek' => 'Dark Spots',
                'noda hitam' => 'Dark Spots',
                'bekas jerawat' => 'Acne Scars',
                'bopeng' => 'Acne Scars',
                'merah' => 'Redness',
                'gatal' => 'Sensitive',
                'perih' => 'Sensitive',
                'keriput' => 'Anti Aging',
                'garis halus' => 'Anti Aging',
                'kendur' => 'Anti Aging',
                'pori' => 'Pores',
                'lubang' => 'Pores',
                'komedo' => 'Blackheads'
            ];

            foreach ($keywords as $input => $tag) {
                if (str_contains($text, $input)) {
                    $finalConcerns[] = $tag;
                }
            }
        }

        // Bersihkan duplikat
        $finalConcerns = array_values(array_unique($finalConcerns));

        // 4. Simpan ke Database
        UserSkinProfile::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'skin_type' => $skinType,
                'skin_concerns' => $finalConcerns, // Ini sudah gabungan Checkbox + Hasil Mapping
                'answers_data' => $validated['answers']
            ]
        );

        return redirect()->back()->with('success', 'Analisis kulit diperbarui!');
    }
}