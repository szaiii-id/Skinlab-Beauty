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

    // --- FORMATTER DATA (AGAR GAMBAR MUNCUL) ---
    public function formatForFrontend(SkincareRoutine $routine)
    {
        $today = now()->day;
        $completedDays = $routine->currentMonthCompletion->completed_days ?? [];
        
        // Ambil Gambar (Prioritas Thumbnail Produk)
        $imageUrl = null;
        if ($routine->product) {
            $imageUrl = $routine->product->thumbnail;
        }

        return [
            'id' => $routine->id,
            'name' => $routine->product ? $routine->product->name : $routine->custom_product_name,
            'image_url' => $imageUrl, // Kirim URL gambar
            'note' => $routine->note,
            'step_order' => $routine->step_order,
            'reminder_time' => $routine->reminder_time,
            'is_reminder_active' => (bool) $routine->is_reminder_active,
            'repeat_frequency' => $routine->repeat_frequency,
            'product_id' => $routine->product_id,
            'custom_product_name' => $routine->custom_product_name,
            
            // INFO TAMBAHAN (AGAR CARD TIDAK KOSONG)
            'brand_name' => $routine->product?->brand?->name,
            'category_name' => $routine->product?->category?->name,
            'tags' => $routine->product?->suitability_tags ?? [],

            'is_completed_today' => in_array($today, $completedDays),
        ];
    }

    public function createRoutine(User $user, array $data): void
    {
        $times = array_filter($data['reminder_times'] ?? []);
        if (empty($times)) $times = [null]; // Minimal 1 slot kosong

        DB::transaction(function () use ($user, $data, $times) {
            foreach ($times as $timeInput) {
                $this->insertSingleRoutine($user, $data, $timeInput);
            }
        });
    }

    // LOGIC BARU: UPDATE GROUP (Hapus Lama -> Buat Baru)
    public function updateRoutineGroup(User $user, int $originalId, array $data): void
    {
        DB::transaction(function () use ($user, $originalId, $data) {
            // 1. Cari Routine Asli untuk tahu Product ID nya
            $original = SkincareRoutine::where('user_id', $user->id)->findOrFail($originalId);
            
            // 2. Hapus SEMUA jadwal produk tersebut (agar bersih)
            $query = SkincareRoutine::where('user_id', $user->id);
            if ($original->product_id) {
                $query->where('product_id', $original->product_id);
            } else {
                $query->where('custom_product_name', $original->custom_product_name);
            }
            $query->delete();

            // 3. Buat Ulang Jadwal Baru (Sesuai input User)
            $times = array_filter($data['reminder_times'] ?? []);
            if (empty($times)) $times = [null];

            foreach ($times as $timeInput) {
                $this->insertSingleRoutine($user, $data, $timeInput);
            }
        });
    }

    // Helper Insert
    private function insertSingleRoutine($user, $data, $timeInput)
    {
        $finalTime = $this->convertTimeInput($timeInput, $data['timezone_input'] ?? null);
        $period = $timeInput && (int)substr($timeInput, 0, 2) >= 15 ? 'pm' : 'am';

        SkincareRoutine::create([
            'user_id' => $user->id,
            'period' => $period,
            'product_id' => $data['product_id'],
            'custom_product_name' => $data['custom_product_name'],
            'step_order' => $data['step_order'],
            'note' => $data['note'],
            'reminder_time' => $finalTime,
            'is_reminder_active' => $data['is_reminder_active'] ?? false,
            'repeat_frequency' => $data['repeat_frequency']
        ]);
    }

    public function updateRoutine(SkincareRoutine $routine, array $data): void
    {
        $finalTime = $routine->reminder_time; 

        // Logic Update Waktu
        if (!empty($data['reminder_time'])) {
             $finalTime = $this->convertTimeInput($data['reminder_time'], $data['timezone_input'] ?? null);
        } elseif (isset($data['is_reminder_active']) && !$data['is_reminder_active']) {
             $finalTime = null;
        }

        $routine->update([
            'step_order' => $data['step_order'],
            'note' => $data['note'],
            'reminder_time' => $finalTime,
            'is_reminder_active' => $data['is_reminder_active'] ?? false,
            'repeat_frequency' => $data['repeat_frequency']
        ]);
    }

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
            $days = array_values(array_diff($days, [$currentDay]));
        } else {
            $days[] = $currentDay;
            sort($days);
            $justCompleted = true; 
        }
        
        $log->completed_days = $days;
        $log->save(); // SAVE STATUS CENTANG

        if ($justCompleted) {
            $this->checkAndAwardDailyBonus($user);
        }
    }

    // --- HELPERS ---
    private function convertTimeInput(?string $timeInput, ?string $timezone): ?string
    {
        if (!$timeInput) return null;
        try {
            $tz = $timezone ?? 'Asia/Jakarta';
            $userTime = Carbon::createFromFormat('H:i', substr($timeInput, 0, 5), $tz);
            $userTime->setTimezone('UTC');
            return $userTime->format('H:i:s');
        } catch (\Exception $e) { return null; }
    }

    private function checkAndAwardDailyBonus(User $user): void
    {
        $alreadyAwarded = PointTransaction::where('user_id', $user->id)
            ->where('source_type', 'routine_daily')
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($alreadyAwarded) return;

        $allUserRoutines = SkincareRoutine::where('user_id', $user->id)->get();
        if ($allUserRoutines->isEmpty()) return;

        $todayDay = now()->day;
        $completedCount = 0;
        
        foreach ($allUserRoutines as $rt) {
            $log = $rt->currentMonthCompletion ?? RoutineCompletion::where('skincare_routine_id', $rt->id)
                ->where('year_month', now()->format('Y-m'))
                ->first();
            if ($log && in_array($todayDay, $log->completed_days ?? [])) {
                $completedCount++;
            }
        }

        if ($completedCount >= $allUserRoutines->count()) {
            $this->pointService->addPoints(
                $user, 
                5, 
                'routine_daily', 
                "Daily Routine Bonus (" . now()->format('Y-m-d') . ")"
            );

            try {
                $this->fcmService->sendToUser(
                    $user->id,
                    "Routine Complete! 🎉",
                    "Great job! You earned +5 Points today.",
                    "/rewards" 
                );
            } catch (\Exception $e) {
                Log::error("FCM Error: " . $e->getMessage());
            }
        }
    }

    public function getUserRoutines(User $user)
    {
        $today = now()->day;
        
        return SkincareRoutine::with([
                'product' => function($query) {
                    $query->select('id', 'name', 'thumbnail', 'brand_id', 'category_id', 'suitability_tags'); 
                },
                'product.brand:id,name',       
                'product.category:id,name',
                'currentMonthCompletion'
            ])
            ->where('user_id', $user->id)
            ->orderBy('step_order', 'asc')
            ->orderBy('reminder_time', 'asc')
            ->get()
            ->map(function ($routine) use ($today) {
                $completedDays = $routine->currentMonthCompletion->completed_days ?? [];
                return [
                    'id' => $routine->id,
                    'name' => $routine->product ? $routine->product->name : $routine->custom_product_name,
                    'image_url' => $routine->product->thumbnail ?? null,
                    'note' => $routine->note,
                    'step_order' => $routine->step_order,
                    'reminder_time' => $routine->reminder_time, 
                    'is_reminder_active' => (bool) $routine->is_reminder_active,
                    'repeat_frequency' => $routine->repeat_frequency,
                    'product_id' => $routine->product_id,
                    'custom_product_name' => $routine->custom_product_name,
                    'brand_name' => $routine->product?->brand?->name,
                    'category_name' => $routine->product?->category?->name,
                    'tags' => $routine->product?->suitability_tags ?? [],
                    'is_completed_today' => in_array($today, $completedDays),
                ];
            });
    }
}