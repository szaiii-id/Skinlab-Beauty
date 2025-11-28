<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Http\Resources\PromoBannerResource;
use App\Models\Product;
use App\Models\UserSkinProfile;
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
        $newReleases = $this->productService->getNewReleases();
        $bestSellers = $this->productService->getBestSellers();
        $promoBanners = $this->promoBannerService->getActiveBanners();

        $recommendedProducts = [];
        $userSkinType = null;

        if (Auth::check()) {
            $profile = UserSkinProfile::where('user_id', Auth::id())->first();

            if ($profile) {
                $userSkinType = $profile->skin_type;

                $query = Product::with(['variants', 'category', 'brand'])
                    ->whereJsonContains('suitability_tags', $profile->skin_type);

                if (!empty($profile->skin_concerns)) {
                    $query->orWhere(function($q) use ($profile) {
                        foreach ($profile->skin_concerns as $concern) {
                            $q->orWhereJsonContains('suitability_tags', $concern);
                        }
                    });
                }

                $recommendedProducts = $query->inRandomOrder()->take(4)->get();
            }
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