<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Services\RewardService; // Inject Service untuk Clear Cache
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class RewardController extends Controller
{
    protected RewardService $rewardService;

    public function __construct(RewardService $rewardService)
    {
        $this->rewardService = $rewardService;
    }

    /**
     * Menampilkan Halaman Admin Reward
     */
    public function index()
    {
        return Inertia::render('Admin/Rewards/Index', [
            'rewards' => Reward::latest()->paginate(10)
        ]);
    }

    /**
     * Simpan Reward Baru
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048', // Validasi Gambar
            'points_required' => 'required|integer|min:0',
            
            // Validasi Logic Diskon
            'type' => 'required|in:fixed,percent,free_shipping',
            'value' => 'required|integer|min:0',
            'min_spend' => 'required|integer|min:0',
            
            // Validasi Stok & Aturan
            'stock' => 'required|integer|min:0',
            'max_per_user' => 'required|integer|min:0', // 0 = Unlimited
            'validity_days' => 'required|integer|min:1',
            
            // Toggles
            'is_claim_only' => 'boolean',
            'is_active' => 'boolean'
        ]);

        // Handle Image Upload
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('rewards', 'public');
        }

        Reward::create($data);

        // PENTING: Hapus Cache agar User melihat reward baru
        $this->rewardService->clearCatalogCache();

        return redirect()->back()->with('success', 'Reward created successfully.');
    }

    /**
     * Update Reward yang ada
     */
    public function update(Request $request, $id)
    {
        $reward = Reward::findOrFail($id);
        
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'points_required' => 'required|integer|min:0',
            'type' => 'required|in:fixed,percent,free_shipping',
            'value' => 'required|integer|min:0',
            'min_spend' => 'required|integer|min:0',
            'stock' => 'required|integer|min:0',
            'max_per_user' => 'required|integer|min:0',
            'validity_days' => 'required|integer|min:1',
            'is_claim_only' => 'boolean',
            'is_active' => 'boolean'
        ]);

        if ($request->hasFile('image')) {
            // Hapus gambar lama jika ada
            if ($reward->image) {
                Storage::disk('public')->delete($reward->image);
            }
            $data['image'] = $request->file('image')->store('rewards', 'public');
        }

        $reward->update($data);

        // Clear Cache setelah update (misal harga poin berubah)
        $this->rewardService->clearCatalogCache();

        return redirect()->back()->with('success', 'Reward updated successfully.');
    }

    /**
     * Hapus Reward
     */
    public function destroy($id)
    {
        $reward = Reward::findOrFail($id);
        
        if ($reward->image) {
            Storage::disk('public')->delete($reward->image);
        }
        
        $reward->delete();

        // Clear Cache setelah delete
        $this->rewardService->clearCatalogCache();
        
        return redirect()->back()->with('success', 'Reward deleted.');
    }
}