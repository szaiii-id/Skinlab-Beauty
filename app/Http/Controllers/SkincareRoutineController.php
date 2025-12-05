<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoutineRequest;
use App\Models\SkincareRoutine;
use App\Models\Product;
use App\Services\SkincareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class SkincareRoutineController extends Controller
{
    protected SkincareService $skincareService;

    public function __construct(SkincareService $skincareService)
    {
        $this->skincareService = $skincareService;
    }

    public function index(): Response
    {
        $user = Auth::user();

        // 1. AMBIL DATA ROUTINE (EAGER LOAD SEMUA RELASI)
        $routines = SkincareRoutine::with([
                'product.brand',        // Load Brand
                'product.category',     // Load Category
                'currentMonthCompletion' // Load Checklist
            ])
            ->where('user_id', $user->id)
            ->orderBy('step_order', 'asc')
            ->orderBy('reminder_time', 'asc')
            ->get()
            ->map(function ($routine) {
                // Format data agar siap dipakai Vue
                return $this->skincareService->formatForFrontend($routine);
            });

        // 2. DATA PRODUK UNTUK DROPDOWN SEARCH
        $storeProducts = Product::with('brand')
            ->select('id', 'name', 'thumbnail', 'brand_id')
            ->limit(50)
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'image_url' => $p->thumbnail, // Pastikan Accessor di Model Product jalan
                    'brand_name' => $p->brand ? $p->brand->name : null
                ];
            });

        return Inertia::render('SkincareRoutine/Index', [
            'routines' => $routines,
            'storeProducts' => $storeProducts
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Validasi Manual disini agar fleksibel
        $data = $request->validate([
            'product_id' => 'nullable|integer',
            'custom_product_name' => 'nullable|string',
            'step_order' => 'required|integer',
            'note' => 'nullable|string',
            'reminder_times' => 'array', // Terima Array
            'is_reminder_active' => 'boolean',
            'repeat_frequency' => 'required|integer',
            'timezone_input' => 'nullable|string'
        ]);

        $this->skincareService->createRoutine(Auth::user(), $data);
        return back()->with('success', 'Routine added successfully!');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => 'nullable|integer',
            'custom_product_name' => 'nullable|string',
            'step_order' => 'required|integer',
            'note' => 'nullable|string',
            'reminder_times' => 'array', // TERIMA ARRAY (Bukan single string)
            'is_reminder_active' => 'boolean',
            'repeat_frequency' => 'required|integer',
            'timezone_input' => 'nullable|string'
        ]);

        // Panggil Service khusus Update Group
        $this->skincareService->updateRoutineGroup(Auth::user(), $id, $data);

        return back()->with('success', 'Routine updated!');
    }

    public function destroy($id): RedirectResponse
    {
        SkincareRoutine::where('user_id', Auth::id())->findOrFail($id)->delete();
        return back()->with('success', 'Routine deleted.');
    }

    public function destroyGroup($id): RedirectResponse
    {
        $user = Auth::user();
        $target = SkincareRoutine::where('user_id', $user->id)->findOrFail($id);

        $query = SkincareRoutine::where('user_id', $user->id);
        if ($target->product_id) {
            $query->where('product_id', $target->product_id);
        } else {
            $query->where('custom_product_name', $target->custom_product_name);
        }
        $query->delete();

        return back()->with('success', 'All schedules deleted.');
    }

    public function toggleCheck($id): RedirectResponse
    {
        $this->skincareService->toggleCompletion(Auth::user(), (int) $id);
        return back();
    }
}