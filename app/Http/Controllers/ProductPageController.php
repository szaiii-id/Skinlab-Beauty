<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

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
            'products' => ProductResource::collection($products)
        ]);
    }

    public function show(string $slug, int $id): Response|RedirectResponse
    {
        $productId = (int)$id;
        $product = $this->productService->getProductById($productId);

        if (!$product) {
            abort(404);
        }

        if ($product->slug !== $slug) {
            return redirect()->route('products.show', [
                'slug' => $product->slug,
                'id' => $product->id
            ]); 
        }

        return Inertia::render('Catalog/Show', [
            'product' => new ProductResource($product)
        ]);
    }
}