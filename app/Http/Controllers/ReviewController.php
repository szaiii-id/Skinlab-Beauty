<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    protected ReviewService $reviewService;

    // Inject ReviewService only
    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    /**
     * Store a newly created review in storage.
     */
    public function store(StoreReviewRequest $request): RedirectResponse
    {
        try {
            // Delegate logic to Service
            $this->reviewService->submitReview(Auth::user(), $request->validated());

            return back()->with('toast_success', 'Review submitted! Points added to your account.');

        } catch (\Exception $e) {
            // Handle Business Logic Errors (Duplicate, Not Completed, etc)
            return back()->with('toast_error', $e->getMessage());
        }
    }
}