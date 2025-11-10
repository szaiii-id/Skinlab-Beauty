<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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

        return Inertia::render('Products/Index', [
            'products' => ProductResource::collection($products)
        ]);
    }

    public function show(int $id): Response
    {
        $product = $this->productService->getProductById($id);

        if (!$product) {
            abort(404); // Gunakan abort(404) untuk halaman tidak ditemukan
        }

        return Inertia::render('Products/Show', [
            'product' => new ProductResource($product)
        ]);
    }
}