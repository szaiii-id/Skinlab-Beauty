<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { useCart } from '@/composables/useCart';
import { useFormatting } from '@/composables/useFormatting';
import { useWishlist } from '@/composables/useWishlist';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import ProductReviews from '@/components/ProductReviews.vue'; // Modular Component
import { Heart, ShoppingCart, Zap, Star } from 'lucide-vue-next'; 

defineOptions({
    layout: AppNavbarLayout
});

const props = defineProps({
    product: Object,
    wishlistItems: {
        type: Array,
        default: () => []
    }
});

// --- STATE MANAGEMENT ---
// 1. Variant Logic: Auto-select variant with stock available
const defaultVariant = props.product.variants.find(v => v.stock > 0) || props.product.variants[0];
const selectedVariantId = ref(defaultVariant?.variant_id || '');
const quantity = ref(1);

// 2. Notification States (Popups)
const showCartPopup = ref(false);
const showWishlistPopup = ref(false);
const wishlistMessage = ref(''); // e.g., "Added to Wishlist"

// --- COMPOSABLES ---
const { addToCart, isAddingToCart } = useCart();
const { formatCurrency } = useFormatting();
const { addToWishlist, removeFromWishlist, isAdding, isRemoving } = useWishlist();

// --- COMPUTED PROPERTIES ---
const currentVariant = computed(() => {
    return props.product.variants.find(v => v.variant_id === selectedVariantId.value);
});

const isOutOfStock = computed(() => {
    return !currentVariant.value || currentVariant.value.stock <= 0;
});

const isWished = computed(() => {
    if (!currentVariant.value) return false;
    const wishlistItems = props.wishlistItems || [];
    return wishlistItems.includes(currentVariant.value.variant_id.toString());
});

const averageRating = computed(() => {
    if (!props.product.reviews || props.product.reviews.length === 0) return 0;
    const total = props.product.reviews.reduce((acc, review) => acc + review.rating, 0);
    return (total / props.product.reviews.length).toFixed(1);
});

// Styling Computed Classes
const addToCartButtonClasses = computed(() => showCartPopup.value ? 'bg-green-100 border-green-600' : 'bg-white border-gray-300 hover:bg-gray-100');
const addToCartIconClasses = computed(() => showCartPopup.value ? 'text-green-600' : 'text-gray-500');
const wishlistButtonClasses = computed(() => isWished.value ? 'bg-rose-100 border-rose-600' : 'bg-white border-gray-300 hover:bg-gray-100');
const wishlistIconClasses = computed(() => isWished.value ? 'fill-rose-600 text-rose-600' : 'text-gray-500');

// --- WATCHERS ---
// Reset quantity to 1 when user switches variant
watch(selectedVariantId, () => {
    quantity.value = 1;
});

// --- METHODS / HANDLERS ---

const increment = () => { 
    if (currentVariant.value && quantity.value < currentVariant.value.stock) {
        quantity.value++;
    }
};

const decrement = () => { 
    if (quantity.value > 1) quantity.value--; 
};

const handleAddToCartClick = () => {
    if (!selectedVariantId.value) { alert('Please select a variant first.'); return; }
    
    addToCart(selectedVariantId.value, quantity.value, {
        onSuccess: () => { 
            showCartPopup.value = true; 
            setTimeout(() => { showCartPopup.value = false; }, 2500); 
        },
        onError: (errors) => { alert(errors.variant_id || errors.quantity || 'An error occurred.'); }
    });
};

const handleBuyNowClick = () => {
    if (!selectedVariantId.value) { alert('Please select a variant first.'); return; }
    
    const user = usePage().props.auth.user;
    if (!user) { alert('Please login to continue to checkout.'); router.get('/login'); return; }
    
    if (isOutOfStock.value) { alert('This product is out of stock.'); return; }
    
    if (quantity.value > currentVariant.value.stock) { alert(`Only ${currentVariant.value.stock} items available in stock.`); return; }
    
    router.get('/checkout', { items: [selectedVariantId.value], quantity: quantity.value });
};

