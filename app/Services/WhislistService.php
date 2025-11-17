<?php

namespace App\Services;

use App\Repositories\WhislistRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhislistService
{
    protected $whislistRepository;

    public function __construct(WhislistRepository $whislistRepository)
    {
        $this->whislistRepository = $whislistRepository;
    }

    public function getUserWishlist(int $userId): array
    {
        try {
            $wishlistItems = $this->whislistRepository->getUserWishlist($userId);
            
            $formattedItems = $wishlistItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->productVariant->product->name,
                    'variant_volume' => $item->productVariant->volume,
                    'price' => $item->productVariant->price,
                    'image_url' => $item->productVariant->product->image_url,
                    'stock' => $item->productVariant->stock,
                    'product_slug' => $item->productVariant->product->slug,
                    'category_name' => $item->productVariant->product->category->name ?? '',
                    'created_at' => $item->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return [
                'success' => true,
                'data' => $formattedItems
            ];
        } catch (\Exception $e) {
            Log::error('WhislistService getUserWishlist error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to get wishlist',
                'data' => []
            ];
        }
    }

    public function addToWishlist(int $userId, int $productVariantId): array
    {
        DB::beginTransaction();
        
        try {
            $success = $this->whislistRepository->addToWishlist($userId, $productVariantId);
            
            if (!$success) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Item already in wishlist'
                ];
            }

            DB::commit();
            
            $wishlistCount = $this->whislistRepository->getWishlistCount($userId);
            
            return [
                'success' => true,
                'message' => 'Product added to wishlist successfully!',
                'wishlist_count' => $wishlistCount
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('WhislistService addToWishlist error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to add item to wishlist'
            ];
        }
    }

    public function removeFromWishlist(int $userId, int $productVariantId): array
    {
        DB::beginTransaction();
        
        try {
            $success = $this->whislistRepository->removeFromWishlist($userId, $productVariantId);
            
            if (!$success) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Item not found in wishlist'
                ];
            }

            DB::commit();
            
            $wishlistCount = $this->whislistRepository->getWishlistCount($userId);
            
            return [
                'success' => true,
                'message' => 'Product removed from wishlist',
                'wishlist_count' => $wishlistCount
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('WhislistService removeFromWishlist error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to remove item from wishlist'
            ];
        }
    }

    public function getWishlistStatus(int $userId): array
    {
        try {
            $wishlistItems = $this->whislistRepository->getWishlistStatus($userId);
            $wishlistCount = $this->whislistRepository->getWishlistCount($userId);
            
            return [
                'success' => true,
                'wishlist_items' => $wishlistItems,
                'wishlist_count' => $wishlistCount
            ];
        } catch (\Exception $e) {
            Log::error('WhislistService getWishlistStatus error: ' . $e->getMessage());
            return [
                'success' => false,
                'wishlist_items' => [],
                'wishlist_count' => 0
            ];
        }
    }
}