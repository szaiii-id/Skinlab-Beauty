<?php
// app/Repositories/WhislistRepository.php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserWhislist;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class WhislistRepository
{
    public function getUserWishlist(int $userId): Collection
    {
        return Cache::remember("user_wishlist_{$userId}", 300, function () use ($userId) {
            return UserWhislist::where('user_id', $userId)
                ->with([
                    'productVariant.product' => function ($query) {
                        $query->select('id', 'name', 'slug', 'image_url', 'category_id')
                              ->with('category:id,name');
                    }
                ])
                ->select('id', 'user_id', 'product_variant_id', 'created_at')
                ->get();
        });
    }

    public function addToWishlist(int $userId, int $productVariantId): bool
    {
        $user = User::find($userId);
        
        if ($user->wishlist()->where('product_variant_id', $productVariantId)->exists()) {
            return false;
        }

        $user->wishlist()->attach($productVariantId);
        $this->clearUserWishlistCache($userId);

        return true;
    }

    public function removeFromWishlist(int $userId, int $productVariantId): bool
    {
        $user = User::find($userId);
        
        $deleted = $user->wishlist()->detach($productVariantId);

        if ($deleted > 0) {
            $this->clearUserWishlistCache($userId);
            return true;
        }

        return false;
    }

    public function isInWishlist(int $userId, int $productVariantId): bool
    {
        return UserWhislist::where('user_id', $userId)
            ->where('product_variant_id', $productVariantId)
            ->exists();
    }

    public function getWishlistCount(int $userId): int
    {
        return Cache::remember("wishlist_count_{$userId}", 300, function () use ($userId) {
            return UserWhislist::where('user_id', $userId)->count();
        });
    }

    public function getWishlistStatus(int $userId): array
    {
        return Cache::remember("wishlist_status_{$userId}", 300, function () use ($userId) {
            return UserWhislist::where('user_id', $userId)
                ->pluck('product_variant_id')
                ->toArray();
        });
    }

    private function clearUserWishlistCache(int $userId): void
    {
        Cache::forget("user_wishlist_{$userId}");
        Cache::forget("wishlist_count_{$userId}");
        Cache::forget("wishlist_status_{$userId}");
        
        $user = User::find($userId);
        if ($user) {
            $user->clearWishlistCache();
        }
    }
}