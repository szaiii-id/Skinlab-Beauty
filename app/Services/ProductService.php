<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserSkinProfile;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    private const CACHE_TAG_PRODUCTS = 'products';
    private const CACHE_TTL = 3600; // 1 Jam

    protected ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Generate unique cache key based on query parameters (page, sort, etc.)
     */
    private function getCacheKey(string $prefix): string
    {
        $queryParams = request()->query(); // Ambil semua ?page=1&sort=desc
        ksort($queryParams); // Urutkan parameter agar konsisten
        $queryString = http_build_query($queryParams);
        
        return "{$prefix}:{$queryString}";
    }

    public function getAllProducts(): LengthAwarePaginator 
    {
        // Cache Key contoh: "products:all:page=1"
        $cacheKey = $this->getCacheKey('products:all');

        return Cache::tags([self::CACHE_TAG_PRODUCTS])->remember(
            $cacheKey, 
            self::CACHE_TTL, 
            fn() => $this->productRepository->getAllProductsWithVariants()
        );
    }

    public function getProductById(int $id): ?\App\Models\Product
    {
        // Cache Key contoh: "product:detail:105"
        $cacheKey = "product:detail:{$id}";

        return Cache::tags([self::CACHE_TAG_PRODUCTS])->remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => $this->productRepository->findByIdWithVariants($id)
        );
    }

    public function getNewReleases(): Collection
    {
        return Cache::tags([self::CACHE_TAG_PRODUCTS])->remember(
            'products:new_releases',
            self::CACHE_TTL,
            fn() => $this->productRepository->getNewReleases()
        );
    }

    public function getBestSellers(): Collection
    {
        return Cache::tags([self::CACHE_TAG_PRODUCTS])->remember(
            'products:best_sellers',
            self::CACHE_TTL,
            fn() => $this->productRepository->getBestSellers()
        );
    }

    public function getProductsByCategoryId(int $categoryId): LengthAwarePaginator
    {
        $cacheKey = $this->getCacheKey("products:category:{$categoryId}");

        return Cache::tags([self::CACHE_TAG_PRODUCTS])->remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => $this->productRepository->getProductsByCategoryId($categoryId)
        );
    }

    public function getProductsByBrandId(int $brandId): LengthAwarePaginator
    {
        $cacheKey = $this->getCacheKey("products:brand:{$brandId}");

        return Cache::tags([self::CACHE_TAG_PRODUCTS])->remember(
            $cacheKey,
            self::CACHE_TTL,
            fn() => $this->productRepository->getProductsByBrandId($brandId)
        );
    }

    /**
     * SEARCH: DO NOT CACHE
     * Alasan: Kombinasi keyword user tidak terbatas. 
     * Jika di-cache, memori Redis akan penuh dengan sampah pencarian.
     */
    public function searchProducts(string $query): LengthAwarePaginator
    {
        return $this->productRepository->searchProducts($query);
    }

    public function getInstantSearchResult(string $query, int $limit = 8): Collection
    {
        // Direct DB untuk akurasi instan
        return $this->productRepository->getInstantSearchResult($query, $limit);
    }

    /**
     * [BARU] Logika Rekomendasi Personal
     * Di-cache per user selama 10 menit agar tidak membebani DB setiap refresh home.
     */
    public function getPersonalizedRecommendations(User $user): array
    {
        $cacheKey = "recommendations:user:{$user->id}";

        // Gunakan tag 'products' agar jika ada produk dihapus, cache ini bisa ikut di-flush (opsional)
        // atau biarkan expire sendiri dalam 10 menit (600 detik).
        return Cache::tags([self::CACHE_TAG_PRODUCTS])->remember($cacheKey, 600, function () use ($user) {
            $profile = UserSkinProfile::where('user_id', $user->id)->first();

            if (!$profile) {
                return ['products' => [], 'skin_type' => null];
            }

            // Query berat dipindah kesini
            $query = \App\Models\Product::with(['variants', 'category', 'brand'])
                ->whereJsonContains('suitability_tags', $profile->skin_type);

            if (!empty($profile->skin_concerns)) {
                $query->orWhere(function($q) use ($profile) {
                    foreach ($profile->skin_concerns as $concern) {
                        $q->orWhereJsonContains('suitability_tags', $concern);
                    }
                });
            }

            return [
                'products' => $query->inRandomOrder()->take(4)->get(),
                'skin_type' => $profile->skin_type
            ];
        });
    }

    // --- SEARCH (ELASTICSEARCH) ---
    public function searchForAdmin(string $query = '', int $perPage = 10): LengthAwarePaginator
    {
        if (empty($query)) {
            return Product::with(['category', 'brand'])
                ->withCount('variants')
                ->latest()
                ->paginate($perPage);
        }
        // Wildcard search agar pencarian lebih fleksibel
        return Product::search("*{$query}*")
            ->query(fn ($q) => $q->with(['category', 'brand'])->withCount('variants'))
            ->paginate($perPage);
    }

    // --- CREATE ---
    public function createProduct(array $data, ?UploadedFile $thumbnail, array $variants): Product
    {
        $disk = config('filesystems.default', 'public');

        // Handle Thumbnail
        if ($thumbnail) {
            $filename = Str::slug($data['name']) . '-' . Str::random(10) . '.' . $thumbnail->getClientOriginalExtension();
            // Simpan PATH saja ke database, bukan URL
            $path = $thumbnail->storeAs('products', $filename, $disk);
            $data['thumbnail'] = $path; 
        }

        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(4);

        // Handle Variants
        foreach ($variants as &$variant) {
            if (empty($variant['sku'])) {
                $variant['sku'] = 'SLB-' . date('y') . strtoupper(Str::random(5));
            }
            if (isset($variant['image_file']) && $variant['image_file'] instanceof UploadedFile) {
                $vFilename = $variant['sku'] . '-' . Str::random(6) . '.' . $variant['image_file']->getClientOriginalExtension();
                $vPath = $variant['image_file']->storeAs('products/variants', $vFilename, $disk);
                $variant['image_url'] = $vPath; // Simpan Path
            }
        }

        $product = $this->productRepository->createProductWithVariants($data, $variants);
        $this->invalidateCache();
        return $product;
    }

    public function updateProduct(int $id, array $data, ?UploadedFile $thumbnail, array $variants): Product
    {
        $product = Product::findOrFail($id);
        $disk = config('filesystems.default', 'public');

        // --- 1. HANDLE THUMBNAIL UPDATE ---
        if ($thumbnail) {
            // [BARU] Hapus gambar lama jika ada
            // Kita pakai getRawOriginal agar mendapatkan path murni (contoh: 'products/foto.jpg')
            // tanpa terpengaruh oleh Accessor URL (http://...)
            $oldThumbnail = $product->getRawOriginal('thumbnail');
            
            if ($oldThumbnail && Storage::disk($disk)->exists($oldThumbnail)) {
                Storage::disk($disk)->delete($oldThumbnail);
            }
            
            // Upload gambar baru
            $filename = Str::slug($data['name']) . '-' . Str::random(10) . '.' . $thumbnail->getClientOriginalExtension();
            $data['thumbnail'] = $thumbnail->storeAs('products', $filename, $disk);
        } else {
            unset($data['thumbnail']); // Jangan update jika tidak ada file baru
        }

        // --- 2. PROCESS VARIANTS ---
        foreach ($variants as &$variant) {
            
            // A. Auto SKU (Logika Lama - DIPERTAHANKAN)
            if (empty($variant['sku'])) {
                $variant['sku'] = 'SLB-' . date('y') . strtoupper(Str::random(5));
            }
            
            // B. Cek Duplikat SKU (Logika Lama - DIPERTAHANKAN)
            if (!isset($variant['id'])) {
                if (ProductVariant::where('sku', $variant['sku'])->exists()) {
                    $variant['sku'] = 'SLB-' . date('y') . strtoupper(Str::random(6));
                }
            }
            
            // C. Handle Variant Image
            if (isset($variant['image_file']) && $variant['image_file'] instanceof UploadedFile) {
                
                // [BARU] Hapus gambar varian lama jika ini adalah edit varian
                if (isset($variant['id'])) {
                    $existingVariant = ProductVariant::find($variant['id']);
                    
                    if ($existingVariant) {
                        $oldVariantImage = $existingVariant->getRawOriginal('image_url');
                        
                        if ($oldVariantImage && Storage::disk($disk)->exists($oldVariantImage)) {
                            Storage::disk($disk)->delete($oldVariantImage);
                        }
                    }
                }

                // Upload gambar baru
                $vFilename = $variant['sku'] . '-' . Str::random(6) . '.' . $variant['image_file']->getClientOriginalExtension();
                $vPath = $variant['image_file']->storeAs('products/variants', $vFilename, $disk);
                $variant['image_url'] = $vPath;
            }
        }

        // 3. Update Database via Repository
        $this->productRepository->updateProductWithVariants($product, $data, $variants);
        
        // 4. Bersihkan Cache
        $this->invalidateCache();

        return $product->fresh();
    }

    public function deleteProduct(int $id): bool
    {
        $product = Product::with('variants')->find($id);

        if (!$product) {
            return false;
        }

        $variantIds = $product->variants->pluck('id');
        $hasSales = DB::table('order_items')
            ->whereIn('product_variant_id', $variantIds)
            ->exists();

        $disk = config('filesystems.default', 'public');

        if ($hasSales) {
            
            $deleted = $this->productRepository->delete($product);
        
        } else {
            $thumbPath = $product->getRawOriginal('thumbnail');
            if ($thumbPath && Storage::disk($disk)->exists($thumbPath)) {
                Storage::disk($disk)->delete($thumbPath);
            }

            foreach ($product->variants as $variant) {
                $varPath = $variant->getRawOriginal('image_url');
                if ($varPath && Storage::disk($disk)->exists($varPath)) {
                    Storage::disk($disk)->delete($varPath);
                }
            }

            $product->variants()->forceDelete();
            $deleted = $product->forceDelete();
        }

        if ($deleted) {
            $this->invalidateCache();
        }

        return $deleted;
    }

    private function invalidateCache(): void
    {
        Cache::tags([self::CACHE_TAG_PRODUCTS])->flush();
    }
}