// [UPDATED] Wishlist Handler with Popup Logic
const handleWishlistClick = () => {
    if (!currentVariant.value) return;
    
    const user = usePage().props.auth.user;
    if (!user) { alert('Please login to add items to your wishlist.'); router.get('/login'); return; }
    
    if (isWished.value) {
        removeFromWishlist(currentVariant.value.variant_id, { 
            onSuccess: () => { 
                wishlistMessage.value = 'Removed from Wishlist';
                showWishlistPopup.value = true;
                setTimeout(() => { showWishlistPopup.value = false; }, 2500);
            }, 
            onError: () => alert('Failed to remove from wishlist') 
        });
    } else {
        addToWishlist(currentVariant.value.variant_id, { 
            onSuccess: () => { 
                wishlistMessage.value = 'Added to Wishlist!';
                showWishlistPopup.value = true;
                setTimeout(() => { showWishlistPopup.value = false; }, 2500);
            }, 
            onError: () => alert('Failed to add to wishlist') 
        });
    }
};
</script>

<template>
    <Head :title="product.name" />

    <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
        <div v-if="showCartPopup" class="fixed inset-0 flex items-center justify-center z-[9999] pointer-events-none">
            <div class="bg-rose-50 border border-rose-200 rounded-lg shadow-xl p-8 text-center max-w-sm w-full pointer-events-auto transform transition-all">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                    <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                </div>
                <h3 class="mt-5 text-2xl font-semibold text-rose-900">Success!</h3>
                <p class="mt-2 text-rose-700">Successfully added to cart!</p>
            </div>
        </div>
    </Transition>

    <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0 translate-y-4" enter-to-class="opacity-100 translate-y-0" leave-active-class="ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-4">
        <div v-if="showWishlistPopup" class="fixed inset-0 flex items-center justify-center z-[9999] pointer-events-none">
            <div class="bg-white border border-rose-100 rounded-lg shadow-xl p-8 text-center max-w-sm w-full pointer-events-auto transform transition-all">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-rose-50">
                    <Heart class="h-8 w-8 text-rose-600 fill-rose-600" />
                </div>
                <h3 class="mt-5 text-xl font-semibold text-gray-800">Wishlist Updated</h3>
                <p class="mt-2 text-gray-600">{{ wishlistMessage }}</p>
            </div>
        </div>
    </Transition>

    <div class="bg-rose-50 min-h-screen py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <div class="p-8 md:flex">
                    <div class="md:w-1/2">
                        <Link href="/catalog" class="inline-flex items-center px-4 py-2 border border-rose-600 text-rose-600 rounded-full text-sm font-medium hover:bg-rose-50 transition-colors duration-300">
                            &larr; Back
                        </Link>
                        
                        <div class="mt-6 flex justify-center items-center bg-gray-100 rounded-lg shadow-md overflow-hidden aspect-square h-80 mx-auto relative group">
                            <img 
                                :src="product.image_url || '/images/default-product.png'" 
                                :alt="product.name" 
                                class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-105" 
                                :class="{'grayscale opacity-60': isOutOfStock}"
                            />

                            <div v-if="isOutOfStock" class="absolute inset-0 flex items-center justify-center bg-black/20 backdrop-blur-sm">
                                <span class="bg-black/80 text-white px-6 py-2 rounded-full font-bold text-lg shadow-lg tracking-wider uppercase transform -rotate-12">
                                    Sold Out
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="md:ml-10 md:w-1/2 mt-6 md:mt-0 flex flex-col justify-center">
                        <span v-if="product.category" class="block text-sm text-rose-500 uppercase font-semibold tracking-wider">
                            {{ product.category.name }}
                        </span>

                        <h1 class="text-4xl font-light text-gray-900 mt-2">{{ product.name }}</h1>
                        
                        <div class="flex items-center mt-2 space-x-2">
                            <div class="flex text-amber-400">
                                <Star v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= Math.round(averageRating) ? 'fill-current' : 'text-gray-300'" />
                            </div>
                            <span class="text-sm text-gray-500 hover:text-rose-600 cursor-pointer transition">
                                ({{ product.reviews?.length || 0 }} Reviews)
                            </span>
                        </div>

                        <p class="text-gray-600 mt-4 leading-relaxed">{{ product.description }}</p>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Variant:</label>
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    v-for="variant in product.variants" 
                                    :key="variant.variant_id" 
                                    @click="selectedVariantId = variant.variant_id" 
                                    :class="[
                                        variant.variant_id === selectedVariantId 
                                            ? 'bg-rose-600 text-white shadow-md ring-2 ring-rose-200 ring-offset-1' 
                                            : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-100',
                                        variant.stock === 0 ? 'opacity-60 border-dashed cursor-not-allowed' : ''
                                    ]" 
                                    class="px-4 py-2 border rounded-full text-sm font-medium transition-all"
                                >
                                    {{ variant.volume }} 
                                    <span v-if="variant.stock === 0" class="ml-1 text-xs text-red-100 font-bold bg-red-500/50 px-1 rounded">(Sold Out)</span>
                                </button>
                            </div>
                        </div>

                        <div v-if="currentVariant" class="mt-6 border-t border-gray-200 pt-4">
                            <div class="flex items-end gap-3">
                                <p class="text-3xl font-light text-gray-900">{{ formatCurrency(currentVariant.price) }}</p>
                                <span v-if="currentVariant.stock > 0 && currentVariant.stock < 5" class="text-xs font-bold text-orange-500 mb-2 animate-pulse">
                                    Low Stock!
                                </span>
                            </div>
                            <p :class="currentVariant.stock > 0 ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium mt-1 flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full" :class="currentVariant.stock > 0 ? 'bg-green-500' : 'bg-red-500'"></span>
                                {{ currentVariant.stock > 0 ? `Stock Available: ${currentVariant.stock}` : 'Out of Stock' }}
                            </p>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantity:</label>
                            <div class="flex items-center space-x-2">
                                <button 
                                    @click="decrement" 
                                    :disabled="quantity <= 1 || isAddingToCart || isOutOfStock" 
                                    class="w-8 h-8 flex items-center justify-center border border-gray-300 text-gray-700 rounded-full hover:bg-gray-100 transition-colors disabled:opacity-50"
                                >&minus;</button>
                                
                                <input type="text" v-model="quantity" readonly class="w-12 h-10 text-center border border-gray-300 text-gray-900 rounded-md focus:outline-none bg-gray-50" />
                                
                                <button 
                                    @click="increment" 
                                    :disabled="!currentVariant || quantity >= currentVariant.stock || isAddingToCart || isOutOfStock" 
                                    class="w-8 h-8 flex items-center justify-center border border-gray-300 text-gray-700 rounded-full hover:bg-gray-100 transition-colors disabled:opacity-50"
                                >&plus;</button>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3 mt-6">
                            <button 
                                @click="handleBuyNowClick" 
                                :disabled="isOutOfStock || isAddingToCart || showCartPopup" 
                                class="flex-1 flex items-center justify-center bg-rose-600 text-white p-3 rounded-md font-semibold transition-colors duration-300 hover:bg-rose-700 disabled:bg-gray-300 disabled:cursor-not-allowed shadow-md hover:shadow-lg"
                            >
                                <Zap class="h-5 w-5 mr-2" /> 
                                <span>{{ isOutOfStock ? 'Out of Stock' : 'Buy Now' }}</span>
                            </button>
                            
                            <button 
                                @click="handleAddToCartClick" 
                                :disabled="isOutOfStock || isAddingToCart || showCartPopup" 
                                :class="[addToCartButtonClasses, isOutOfStock ? 'bg-gray-100 border-gray-200 cursor-not-allowed' : '']" 
                                class="p-3 border rounded-md transition-colors disabled:opacity-50 shadow-sm hover:shadow-md" 
                                title="Add to Cart"
                            >
                                <ShoppingCart class="h-6 w-6" :class="isOutOfStock ? 'text-gray-400' : addToCartIconClasses" />
                            </button>
                            
                            <button 
                                @click="handleWishlistClick" 
                                :disabled="!currentVariant || isAdding || isRemoving" 
                                :class="wishlistButtonClasses" 
                                class="p-3 border rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed shadow-sm hover:shadow-md" 
                                :title="isWished ? 'Remove from Wishlist' : 'Add to Wishlist'"
                            >
                                <Heart class="h-6 w-6" :class="wishlistIconClasses" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <ProductReviews :reviews="product.reviews" />

        </div>
    </div>
</template>