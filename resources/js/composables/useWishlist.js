import { useForm } from '@inertiajs/vue3';

export function useWishlist() {
    
    const form = useForm({
        product_variant_id: null,
    });

    // Helper untuk dispatch event
    const dispatchWishlistUpdate = (count) => {
        window.dispatchEvent(new CustomEvent('wishlist-updated', { 
            detail: { count } 
        }));
    };

    const addToWishlist = (variantId, options = {}) => {
        form.product_variant_id = variantId;

        form.post('/wishlist', {
            preserveScroll: true,
            preserveState: true, // TAMBAHKAN INI
            onSuccess: (page) => {
                // Trigger update setelah success
                const newCount = page.props.wishlistCount || 0;
                dispatchWishlistUpdate(newCount);
                
                // Panggil callback jika ada
                if (options.onSuccess) {
                    options.onSuccess(page);
                }
            },
            ...options,
        });
    };

    const removeFromWishlist = (variantId, options = {}) => {
        form.product_variant_id = variantId;

        form.delete(`/wishlist/${variantId}`, {
            preserveScroll: true,
            preserveState: true, // TAMBAHKAN INI
            onSuccess: (page) => {
                // Trigger update setelah success
                const newCount = page.props.wishlistCount || 0;
                dispatchWishlistUpdate(newCount);
                
                // Panggil callback jika ada
                if (options.onSuccess) {
                    options.onSuccess(page);
                }
            },
            ...options,
        });
    };

    return { 
        addToWishlist,
        removeFromWishlist,
        isAdding: form.processing,
        isRemoving: form.processing
    };
}