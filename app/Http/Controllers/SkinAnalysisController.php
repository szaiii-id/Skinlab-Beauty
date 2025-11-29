<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSkinAnalysisRequest;
use App\Http\Resources\ProductResource;
use App\Services\SkinAnalysisService;
use App\Models\UserSkinProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SkinAnalysisController extends Controller
{
    protected SkinAnalysisService $skinAnalysisService;

    // Inject Service
    public function __construct(SkinAnalysisService $skinAnalysisService)
    {
        $this->skinAnalysisService = $skinAnalysisService;
    }

    /**
     * Display analysis page and recommendations.
     */
    public function index(): Response
    {
        $user = Auth::user();
        
        // Fetch Profile
        $profile = UserSkinProfile::where('user_id', $user->id)->first();
        
        // Fetch Recommendations via Service
        $recommendedProducts = $this->skinAnalysisService->getRecommendations($user);

        return Inertia::render('SkinAnalysis/Index', [
            'existingProfile'     => $profile,
            'recommendedProducts' => ProductResource::collection($recommendedProducts) 
        ]);
    }

    /**
     * Process analysis submission.
     */
    public function store(StoreSkinAnalysisRequest $request): RedirectResponse
    {
        // Logic processing is delegated to Service
        $this->skinAnalysisService->analyzeAndSaveProfile(
            Auth::user(), 
            $request->validated()
        );

        return redirect()->back()->with('success', 'Skin analysis updated successfully!');
    }
}