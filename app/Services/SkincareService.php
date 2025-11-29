<?php

namespace App\Services;

use App\Models\User;
use App\Models\SkincareRoutine;
use App\Models\RoutineCompletion;
use App\Models\PointTransaction;
use App\Models\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SkincareService
{
    protected PointService $pointService;
    protected FcmService $fcmService;

    public function __construct(PointService $pointService, FcmService $fcmService)
    {
        $this->pointService = $pointService;
        $this->fcmService = $fcmService;
    }

    /**
     * Get All Routines for Dashboard
     */
    public function getUserRoutines(User $user)
    {
        $today = now()->day;
        
        return SkincareRoutine::where('user_id', $user->id)
            ->with(['product.variants', 'currentMonthCompletion'])
            ->orderByRaw('CASE WHEN reminder_time IS NULL THEN 1 ELSE 0 END, reminder_time ASC, step_order ASC')
            ->get()
            ->map(function ($routine) use ($today) {
                $completedDays = $routine->currentMonthCompletion->completed_days ?? [];
                
                $imageUrl = null;
                if ($routine->product && $routine->product->variants->isNotEmpty()) {
                    $imageUrl = $routine->product->variants->first()->image_url;
                }

                return [
                    'id' => $routine->id,
                    'name' => $routine->product ? $routine->product->name : $routine->custom_product_name,
                    'image_url' => $imageUrl,
                    'note' => $routine->note,
                    'step_order' => $routine->step_order,
                    'reminder_time' => $routine->reminder_time, 
                    'is_reminder_active' => (bool) $routine->is_reminder_active,
                    'repeat_frequency' => $routine->repeat_frequency,
                    'product_id' => $routine->product_id,
                    'custom_product_name' => $routine->custom_product_name,
                    'brand_name' => $routine->product?->brand?->name,
                    'is_completed_today' => in_array($today, $completedDays),
                ];
            });
    }

    /**
     * Get Store Products (Dropdown)
     */
    public function getStoreProducts()
    {
        return Product::with(['variants', 'brand'])
            ->select('id', 'name', 'brand_id')
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'brand_name' => $product->brand ? $product->brand->name : null,
                    'image_url' => $product->variants->first()->image_url ?? null,
                ];
            });
    }

    /**
     * Create Routines (Handle Timezone Conversion)
     */
    public function createRoutine(User $user, array $data): void
    {
        $times = $data['reminder_times'] ?? [null];
        if (empty($data['is_reminder_active']) || empty($times)) {
            $times = [null];
        }

        DB::transaction(function () use ($user, $data, $times) {
            foreach ($times as $timeInput) {
                $finalTime = $this->convertTimeInput($timeInput, $data['timezone_input'] ?? null);
                
                $period = 'am';
                if ($timeInput) {
                    $hour = (int) substr($timeInput, 0, 2);
                    $period = $hour >= 15 ? 'pm' : 'am';
                }

                SkincareRoutine::create([
                    'user_id' => $user->id,
                    'period' => $period,
                    'product_id' => $data['product_id'],
                    'custom_product_name' => $data['custom_product_name'],
                    'step_order' => $data['step_order'],
                    'note' => $data['note'],
                    'reminder_time' => $finalTime,
                    'is_reminder_active' => $data['is_reminder_active'],
                    'repeat_frequency' => $data['repeat_frequency']
                ]);
            }
        });
    }

    /**
     * Update Routine
     */
    public function updateRoutine(SkincareRoutine $routine, array $data): void
    {
        $finalTime = $routine->reminder_time; // Default keep old time

        if (!empty($data['is_reminder_active']) && !empty($data['reminder_time'])) {
            $finalTime = $this->convertTimeInput($data['reminder_time'], $data['timezone_input'] ?? null);
        } elseif (empty($data['is_reminder_active'])) {
            $finalTime = null;
        }

        $routine->update([
            'product_id' => $data['product_id'],
            'custom_product_name' => $data['custom_product_name'],
            'step_order' => $data['step_order'],
            'note' => $data['note'],
            'reminder_time' => $finalTime,
            'is_reminder_active' => $data['is_reminder_active'],
            'repeat_frequency' => $data['repeat_frequency']
        ]);
    }

    /**
     * Toggle Check (Complete/Uncomplete)
     */
    public function toggleCompletion(User $user, int $routineId): void
    {
        $routine = SkincareRoutine::where('user_id', $user->id)->findOrFail($routineId);
        $todayDate = now();
        $currentMonth = $todayDate->format('Y-m');
        $currentDay = $todayDate->day;

        $log = RoutineCompletion::firstOrCreate(
            ['user_id' => $user->id, 'skincare_routine_id' => $routine->id, 'year_month' => $currentMonth],
            ['completed_days' => []]
        );

        $days = $log->completed_days ?? [];
        $justCompleted = false; 

        if (in_array($currentDay, $days)) {
            // Uncheck (Remove from array)
            $days = array_values(array_diff($days, [$currentDay]));
        } else {
            // Check (Add to array)
            $days[] = $currentDay;
            sort($days);
            $justCompleted = true; 
        }
        $log->update(['completed_days' => $days]);

        if ($justCompleted) {
            $this->checkAndAwardDailyBonus($user);
        }
    }

    /**
     * Helper: Convert User Input Time to UTC Database Time
     */
    private function convertTimeInput(?string $timeInput, ?string $timezone): ?string
    {
        if (!$timeInput || !$timezone) return null;

        try {
            $userTime = Carbon::createFromFormat('H:i', $timeInput, $timezone);
            $userTime->setTimezone('UTC');
            return $userTime->format('H:i:s');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Helper: Check if all routines completed & give bonus
     */
    private function checkAndAwardDailyBonus(User $user): void
    {
        // 1. Check if already awarded today
        $alreadyAwarded = PointTransaction::where('user_id', $user->id)
            ->where('source_type', 'routine_daily')
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($alreadyAwarded) return;

        // 2. Count Total Routines
        $allUserRoutines = SkincareRoutine::where('user_id', $user->id)->get();
        if ($allUserRoutines->isEmpty()) return;

        // 3. Count Completed Routines
        $todayDay = now()->day;
        $completedCount = 0;
        
        foreach ($allUserRoutines as $rt) {
            $log = $rt->currentMonthCompletion; 
            if ($log && in_array($todayDay, $log->completed_days ?? [])) {
                $completedCount++;
            }
        }

        // 4. Award if 100%
        if ($completedCount >= $allUserRoutines->count()) {
            
            // Give Points
            $this->pointService->addPoints(
                $user, 
                5, 
                'routine_daily', 
                "Daily Routine Bonus (" . now()->format('Y-m-d') . ")"
            );

            // Send Notif
            try {
                $this->fcmService->sendToUser(
                    $user->id,
                    "Routine Complete! 🎉",
                    "Great job! You earned +5 Points today.",
                    "/rewards" 
                );
            } catch (\Exception $e) {
                Log::error("Failed sending daily bonus notif: " . $e->getMessage());
            }

            // Flash Message for Frontend (Optional: handled via session in controller)
            session()->flash('points_awarded', 'Rutinitas Selesai! +5 Poin.');
        }
    }
}