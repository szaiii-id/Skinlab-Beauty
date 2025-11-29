<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class ReviewService
{
    protected PointService $pointService;
    protected FcmService $fcmService;

    public function __construct(PointService $pointService, FcmService $fcmService)
    {
        $this->pointService = $pointService;
        $this->fcmService = $fcmService;
    }

    /**
     * Submit a new review and grant rewards
     */
    public function submitReview(User $user, array $data): void
    {
        // 1. Business Logic Validation
        $this->validateReviewEligibility($user, $data['order_id'], $data['product_id']);

        // 2. Execute Transaction
        DB::transaction(function () use ($user, $data) {
            
            // A. Create Review Record
            $review = Review::create([
                'user_id'    => $user->id,
                'product_id' => $data['product_id'],
                'order_id'   => $data['order_id'],
                'rating'     => $data['rating'],
                'comment'    => $data['comment'] ?? null
            ]);

            // B. Grant Reward (10 Points)
            // We fetch product name for clearer point description
            $productName = $review->product->name ?? 'Product';
            
            $this->pointService->addPoints(
                $user,
                10,
                'review_reward',
                "Review Bonus: {$productName}"
            );

            // C. Send Notification (Async-like via Service)
            // We do this inside transaction or right after. 
            // If strictly inside, ensure FcmService handles exceptions so DB doesn't rollback on net error.
            try {
                $this->fcmService->sendToUser(
                    $user->id,
                    "Points Received! 🌟",
                    "Thanks for your review! You earned +10 Points.",
                    "/products/" . ($review->product->slug ?? '')
                );
            } catch (\Exception $e) {
                // Log only, do not fail the review submission
                Log::warning("Failed sending review notification: " . $e->getMessage());
            }
        });
    }

    /**
     * Check if user is allowed to review this product
     */
    private function validateReviewEligibility(User $user, int $orderId, int $productId): void
    {
        // Check 1: Is Order Completed?
        $order = Order::where('id', $orderId)
            ->where('user_id', $user->id)
            ->where('order_status', 'completed')
            ->first();

        if (!$order) {
            throw new Exception("Reviews are only allowed for completed orders.");
        }

        // Check 2: Is Duplicate?
        $exists = Review::where('user_id', $user->id)
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->exists();

        if ($exists) {
            throw new Exception("You have already reviewed this product for this order.");
        }
    }
}