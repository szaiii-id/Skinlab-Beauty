<?php

namespace App\Http\Controllers;

use App\Models\Reward;
use App\Models\UserReward;
use App\Models\PointTransaction;
use App\Services\PointService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class RewardController extends Controller
{
    protected $pointService;

    public function __construct(PointService $pointService)
    {
        $this->pointService = $pointService;
    }

    public function index()
    {
        $user = Auth::user();

        return Inertia::render('Rewards/Index', [
            'points' => $user->current_points,
            
            // Katalog Hadiah
            'rewards' => Reward::where('is_active', true)
                ->where('stock', '>', 0)
                ->get(),
            
            // Voucher Saya (Belum Dipakai)
            'my_vouchers' => UserReward::with('reward')
                ->where('user_id', $user->id)
                ->where('is_used', false)
                ->latest()
                ->get(),
            
            // History Transaksi Poin
            'history' => PointTransaction::where('user_id', $user->id)
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($t) {
                    return [
                        'id' => $t->id,
                        'amount' => $t->amount,
                        'description' => $t->description,
                        'date' => $t->created_at->format('d M Y'),
                        'is_positive' => $t->amount > 0
                    ];
                })
        ]);
    }

    public function redeem(Request $request, $id)
    {
        try {
            $reward = Reward::findOrFail($id);
            $this->pointService->redeemReward(Auth::user(), $reward);
            
            return redirect()->back()->with('success', 'Berhasil! Voucher baru ditambahkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}