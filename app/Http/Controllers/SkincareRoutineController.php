<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SkincareRoutine;
use App\Models\RoutineCompletion;
use App\Models\PointTransaction; // [BARU] Import Model Transaksi Poin
use App\Services\PointService;   // [BARU] Import Service Poin
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class SkincareRoutineController extends Controller
{
    // [BARU] Property untuk Service
    protected $pointService;

    // [BARU] Inject PointService via Constructor
    public function __construct(PointService $pointService)
    {
        $this->pointService = $pointService;
    }

    public function index()
    {
        $user = Auth::user();
        $today = now()->day;

        // 1. Ambil Routine (Sort by Time)
        $routines = SkincareRoutine::where('user_id', $user->id)
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

        // 2. Produk Toko
        $storeProducts = Product::with(['variants', 'brand'])
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

        return Inertia::render('SkincareRoutine/Index', [
            'routines' => $routines,
            'storeProducts' => $storeProducts
        ]);
    }

    // --- FITUR MULTI-TIME STORE ---
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'custom_product_name' => 'nullable|string|max:255',
            'step_order' => 'required|integer|min:1',
            'note' => 'nullable|string|max:100',
            'is_reminder_active' => 'boolean',
            'repeat_frequency' => 'required|integer|min:1|max:30',
            'reminder_times' => 'array', 
            'reminder_times.*' => 'nullable|date_format:H:i',
            'timezone_input' => 'nullable|string',
        ]);

        $times = $validated['reminder_times'] ?? [null];
        if (!$validated['is_reminder_active'] || empty($times)) {
            $times = [null];
        }

        foreach ($times as $timeInput) {
            $finalTime = null;
            if ($validated['is_reminder_active'] && $timeInput && $request->timezone_input) {
                try {
                    $userTime = Carbon::createFromFormat('H:i', $timeInput, $request->timezone_input);
                    $userTime->setTimezone('UTC');
                    $finalTime = $userTime->format('H:i:s');
                } catch (\Exception $e) { $finalTime = null; }
            }

            $period = 'am';
            if ($timeInput) {
                $hour = (int) substr($timeInput, 0, 2);
                $period = $hour >= 15 ? 'pm' : 'am';
            }

            SkincareRoutine::create([
                'user_id' => Auth::id(),
                'period' => $period,
                'product_id' => $validated['product_id'],
                'custom_product_name' => $validated['custom_product_name'],
                'step_order' => $validated['step_order'],
                'note' => $validated['note'],
                'reminder_time' => $finalTime,
                'is_reminder_active' => $validated['is_reminder_active'],
                'repeat_frequency' => $validated['repeat_frequency']
            ]);
        }

        return redirect()->back()->with('success', 'Jadwal berhasil ditambahkan!');
    }

    // UPDATE
    public function update(Request $request, $id)
    {
        $routine = SkincareRoutine::where('user_id', Auth::id())->findOrFail($id);
        
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'custom_product_name' => 'nullable|string|max:255',
            'step_order' => 'required|integer|min:1',
            'note' => 'nullable|string|max:100',
            'reminder_time' => 'nullable|date_format:H:i',
            'is_reminder_active' => 'boolean',
            'timezone_input' => 'nullable|string',
            'repeat_frequency' => 'required|integer|min:1'
        ]);

        $finalTime = null;
        if ($validated['is_reminder_active'] && $validated['reminder_time'] && $request->timezone_input) {
            try {
                $userTime = Carbon::createFromFormat('H:i', $validated['reminder_time'], $request->timezone_input);
                $userTime->setTimezone('UTC');
                $finalTime = $userTime->format('H:i:s');
            } catch (\Exception $e) {}
        } elseif (!$validated['is_reminder_active']) {
            $finalTime = null;
        } else {
            $finalTime = $routine->reminder_time;
        }

        $routine->update([
            'product_id' => $validated['product_id'],
            'custom_product_name' => $validated['custom_product_name'],
            'step_order' => $validated['step_order'],
            'note' => $validated['note'],
            'reminder_time' => $finalTime,
            'is_reminder_active' => $validated['is_reminder_active'],
            'repeat_frequency' => $validated['repeat_frequency']
        ]);

        return redirect()->back()->with('success', 'Jadwal diperbarui!');
    }

    public function destroy($id)
    {
        SkincareRoutine::where('user_id', Auth::id())->findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Produk dihapus.');
    }

    // --- [DIUPDATE] TOGGLE CHECK DENGAN BONUS POIN ---
    public function toggleCheck($id)
    {
        $user = Auth::user();
        $routine = SkincareRoutine::where('user_id', $user->id)->findOrFail($id);
        $todayDate = now();
        $currentMonth = $todayDate->format('Y-m');
        $currentDay = $todayDate->day;

        $log = RoutineCompletion::firstOrCreate(
            ['user_id' => $user->id, 'skincare_routine_id' => $routine->id, 'year_month' => $currentMonth],
            ['completed_days' => []]
        );

        $days = $log->completed_days ?? [];
        $justCompleted = false; // Flag apakah user BARU SAJA menyelesaikannya

        if (in_array($currentDay, $days)) {
            // Uncheck (Hapus)
            $days = array_values(array_diff($days, [$currentDay]));
        } else {
            // Check (Tambah)
            $days[] = $currentDay;
            sort($days);
            $justCompleted = true; // User menambah centang
        }
        $log->update(['completed_days' => $days]);

        // [BARU] Cek Bonus Poin (Hanya jika user baru mencentang)
        if ($justCompleted) {
            $this->checkAndAwardDailyBonus($user);
        }

        return redirect()->back();
    }

    // --- [BARU] PRIVATE METHOD UNTUK CEK BONUS ---
    private function checkAndAwardDailyBonus($user)
    {
        // A. Cek apakah user SUDAH dapat bonus hari ini? (Supaya tidak dobel poin)
        $todayStr = now()->format('Y-m-d');
        $alreadyAwarded = PointTransaction::where('user_id', $user->id)
            ->where('source_type', 'routine_daily')
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($alreadyAwarded) {
            return; // Stop, sudah dapat jatah hari ini
        }

        // B. Hitung Total Rutinitas Aktif User
        $allUserRoutines = SkincareRoutine::where('user_id', $user->id)->get();
        if ($allUserRoutines->isEmpty()) return;

        // C. Hitung Berapa yang Sudah Selesai Hari Ini
        $currentMonth = now()->format('Y-m');
        $todayDay = now()->day;
        
        $completedCount = 0;
        foreach ($allUserRoutines as $rt) {
            // Cek log bulan ini
            $log = $rt->currentMonthCompletion; 
            if ($log && in_array($todayDay, $log->completed_days ?? [])) {
                $completedCount++;
            }
        }

        // D. Jika Selesai Semua (100%), Beri Poin
        if ($completedCount >= $allUserRoutines->count()) {
            
            // Panggil Service untuk tambah poin (+5 Poin)
            $this->pointService->addPoints(
                $user, 
                5, // Jumlah poin
                'routine_daily', 
                "Bonus Rutinitas Harian ($todayStr)"
            );

            // Kirim Flash Message khusus agar frontend tahu user dapat poin
            session()->flash('points_awarded', 'Selamat! Rutinitas lengkap. +5 Poin.');
        }
    }
}