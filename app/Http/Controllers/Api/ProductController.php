<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $products = $this->productService->getAllProducts();
        return ProductResource::collection($products)->response()->setStatusCode(200);

    }

    // /**
    //  * Store a newly created resource in storage.
    //  */
    // public function store(Request $request)
    // {
    //     //
    // }
    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);
        
        if(!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return (new ProductResource($product))->response()->setStatusCode(200);
    }
    //
     // /**
    //  * Update the specified resource in storage.
    //  */
    // public function update(Request $request, string $id)
    // {
    //     //
    // }
    // /**
    //  * Remove the specified resource from storage.
    //  */
    // public function destroy(string $id)
    // {
    //     //
    // }
}