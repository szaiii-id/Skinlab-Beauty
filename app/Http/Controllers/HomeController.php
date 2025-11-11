<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Http\Resources\PromoBannerResource;
use App\Services\ProductService;
use App\Services\PromoBannerService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    protected ProductService $productService;
    protected PromoBannerService $promoBannerService;

    public function __construct(ProductService $productService, PromoBannerService $promoBannerService)
    {
        $this->productService = $productService;
        $this->promoBannerService = $promoBannerService;
    }

    public function index(): Response
    {
        $newReleases = $this->productService->getNewReleases();
        $bestSellers = $this->productService->getBestSellers();
        $promoBanners = $this->promoBannerService->getActiveBanners();

        return Inertia::render('Home', [
            'newReleases' => ProductResource::collection($newReleases),
            'bestSellers' => ProductResource::collection($bestSellers),
            'promoBanners' => PromoBannerResource::collection($promoBanners),
        ]);
    }

}
