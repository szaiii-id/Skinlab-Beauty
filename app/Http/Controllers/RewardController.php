<?php

namespace App\Http\Controllers;

use App\Services\RewardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RewardController extends Controller
{
    protected RewardService $rewardService;

    // Inject RewardService
    public function __construct(RewardService $rewardService)
    {
        $this->rewardService = $rewardService;
    }

    /**
     * Display Rewards Page
     */
    public function index(): Response
    {
        $user = Auth::user();

        // Fetch data via Service
        $catalog = $this->rewardService->getCatalog();
        $myVouchers = $this->rewardService->getUserVouchers($user->id);
        $history = $this->rewardService->getPointHistory($user->id);

        return Inertia::render('Rewards/Index', [
            'points' => $user->current_points,
            'rewards' => $catalog,
            'my_vouchers' => $myVouchers,
            'history' => $history
        ]);
    }

    /**
     * Process Redemption
     */
    public function redeem(Request $request, $id): RedirectResponse
    {
        try {
            $this->rewardService->redeemReward(Auth::user(), (int) $id);
            
            return redirect()->back()->with('toast_success', 'Redemption successful! Voucher added.');

        } catch (\Exception $e) {
            return redirect()->back()->with('toast_error', $e->getMessage());
        }
    }
}