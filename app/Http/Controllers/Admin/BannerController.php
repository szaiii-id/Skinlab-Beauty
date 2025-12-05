<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PromoBannerService;
use App\Models\PromoBanner;
use App\Models\ProductVariant; // Import Model Varian
use Illuminate\Http\Request;
use Inertia\Inertia;

class BannerController extends Controller
{
    protected PromoBannerService $service;

    public function __construct(PromoBannerService $service) {
        $this->service = $service;
    }

    public function index()
    {
        // 1. Ambil Banner beserta relasi Varian
        $banners = PromoBanner::with('variants')->latest()->paginate(10);
        
        // 2. Transformasi data agar Vue mudah membaca ID dan Diskon yang tersimpan (Pivot)
        // Hasil: selected_variants = { "15": { "discount_type": "percent", "discount_value": 20 }, ... }
        $banners->getCollection()->transform(function ($b) {
            $b->selected_variants = $b->variants->mapWithKeys(function ($v) {
                return [$v->id => [
                    'discount_type' => $v->pivot->discount_type,
                    'discount_value' => $v->pivot->discount_value
                ]];
            });
            return $b;
        });

        // 3. Siapkan List Semua Varian untuk Pilihan di Modal
        // Kita gabungkan Nama Produk + Volume agar Admin tidak bingung
        // Contoh: "Somethinc Niacinamide (20ml)"
        $availableVariants = ProductVariant::with('product:id,name,thumbnail')
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'full_name' => $v->product ? ($v->product->name . ' (' . $v->volume . ')') : 'Unknown Product',
                    'price' => $v->price,
                    'thumbnail' => $v->product ? $v->product->thumbnail : null, // Pakai gambar induk
                ];
            });

        return Inertia::render('Admin/Banner/Index', [
            'banners' => $banners,
            'availableVariants' => $availableVariants
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image' => 'required|image|max:2048',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            
            // Validasi Array Varian & Diskon
            'variants' => 'nullable|array',
            'variants.*.id' => 'exists:product_variants,id',
            'variants.*.discount_type' => 'required|in:percent,fixed',
            'variants.*.discount_value' => 'required|integer|min:0',
        ]);

        // Format data untuk sync() Laravel: [ ID => ['col' => val], ... ]
        $syncData = [];
        if (!empty($request->variants)) {
            foreach ($request->variants as $item) {
                $syncData[$item['id']] = [
                    'discount_type' => $item['discount_type'],
                    'discount_value' => $item['discount_value'],
                ];
            }
        }

        $this->service->createBanner($data, $request->file('image'), $syncData);
        return redirect()->back()->with('success', 'Campaign created successfully.');
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048', // Gambar boleh kosong saat update
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            
            // Validasi Varian
            'variants' => 'nullable|array',
            'variants.*.id' => 'exists:product_variants,id',
            'variants.*.discount_type' => 'required|in:percent,fixed',
            'variants.*.discount_value' => 'required|integer|min:0',
        ]);

        // Format data sync
        $syncData = [];
        if (!empty($request->variants)) {
            foreach ($request->variants as $item) {
                $syncData[$item['id']] = [
                    'discount_type' => $item['discount_type'],
                    'discount_value' => $item['discount_value'],
                ];
            }
        }

        $this->service->updateBanner((int)$id, $data, $request->file('image'), $syncData);
        return redirect()->back()->with('success', 'Campaign updated successfully.');
    }

    public function destroy(string $id) 
    {
        $this->service->deleteBanner((int)$id);
        return redirect()->back()->with('success', 'Campaign deleted.');
    }
    
    public function toggle(string $id) 
    {
        $this->service->toggleActive((int)$id);
        return redirect()->back()->with('success', 'Status updated.');
    }
}