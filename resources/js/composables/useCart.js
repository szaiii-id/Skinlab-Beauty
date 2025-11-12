import { useForm } from '@inertiajs/vue3';

export function useCart() {
    
    const form = useForm({
        variant_id: null,
        quantity: 1,
    });

    const addToCart = (variantId, quantity, options = {}) => {
        form.variant_id = variantId;
        form.quantity = quantity;

        // Gunakan URL langsung untuk menghindari error 'route()'
        form.post('/cart', {
            preserveScroll: true,
            ...options, // Kirim callback dari Show.vue
        });
    };

    return { 
        addToCart,
        isAddingToCart: form.processing
    };
}