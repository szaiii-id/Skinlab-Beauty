import { useForm } from '@inertiajs/vue3';

export function useCart() {
    
    const form = useForm({
        variant_id: null,
        quantity: 1,
    });

    const addToCart = (variantId, quantity) => {
        form.variant_id = variantId;
        form.quantity = quantity;

        form.post(route('cart.store'), {
            preserveScroll: true,
            onSuccess: () => {
                alert('Successfully added to cart!');
            },
            onError: (errors) => {
                alert(errors.variant_id || errors.quantity || 'An error occurred.');
            }
        });
    };

    return { 
        addToCart,
        isAddingToCart: form.processing
    };
}