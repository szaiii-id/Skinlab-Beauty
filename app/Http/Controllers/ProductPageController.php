<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
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
}