<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductRepository
{
    public function getAllProductsWithVariants(): LengthAwarePaginator
    {
        return Product::with(['variants', 'category', 'brand'])
            ->orderBy('name', 'asc') 
            ->paginate(12)
            ->appends(request()->query()); 
    }

    public function findByIdWithVariants(int $id): ?Product
    {
        // Eager load reviews juga agar efisien
        return Product::with(['variants', 'category', 'brand', 'reviews.user'])
            ->find($id);
    }

    public function getNewReleases(int $limit = 4): Collection
    {
        return Product::with(['variants', 'category', 'brand'])
            ->latest()
            ->take($limit)
            ->get();
    }

    public function getBestSellers(int $limit = 4): Collection
    {
        return Product::with(['variants', 'category', 'brand'])
            ->oldest()
            ->take($limit)
            ->get();
    }

    public function getProductsByCategoryId(int $categoryId): LengthAwarePaginator
    {
        return Product::with(['variants', 'category', 'brand'])
            ->where('category_id', $categoryId)
            ->orderBy('name', 'asc') 
            ->paginate(12)
            ->appends(request()->query()); 
    }
        
    public function getProductsByBrandId(int $brandId): LengthAwarePaginator
    {
        return Product::with(['variants', 'category', 'brand'])
            ->where('brand_id', $brandId)
            ->orderBy('name', 'asc') 
            ->paginate(12)
            ->appends(request()->query()); 
    }

    public function searchProducts(string $query): LengthAwarePaginator
    {
        return Product::with(['variants', 'category', 'brand'])
            ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%')
                  ->orWhere('description', 'LIKE', '%' . $query . '%');
            })
            ->orWhereHas('brand', function ($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%');
            })
            ->orWhereHas('category', function ($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%');
            })
            ->orderBy('name', 'asc')
            ->paginate(12)
            ->appends(request()->query());
    }

    public function getInstantSearchResult(string $query, int $limit = 8): Collection
    {
        return Product::with(['variants', 'category', 'brand'])
             ->where(function($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%')
                  ->orWhere('description', 'LIKE', '%' . $query . '%');
            })
            ->orWhereHas('brand', function ($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%');
            })
            ->orWhereHas('category', function ($q) use ($query) {
                $q->where('name', 'LIKE', '%' . $query . '%');
            })
            ->orderBy('name', 'asc')
            ->take($limit)
            ->get();
    }

    public function createProductWithVariants(array $productData, array $variantsData): Product
    {
        return DB::transaction(function () use ($productData, $variantsData) {
            
            $product = Product::create($productData);

            foreach ($variantsData as $variant) {
                $variant['product_id'] = $product->id;
                $product->variants()->create($variant);
            }

            return $product;
        });
    }

    /**
     * Update Product dengan Logika Cerdas (Smart Update)
     * Mencegah Error Foreign Key jika varian sudah pernah terjual.
     */
    public function updateProductWithVariants(Product $product, array $productData, array $variantsData): Product
    {
        return DB::transaction(function () use ($product, $productData, $variantsData) {
            
            // 1. Update Induk Produk
            $product->update($productData);

            // 2. Identifikasi ID Varian yang dikirim dari Form (Yang ingin dipertahankan/diupdate)
            $submittedVariantIds = collect($variantsData)
                ->pluck('id')
                ->filter() // Ambil yang punya ID saja (bukan null/baru)
                ->toArray();

            // 3. Hapus Varian Lama yang TIDAK ADA di Form
            // Menggunakan try-catch agar jika varian terkunci oleh order (Foreign Key), sistem tidak crash.
            try {
                if (!empty($submittedVariantIds)) {
                    // Hapus varian milik produk ini yang ID-nya TIDAK ADA di list form
                    $product->variants()->whereNotIn('id', $submittedVariantIds)->delete();
                } else {
                    // Kasus langka: Jika user menghapus SEMUA varian di form, coba hapus semua di DB
                    // (Ini mungkin akan gagal jika ada order, tapi aman karena try-catch)
                   // $product->variants()->delete(); 
                   // Note: Biasanya kita memaksa minimal 1 varian di Frontend, jadi blok else ini jarang kena.
                }
            } catch (\Exception $e) {
                // Silent fail: Jika varian tidak bisa dihapus karena sudah ada transaksi (order_items),
                // biarkan saja varian itu tetap ada di database (orphan/non-aktif) demi integritas data.
                // Log::warning("Gagal menghapus varian produk ID {$product->id}: " . $e->getMessage());
            }

            // 4. Loop Data Varian: Update Existing atau Create New
            foreach ($variantsData as $variant) {
                if (isset($variant['id']) && $variant['id']) {
                    // --- UPDATE VARIANT LAMA ---
                    $product->variants()->where('id', $variant['id'])->update([
                        'volume' => $variant['volume'],
                        'price' => $variant['price'],
                        'stock' => $variant['stock'], // Pastikan 'stock' (English)
                        'sku' => $variant['sku'],
                        // Image URL hanya diupdate jika ada perubahan di Service Layer (tidak null)
                        'image_url' => $variant['image_url'] ?? null, 
                    ]);
                } else {
                    // --- CREATE VARIANT BARU ---
                    $variant['product_id'] = $product->id;
                    $product->variants()->create($variant);
                }
            }

            return $product;
        });
    }

    public function delete(Product $product): bool
    {
        $product->variants()->delete();
        
        return $product->delete();
    }
}