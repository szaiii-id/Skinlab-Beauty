<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\PromoBanner;
use App\Services\BrandService;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class ProductPageController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    public function index(): Response
    {
        $products = $this->productService->getAllProducts();

        return Inertia::render('Catalog/Index', [
            'products' => ProductResource::collection($products),
            'filterTitle' => null 
        ]);
    }

    public function show(string $slug, string $id, Request $request): Response|RedirectResponse
    {
        $productId = (int)$id;
        $product = $this->productService->getProductById($productId);

        if (!$product) {
            abort(404);
        }

        if ($product->slug !== $slug) {
            return to_route('products.show', [
                'slug' => $product->slug,
                'id' => $product->id
            ]);
        }

        $wishlistItems = $request->session()->get('wishlist', []);

        return Inertia::render('Catalog/Show', [
            'product' => new ProductResource($product),
            'wishlistItems' => array_keys($wishlistItems)
        ]);
    }

    public function showByCategory(string $slug, CategoryService $categoryService): Response
    {
        $category = $categoryService->findBySlug($slug);
        if (!$category) {
            abort(404);
        }
        $products = $this->productService->getProductsByCategoryId($category->id);

        return Inertia::render('Catalog/Index', [
            'products' => ProductResource::collection($products),
            'filterTitle' => $category->name
        ]);
    }

    public function showByBrand(string $slug, BrandService $brandService): Response
    {
        $brand = $brandService->findBySlug($slug);
        if (!$brand) {
            abort(404);
        }   
        $products = $this->productService->getProductsByBrandId($brand->id);

        return Inertia::render('Catalog/Index', [
            'products' => ProductResource::collection($products),
            'filterTitle' => $brand->name
        ]);
    }

    public function search(Request $request): Response
    {
        try {
            $query = $request->input('q');
                        
            $products = $this->productService->searchProducts($query);

            return Inertia::render('Catalog/Index', [
                'products' => ProductResource::collection($products),
                'filterTitle' => 'Search results for "' . $query . '"'
            ]);
        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage());
            
            return Inertia::render('Catalog/Index', [
                'products' => ProductResource::collection([]),
                'filterTitle' => 'Search results for "' . $request->input('q') . '"'
            ]);
        }
    }


    public function promo($id)
    {
        // 1. Eager Load Lengkap (Logic ini SUDAH TERBUKTI BERHASIL di debug tadi)
        $banner = PromoBanner::with([
                'variants.product.category',
                'variants.product.brand',
                'variants.product.variants.promoBanners', // Vital untuk harga diskon
                'variants.product.reviews' 
            ])
            ->where('id', $id)
            ->where('is_active', true)
            ->firstOrFail();

        // 2. Ambil Produk Induk
        $products = $banner->variants
            ->map(fn($v) => $v->product)
            ->filter() // Hapus null
            ->unique('id')
            ->values(); // Reset index array

        // 3. KEMBALIKAN KE INERTIA (Hapus Debug JSON)
        return Inertia::render('Catalog/Index', [
            'products' => ProductResource::collection($products),
            'filterTitle' => $banner->title, 
            'bannerImage' => null 
        ]);
    }
}    