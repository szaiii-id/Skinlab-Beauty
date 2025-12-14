<?php

namespace App\Services;

use App\Models\User;
use App\Services\RewardService;
use App\Services\FcmService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerService
{
    protected RewardService $rewardService;
    protected FcmService $fcmService;

    public function __construct(RewardService $rewardService, FcmService $fcmService)
    {
        $this->rewardService = $rewardService;
        $this->fcmService = $fcmService;
    }

    /**
     * Get filtered users dengan logic baru
     */
    public function getFilteredUsers($filter = 'all', $search = null)
    {
        $query = User::query();
        
        // === FILTER LOGIC ===
        if ($filter === 'sleeping_beauty') {
            // User TIDAK order dalam 90 hari terakhir
            $query->whereDoesntHave('orders', function($q) {
                $q->where('created_at', '>=', now()->subDays(90));
            });
        } 
        elseif ($filter === 'loyal_queen') {
            // User dengan spend ≥2jt ATAU completed order ≥5
            $query->whereHas('orders', function($q) {
                $q->select('user_id')
                  ->where('order_status', 'completed')
                  ->groupBy('user_id')
                  ->havingRaw('SUM(total_amount) >= 2000000 OR COUNT(*) >= 5');
            });
        }
        elseif ($filter === 'first_time_buyers') {
            // 🌱 User pertama kali belanja dalam 30 hari
            $query->whereHas('orders', function($q) {
                $q->select('user_id')
                  ->where('order_status', 'completed')
                  ->groupBy('user_id')
                  ->havingRaw('COUNT(*) = 1') // Hanya 1 transaksi
                  ->havingRaw('MAX(created_at) >= ?', [now()->subDays(30)]);
            });
        }
        // 'all' → no filter
        
        // === SEARCH ===
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // === ORDER/SORTING LOGIC ===
        if ($filter === 'all') {
            // DEFAULT: Sort by Last Order Date (aktifitas terkini)
            $query->orderByRaw('
                COALESCE(
                    (SELECT MAX(created_at) FROM orders 
                     WHERE user_id = users.id AND order_status = "completed"),
                    users.created_at
                ) DESC
            ');
        } else {
            // Untuk filter lain: sort by created_at (default)
            $query->latest();
        }
        
        // === GET RESULTS ===
        $users = $query->with(['orders' => function($q) {
            $q->where('order_status', 'completed');
        }])->paginate(10);
        
        // === MANUAL CALCULATION (untuk frontend) ===
        $users->getCollection()->transform(function($user) {
            $completedOrders = $user->orders->where('order_status', 'completed');
            
            $user->orders_count = $user->orders->count();
            $user->completed_orders_count = $completedOrders->count();
            $user->orders_sum_total_amount = $completedOrders->sum('total_amount');
            
            // Hitung last order date untuk display
            $lastOrder = $user->orders->sortByDesc('created_at')->first();
            $user->last_order_date = $lastOrder ? $lastOrder->created_at : null;
            
            return $user;
        });
        
        return $users;
    }
    
    /**
     * Get filtered users optimized
     */
    public function getFilteredUsersOptimized($filter = 'all', $search = null)
    {
        // 1. Subquery Statistics
        $statsSub = DB::table('orders')
            ->select([
                'user_id',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(CASE WHEN order_status = "completed" THEN 1 ELSE 0 END) as completed_count'),
                DB::raw('SUM(CASE WHEN order_status = "completed" THEN total_amount ELSE 0 END) as total_spent'),
                DB::raw('MAX(CASE WHEN order_status = "completed" THEN created_at END) as last_order_date')
            ])
            ->groupBy('user_id');
        
        $query = User::select([
                'users.*',
                DB::raw('COALESCE(os.total_orders, 0) as orders_count'),
                DB::raw('COALESCE(os.completed_count, 0) as completed_orders_count'),
                DB::raw('COALESCE(os.total_spent, 0) as orders_sum_total_amount'),
                DB::raw('os.last_order_date')
            ])
            ->leftJoinSub($statsSub, 'os', function($join) {
                $join->on('users.id', '=', 'os.user_id');
            });
        
        // === 2. FILTER LOGIC UTAMA ===
        
        if ($filter === 'banned') {
            // KHUSUS Filter Banned: Tampilkan HANYA yang di-banned
            $query->where('users.is_banned', true);
        } 
        else {
            // SEMUA Filter Lain (All, Loyal, Sleeping, dll):
            // Tampilkan HANYA yang TIDAK di-banned (User Aktif)
            $query->where('users.is_banned', false);
            
            // Logic filter tambahan untuk kategori user aktif
            if ($filter === 'sleeping_beauty') {
                $query->where(function($q) {
                    $q->whereNull('os.last_order_date')
                      ->orWhere('os.last_order_date', '<', now()->subDays(90));
                });
            }
            elseif ($filter === 'loyal_queen') {
                $query->where(function($q) {
                    $q->where('os.total_spent', '>=', 2000000)
                      ->orWhere('os.completed_count', '>=', 5);
                });
            }
            elseif ($filter === 'first_time_buyers') {
                $query->where('os.completed_count', '=', 1)
                      ->where('os.last_order_date', '>=', now()->subDays(30));
            }
        }
        
        // === 3. SEARCH ===
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                  ->orWhere('users.email', 'like', "%{$search}%");
            });
        }
        
        // === 4. ORDER BY ===
        if ($filter === 'all') {
            // Sort by activity terkini
            $query->orderByRaw('COALESCE(os.last_order_date, users.created_at) DESC');
        } else {
            $query->orderBy('users.created_at', 'desc');
        }
        
        return $query->paginate(10);
    }
    
    /**
     * Send gift to users (Updated with Duplicate Check)
     */
    public function sendGiftToUsers(array $userIds, int $rewardId)
    {
        $successCount = 0;
        $failedUsers = [];
        $skippedCount = 0; // Hitung yang dilewati
        
        foreach ($userIds as $userId) {
            // Kita tidak pakai Transaction di luar loop agar jika 1 gagal, yang lain tetap jalan
            // Transaction dipindah ke dalam try block per user
            
            try {
                $user = User::find($userId);
                
                // 1. Cek User Exist & Banned
                if (!$user || $user->is_banned) {
                    $failedUsers[] = "User #{$userId}: Not found or Banned";
                    continue;
                }

                // 2. CEK DUPLIKAT (VALIDASI LOGIC)
                // Cek apakah user sudah punya voucher ini dan belum dipakai (atau atur logic sesuai kebutuhan)
                $alreadyHas = DB::table('user_rewards')
                    ->where('user_id', $user->id)
                    ->where('reward_id', $rewardId)
                    // ->where('status', 'unused') // Opsional: jika ingin membolehkan kirim lagi kalau yang lama sudah dipakai
                    ->exists();

                if ($alreadyHas) {
                    $skippedCount++;
                    continue;
                }
                
                // Mulai Transaksi Database per User
                DB::beginTransaction();

                // 3. Proses Claim
                $voucher = $this->rewardService->claimReward($user, $rewardId);
                
                // 4. Kirim Notifikasi
                $user->notify(new \App\Notifications\GiftReceivedNotification(
                    $voucher->reward->name, 
                    $voucher->code
                ));
                
                // 5. Kirim FCM
                $this->fcmService->sendToUser(
                    $user->id, 
                    "🎁 Surprise Gift!", 
                    "You received {$voucher->reward->name}. Check your voucher code now!", 
                    '/rewards'
                );
                
                DB::commit();
                $successCount++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $failedUsers[] = "User #{$userId} Error: " . $e->getMessage();
                Log::error("Gift Error User {$userId}: " . $e->getMessage());
            }
        }
        
        return [
            'success' => $successCount,
            'failed' => count($failedUsers), // Total gagal + skipped
            'skipped' => $skippedCount,      // Spesifik yang skipped
            'failed_details' => $failedUsers
        ];
    }

}