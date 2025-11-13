import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useWishlist() {
    
    const page = usePage();
    
    // Ambil data wishlist yang sudah di-load dari Middleware
    const wishlistItems = computed(() => page.props.wishlistItems || []);

    // Cek apakah ID varian ada di dalam array wishlist
    const isInWishlist = (variantId) => {
        return wishlistItems.value.includes(variantId);
    };

    // Form untuk menambah (POST)
    const addForm = useForm({});
    const addToWishlist = (variantId) => {
        addForm.post(`/wishlist/${variantId}`, {
            preserveScroll: true,
            onSuccess: () => {
                // (Pop-up modal/flash message akan dipicu oleh backend)
            }
        });
    };

    // Form untuk menghapus (DELETE)
    const removeForm = useForm({});
    const removeFromWishlist = (variantId) => {
        removeForm.delete(`/wishlist/${variantId}`, {
            preserveScroll: true,
            onSuccess: () => {
                // (Pop-up modal/flash message akan dipicu oleh backend)
            }
        });
    };

    return { 
        isInWishlist,
        addToWishlist,
        removeFromWishlist,
        isAdding: addForm.processing,
        isRemoving: removeForm.processing
    };
}