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

    public function __construct(RewardService $rewardService)
    {
        $this->rewardService = $rewardService;
    }

    /**
     * Halaman Utama Reward Center
     */
    public function index(): Response
    {
        $user = Auth::user();
        $user->load('membership'); 
        $userTier = $user->membership ? $user->membership->tier : 'Bronze';

        return Inertia::render('Rewards/Index', [
            'points' => $user->current_points,
            'tier' => $userTier,
            'rewards' => $this->rewardService->getCatalog(),
            'my_vouchers' => $this->rewardService->getUserVouchers($user->id),
            'history' => $this->rewardService->getPointHistory($user->id)
        ]);
    }

    /**
     * Proses Tukar Poin
     */
    public function redeem(Request $request, $id): RedirectResponse
    {
        try {
            $this->rewardService->redeemReward(Auth::user(), (int) $id);
            
            return redirect()->back()->with('success', 'Berhasil! Voucher ditambahkan.');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}