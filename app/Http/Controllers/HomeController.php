<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Http\Resources\PromoBannerResource;
use App\Services\ProductService;
use App\Services\PromoBannerService;
use Illuminate\Support\Facades\Auth;
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
        // 1. Ambil data global (Cached)
        $newReleases = $this->productService->getNewReleases();
        $bestSellers = $this->productService->getBestSellers();
        $promoBanners = $this->promoBannerService->getActiveBanners();

        // 2. Siapkan variabel rekomendasi
        $recommendedProducts = [];
        $userSkinType = null;

        // 3. Ambil rekomendasi personal (Cached per User di Service)
        if (Auth::check()) {
            $recommendationData = $this->productService->getPersonalizedRecommendations(Auth::user());
            
            $recommendedProducts = $recommendationData['products'];
            $userSkinType = $recommendationData['skin_type'];
        }

        return Inertia::render('Home', [
            'newReleases' => ProductResource::collection($newReleases),
            'bestSellers' => ProductResource::collection($bestSellers),
            'promoBanners' => PromoBannerResource::collection($promoBanners),
            'recommendedProducts' => ProductResource::collection($recommendedProducts),
            'userSkinType' => $userSkinType
        ]);
    }
}