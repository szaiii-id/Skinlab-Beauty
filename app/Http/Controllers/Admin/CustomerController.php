<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\BanRequest;
use App\Services\CustomerService;
use App\Models\Reward;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Pastikan ini ada
use Inertia\Inertia;

class CustomerController extends Controller
{
    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            // PASTIKAN 'banned' ADA DI SINI
            'filter' => 'nullable|in:all,sleeping_beauty,loyal_queen,first_time_buyers,banned',
            'search' => 'nullable|string|max:255',
        ]);
        
        $users = $this->customerService->getFilteredUsersOptimized(
            $validated['filter'] ?? 'all',
            $validated['search'] ?? null
        );
        
        $giftRewards = Reward::where('is_claim_only', true)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->get(['id', 'name', 'stock', 'description']);
            
        $currentAdmin = Auth::guard('admin')->user();
        
        return Inertia::render('Admin/Customers/Index', [
            'users' => $users,
            'filters' => $request->only(['filter', 'search']),
            'giftRewards' => $giftRewards,
            'currentAdmin' => [
                'id' => $currentAdmin->id,
                'name' => $currentAdmin->name,
                'role' => $currentAdmin->role,
                'is_super_admin' => $currentAdmin->isSuperAdmin(),
                'can_request_ban' => $currentAdmin->canRequestBan()
            ]
        ]);
    }
    
    public function sendGift(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'reward_id' => 'required|exists:rewards,id',
        ]);
        
        try {
            $result = $this->customerService->sendGiftToUsers(
                $request->user_ids, 
                $request->reward_id
            );
            
            // KASUS 1: Sukses Sebagian atau Semua
            if ($result['success'] > 0) {

                $rewardObj = Reward::find($request->reward_id);
                $targetUsers = User::whereIn('id', $request->user_ids)->get();
                
                foreach ($targetUsers as $targetUser) {
                    $targetUser->notify(new \App\Notifications\GiftReceived($rewardObj));
                }

                $message = "🎁 Successfully sent to {$result['success']} users.";
                
                // Jika ada yang dilewati (sudah punya)
                if ($result['skipped'] > 0) {
                    $message .= " ({$result['skipped']} users skipped - already owned).";
                }
                
                return back()->with('success', $message);
            } 
            
            // KASUS 2: Gagal Semua (Mungkin karena semua user sudah punya)
            else if ($result['skipped'] > 0) {
                return back()->with('warning', "⚠️ No gifts sent. All selected users already have this reward.");
            }
            
            // KASUS 3: Error Sistem Lainnya
            else {
                return back()->with('error', '❌ Failed to send gifts. Check system logs.');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'System error: ' . $e->getMessage());
        }
    }

    // ============ BAN REQUEST METHOD ============
    
    public function requestBan(Request $request)
    {
        $currentAdmin = Auth::guard('admin')->user();
        
        if (!$currentAdmin->canRequestBan()) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to request bans.'
            ], 403);
        }
        
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'reason' => 'required|in:return_abuse,fraud,toxic_behavior,payment_issue,policy_violation,other',
            // PERBAIKAN 1: Ubah min:20 jadi min:5
            'description' => 'required|string|min:5|max:1000',
            'evidence_notes' => 'nullable|string|max:500'
        ]);
        
        if ($currentAdmin->isSuperAdmin()) {
            return $this->processDirectBan($validated, $currentAdmin);
        }
        
        return $this->createBanRequest($validated, $currentAdmin);
    }
    
    private function processDirectBan(array $data, Admin $admin)
    {
        DB::beginTransaction();
        try {
            $count = 0;
            $failed = [];
            
            foreach ($data['user_ids'] as $userId) {
                $user = User::find($userId);
                
                if (!$user) {
                    $failed[] = "User {$userId} not found";
                    continue;
                }
                
                if (!$user->canBeBanned()) {
                    $failed[] = "User {$userId} cannot be banned (already banned or has pending request)";
                    continue;
                }
                
                // Ban user langsung
                $user->ban($data['reason'], $admin);

                $user->notify(new \App\Notifications\AccountBanned($data['reason'], $data['description']));
                
                // PERBAIKAN 2: Hilangkan json_encode, kirim array murni
                $evidenceData = $data['evidence_notes'] ? ['notes' => $data['evidence_notes']] : null;

                BanRequest::create([
                    'user_id' => $userId,
                    'requested_by' => $admin->id,
                    'reviewed_by' => $admin->id,
                    'reason' => $data['reason'],
                    'description' => $data['description'],
                    'evidence' => $evidenceData,
                    'status' => BanRequest::STATUS_APPROVED,
                    'reviewed_at' => now(),
                    'review_notes' => 'Direct ban by super admin'
                ]);
                
                $count++;
            }
            
            DB::commit();
            
            // PERBAIKAN 3: Ganti Log channel custom jadi Log default
            Log::info('Direct ban executed', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'user_ids' => $data['user_ids'],
                'count' => $count
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "{$count} user(s) banned successfully",
                'count' => $count,
                'failed' => $failed
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Direct ban failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to ban users: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function createBanRequest(array $data, Admin $admin)
    {
        DB::beginTransaction();
        try {
            $count = 0;
            $failed = [];
            
            foreach ($data['user_ids'] as $userId) {
                $user = User::find($userId);
                
                if (!$user) {
                    $failed[] = "User {$userId} not found";
                    continue;
                }
                
                if (!$user->canBeBanned()) {
                    $failed[] = "User {$userId} cannot be banned (already banned or has pending request)";
                    continue;
                }
                
                // PERBAIKAN 2: Hilangkan json_encode
                $evidenceData = $data['evidence_notes'] ? ['notes' => $data['evidence_notes']] : null;

                BanRequest::create([
                    'user_id' => $userId,
                    'requested_by' => $admin->id,
                    'reason' => $data['reason'],
                    'description' => $data['description'],
                    'evidence' => $evidenceData,
                    'status' => BanRequest::STATUS_PENDING
                ]);
                
                $count++;
            }
            
            DB::commit();

            if ($count > 0) {
                $superAdmins = Admin::where('role', 'super_admin')->get();
                
                \Illuminate\Support\Facades\Notification::send($superAdmins, new \App\Notifications\NewBanRequestCreated($admin, $count));
            }
            
            // PERBAIKAN 3: Ganti Log channel custom jadi Log default
            Log::info('Ban requests submitted', [
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'admin_role' => $admin->role,
                'user_ids' => $data['user_ids'],
                'count' => $count
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "{$count} ban request(s) submitted for approval",
                'count' => $count,
                'failed' => $failed
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ban request creation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit ban request: ' . $e->getMessage()
            ], 500);
        }
    }

    public function unban(Request $request)
    {
        $currentAdmin = Auth::guard('admin')->user();

        // Hanya Super Admin yang boleh Unban
        if (!$currentAdmin->isSuperAdmin()) {
            return back()->with('error', 'Only Super Admin can unban users.');
        }

        $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        DB::beginTransaction();
        try {
            $count = 0;
            foreach ($request->user_ids as $userId) {
                $user = User::find($userId);
                
                // Cek apakah user memang sedang dibanned
                if ($user && $user->is_banned) {
                    
                    // 1. UPDATE USER (Aktifkan kembali)
                    $user->update([
                        'is_banned' => false,
                        'banned_at' => null,
                        'ban_reason' => null,
                        'banned_by' => null
                    ]);

                    $user->notify(new \App\Notifications\AccountRestored());

                    
                    // 2. UPDATE HISTORY BAN REQUEST (Best Practice)
                    // Cari request ban terakhir yang statusnya 'approved'
                    $lastBanRequest = BanRequest::where('user_id', $userId)
                        ->where('status', BanRequest::STATUS_APPROVED)
                        ->latest()
                        ->first();
                        
                    if ($lastBanRequest) {
                        // Jangan ubah status jadi 'rejected' (karena faktanya dulu sempat diapprove)
                        // Tapi tambahkan catatan di notes bahwa sudah di-unban
                        $newNote = $lastBanRequest->review_notes . "\n\n[SYSTEM LOG]: User Unbanned/Reactivated by {$currentAdmin->name} on " . now()->toDateTimeString();
                        
                        $lastBanRequest->update([
                            'review_notes' => $newNote
                        ]);
                    }

                    
                    $count++;
                }
            }

            DB::commit();

            if ($count > 0) {
                Log::info("{$count} users unbanned by {$currentAdmin->name}");
                return back()->with('success', "{$count} user(s) successfully reactivated!");
            } else {
                return back()->with('warning', 'No banned users were selected.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to unban users: ' . $e->getMessage());
        }
    }
}