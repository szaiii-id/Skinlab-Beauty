<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoutineRequest;
use App\Models\SkincareRoutine;
use App\Services\SkincareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class SkincareRoutineController extends Controller
{
    protected SkincareService $skincareService;

    // Inject SkincareService
    public function __construct(SkincareService $skincareService)
    {
        $this->skincareService = $skincareService;
    }

    public function index(): Response
    {
        $user = Auth::user();

        // Fetch Data via Service
        $routines = $this->skincareService->getUserRoutines($user);
        $storeProducts = $this->skincareService->getStoreProducts();

        return Inertia::render('SkincareRoutine/Index', [
            'routines' => $routines,
            'storeProducts' => $storeProducts
        ]);
    }

    public function store(StoreRoutineRequest $request): RedirectResponse
    {
        $this->skincareService->createRoutine(Auth::user(), $request->validated());
        return back()->with('success', 'Routine added successfully!');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        // Simple validation specific for update (can be moved to UpdateRequest if needed)
        $request->validate([
            'step_order' => 'required|integer|min:1',
            'repeat_frequency' => 'required|integer|min:1',
            'is_reminder_active' => 'boolean'
        ]);

        $routine = SkincareRoutine::where('user_id', Auth::id())->findOrFail($id);
        
        $this->skincareService->updateRoutine($routine, $request->all());

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

        return back()->with('success', 'All schedules for this product deleted.');
    }

    /**
     * Toggle Check (Complete/Uncomplete)
     */
    public function toggleCheck($id): RedirectResponse
    {
        $this->skincareService->toggleCompletion(Auth::user(), (int) $id);
        return back();
    }
}