<?php

namespace App\Services;

use App\Models\PromoBanner;
use App\Repositories\PromoBannerRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PromoBannerService
{
    private const CACHE_KEY = 'promo_banners:active';
    private const CACHE_TTL = 3600; 
    
    protected PromoBannerRepository $repo;

    public function __construct(PromoBannerRepository $repo) 
    { 
        $this->repo = $repo; 
    }

    /**
     * ====================================================================
     * BAGIAN FRONTEND (USER)
     * ====================================================================
     */
    public function getActiveBanners() 
    {
        // Mengambil banner aktif dari cache untuk user
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->repo->getActiveBanners();
        });
    }

    /**
     * ====================================================================
     * BAGIAN BACKEND (ADMIN MANAGEMENT)
     * ====================================================================
     */

    // 1. GET LIST FOR TABLE
    public function getForAdmin(): LengthAwarePaginator 
    { 
        return $this->repo->getForDataTable(); 
    }

    // 2. CREATE (UPLOAD + SYNC VARIANTS)
    // Perhatikan parameter ke-3 sekarang $syncVariants (format pivot)
    public function createBanner(array $data, ?UploadedFile $image, array $syncVariants = []): PromoBanner
    {
        $disk = config('filesystems.default', 'public');

        // Upload Gambar
        if ($image) {
            $filename = 'banner-' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            // Simpan Path fisik
            $data['image_url'] = $image->storeAs('banners', $filename, $disk);
        }
        
        $data['is_active'] = true; // Default aktif

        // Simpan ke DB
        $banner = $this->repo->create($data);
        
        // Simpan Relasi VARIAN (Pivot)
        // $syncVariants format: [ ID_VARIAN => ['discount_type' => 'percent', 'discount_value' => 20], ... ]
        if (!empty($syncVariants)) {
            $banner->variants()->sync($syncVariants);
        }

        // Hapus Cache agar User lihat banner baru
        $this->invalidateCache();
        
        return $banner;
    }

    // 3. UPDATE (REPLACE IMAGE + SYNC VARIANTS)
    public function updateBanner(int $id, array $data, ?UploadedFile $image, array $syncVariants = []): PromoBanner
    {
        $banner = PromoBanner::findOrFail($id);
        $disk = config('filesystems.default', 'public');

        if ($image) {
            // --- GARBAGE COLLECTION ---
            // Hapus file lama dari storage agar tidak menumpuk
            $oldPath = $banner->getRawOriginal('image_url');
            if ($oldPath && Storage::disk($disk)->exists($oldPath)) {
                Storage::disk($disk)->delete($oldPath);
            }

            // Upload file baru
            $filename = 'banner-' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            $data['image_url'] = $image->storeAs('banners', $filename, $disk);
        } else {
            // Jika tidak upload baru, jangan update kolom image
            unset($data['image_url']);
        }

        // Update Data Utama
        $this->repo->update($banner, $data);
        
        // Update Relasi Varian (Sync otomatis hapus yg tidak dicentang dan update diskon)
        $banner->variants()->sync($syncVariants);

        $this->invalidateCache();
        
        return $banner->fresh();
    }

    // 4. DELETE (REMOVE FILE + DB)
    public function deleteBanner(int $id): bool
    {
        $banner = PromoBanner::findOrFail($id);
        $disk = config('filesystems.default', 'public');

        // Hapus File Fisik
        $path = $banner->getRawOriginal('image_url');
        if ($path && Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }

        // Hapus dari DB (Relasi pivot otomatis terhapus karena Cascade Delete di database)
        $deleted = $this->repo->delete($banner);
        
        $this->invalidateCache();
        
        return $deleted;
    }
    
    // 5. TOGGLE ACTIVE
    public function toggleActive(int $id): bool
    {
        $banner = PromoBanner::findOrFail($id);
        $updated = $this->repo->toggleActive($banner);
        
        $this->invalidateCache();
        
        return $updated;
    }

    // --- HELPER ---
    private function invalidateCache() 
    { 
        Cache::forget(self::CACHE_KEY); 
    }
}