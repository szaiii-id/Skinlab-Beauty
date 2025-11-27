<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { useCart } from '@/composables/useCart';
import { useFormatting } from '@/composables/useFormatting';
import { useWishlist } from '@/composables/useWishlist';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import { Heart, ShoppingCart, Zap, Star, User } from 'lucide-vue-next'; // Tambah Icon Star & User

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

// --- LOGIKA LAMA (Cart, Wishlist, Variant) ---
const selectedVariantId = ref(props.product.variants[0]?.variant_id || '');
const quantity = ref(1);
const justAdded = ref(false);
const flash = computed(() => usePage().props.flash?.success);
const isFlashVisible = ref(false);

watch(flash, (newValue) => {
  if (newValue) {
    isFlashVisible.value = true; 
    setTimeout(() => { isFlashVisible.value = false; }, 2500);
  }
});

const { addToCart, isAddingToCart } = useCart();
const { formatCurrency } = useFormatting();
const { addToWishlist, removeFromWishlist, isAdding, isRemoving } = useWishlist();

const currentVariant = computed(() => {
    return props.product.variants.find(v => v.variant_id === selectedVariantId.value);
});

const isWished = computed(() => {
    if (!currentVariant.value) return false;
    const wishlistItems = props.wishlistItems || [];
    return wishlistItems.includes(currentVariant.value.variant_id.toString());
});

const increment = () => { if (currentVariant.value && quantity.value < currentVariant.value.stock) quantity.value++; };
const decrement = () => { if (quantity.value > 1) quantity.value--; };

const handleAddToCartClick = () => {
    if (!selectedVariantId.value) { alert('Please select a variant first.'); return; }
    addToCart(selectedVariantId.value, quantity.value, {
        onSuccess: () => { justAdded.value = true; setTimeout(() => { justAdded.value = false; }, 2500); },
        onError: (errors) => { alert(errors.variant_id || errors.quantity || 'An error occurred.'); }
    });
};

const handleBuyNowClick = () => {
    if (!selectedVariantId.value) { alert('Please select a variant first.'); return; }
    const user = usePage().props.auth.user;
    if (!user) { alert('Please login to continue to checkout.'); router.get('/login'); return; }
    if (!currentVariant.value || currentVariant.value.stock === 0) { alert('This product is out of stock.'); return; }
    if (quantity.value > currentVariant.value.stock) { alert(`Only ${currentVariant.value.stock} items available in stock.`); return; }
    router.get('/checkout', { items: [selectedVariantId.value], quantity: quantity.value });
};

const handleWishlistClick = () => {
    if (!currentVariant.value) return;
    const user = usePage().props.auth.user;
    if (!user) { alert('Please login to add items to your wishlist.'); router.get('/login'); return; }
    if (isWished.value) {
        removeFromWishlist(currentVariant.value.variant_id, { onSuccess: () => {}, onError: () => alert('Failed to remove') });
    } else {
        addToWishlist(currentVariant.value.variant_id, { onSuccess: () => {}, onError: () => alert('Failed to add') });
    }
};

// Styling Computeds
const addToCartButtonClasses = computed(() => justAdded.value ? 'bg-green-100 border-green-600' : 'bg-white border-gray-300 hover:bg-gray-100');
const addToCartIconClasses = computed(() => justAdded.value ? 'text-green-600' : 'text-gray-500');
const wishlistButtonClasses = computed(() => isWished.value ? 'bg-rose-100 border-rose-600' : 'bg-white border-gray-300 hover:bg-gray-100');
const wishlistIconClasses = computed(() => isWished.value ? 'fill-rose-600 text-rose-600' : 'text-gray-500');

// --- LOGIKA BARU: REVIEWS ---
// Menghitung rata-rata rating jika belum ada di database product
const averageRating = computed(() => {
    if (!props.product.reviews || props.product.reviews.length === 0) return 0;
    const total = props.product.reviews.reduce((acc, review) => acc + review.rating, 0);
    return (total / props.product.reviews.length).toFixed(1);
});

// Format tanggal review
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        year: 'numeric', month: 'long', day: 'numeric'
    });
};

// Masking nama (Opsional: Budi Santoso -> Budi S****)
// Saat ini saya gunakan nama asli agar lebih personal, tapi bisa diubah
const getDisplayName = (user) => {
    return user ? user.name : 'Pengguna';
};
</script>

<template>
    <Head :title="product.name" />

    <Transition enter-active-class="ease-out duration-300" enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="ease-in duration-200" leave-from-class="opacity-100 translate-y-0 sm:scale-100" leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
        <div v-if="justAdded" class="fixed inset-0 flex items-center justify-center z-[9999] pointer-events-none">
            <div class="bg-rose-50 border border-rose-200 rounded-lg shadow-xl p-8 text-center max-w-sm w-full pointer-events-auto">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
                    <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                </div>
                <h3 class="mt-5 text-2xl font-semibold text-rose-900">Success!</h3>
                <p class="mt-2 text-rose-700">Successfully added to cart!</p>
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
                        <div class="mt-6 flex justify-center items-center bg-gray-100 rounded-lg shadow-md overflow-hidden aspect-square h-80 mx-auto">
                            <img :src="product.image_url || '/images/default-product.png'" :alt="product.name" class="w-full h-full object-contain" />
                        </div>
                    </div>

                    <div class="md:ml-10 md:w-1/2 mt-6 md:mt-0">
                        <span v-if="product.category" class="block text-sm text-rose-500 uppercase font-semibold tracking-wider">
                            {{ product.category.name }}
                        </span>

                        <h1 class="text-4xl font-light text-gray-900 mt-2">{{ product.name }}</h1>
                        
                        <div class="flex items-center mt-2 space-x-2">
                            <div class="flex text-amber-400">
                                <Star v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= Math.round(averageRating) ? 'fill-current' : 'text-gray-300'" />
                            </div>
                            <span class="text-sm text-gray-500">({{ product.reviews?.length || 0 }} Reviews)</span>
                        </div>

                        <p class="text-gray-600 mt-4">{{ product.description }}</p>

                        <div class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Variant:</label>
                            <div class="flex space-x-3">
                                <button v-for="variant in product.variants" :key="variant.variant_id" @click="selectedVariantId = variant.variant_id; quantity = 1" :disabled="variant.stock === 0" :class="{ 'bg-rose-600 text-white shadow-md': variant.variant_id === selectedVariantId, 'bg-white border-gray-300 text-gray-700 hover:bg-gray-100': variant.variant_id !== selectedVariantId }" class="px-4 py-2 border rounded-full text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{ variant.volume }}
                                </button>
                            </div>
                        </div>

                        <div v-if="currentVariant" class="mt-6 border-t border-gray-200 pt-4">
                            <p class="text-3xl font-light text-gray-900">{{ formatCurrency(currentVariant.price) }}</p>
                            <p :class="currentVariant.stock > 0 ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium mt-1">Stock: {{ currentVariant.stock }}</p>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantity:</label>
                            <div class="flex items-center space-x-2">
                                <button @click="decrement" :disabled="quantity <= 1 || isAddingToCart" class="w-8 h-8 flex items-center justify-center border border-gray-300 text-gray-700 rounded-full hover:bg-gray-100 transition-colors">&minus;</button>
                                <input type="text" v-model="quantity" readonly class="w-12 h-10 text-center border border-gray-300 text-gray-900 rounded-md focus:outline-none bg-gray-50" />
                                <button @click="increment" :disabled="!currentVariant || quantity >= currentVariant.stock || isAddingToCart" class="w-8 h-8 flex items-center justify-center border border-gray-300 text-gray-700 rounded-full hover:bg-gray-100 transition-colors">&plus;</button>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3 mt-6">
                            <button @click="handleBuyNowClick" :disabled="!currentVariant || currentVariant.stock === 0 || isAddingToCart || justAdded" class="flex-1 flex items-center justify-center bg-rose-600 text-white p-3 rounded-md font-semibold transition-colors duration-300 hover:bg-rose-700 disabled:bg-gray-300 disabled:cursor-not-allowed">
                                <Zap class="h-5 w-5 mr-2" /> <span>Buy Now</span>
                            </button>
                            <button @click="handleAddToCartClick" :disabled="!currentVariant || currentVariant.stock === 0 || isAddingToCart || justAdded" :class="addToCartButtonClasses" class="p-3 border rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed" title="Add to Cart">
                                <ShoppingCart class="h-6 w-6" :class="addToCartIconClasses" />
                            </button>
                            <button @click="handleWishlistClick" :disabled="!currentVariant || isAdding || isRemoving" :class="wishlistButtonClasses" class="p-3 border rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed" :title="isWished ? 'Remove from Wishlist' : 'Add to Wishlist'">
                                <Heart class="h-6 w-6" :class="wishlistIconClasses" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-xl overflow-hidden p-8">
                <div class="flex items-center justify-between mb-6 border-b border-gray-100 pb-4">
                    <h2 class="text-2xl font-light text-gray-900">Ulasan Pelanggan</h2>
                    
                    <div class="text-right" v-if="product.reviews && product.reviews.length > 0">
                        <div class="flex items-center gap-2 justify-end">
                            <span class="text-3xl font-bold text-gray-900">{{ averageRating }}</span>
                            <span class="text-gray-400">/ 5.0</span>
                        </div>
                        <div class="flex text-amber-400 justify-end text-sm">
                            <Star v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= Math.round(averageRating) ? 'fill-current' : 'text-gray-300'" />
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Berdasarkan {{ product.reviews.length }} ulasan</p>
                    </div>
                </div>

                <div v-if="product.reviews && product.reviews.length > 0" class="space-y-6">
                    <div v-for="review in product.reviews" :key="review.id" class="border-b border-gray-50 pb-6 last:border-0">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 font-bold">
                                    {{ review.user ? review.user.name.charAt(0).toUpperCase() : 'U' }}
                                </div>
                            </div>
                            
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-bold text-gray-900 text-sm">
                                            {{ getDisplayName(review.user) }}
                                        </h4>
                                        <div class="flex text-amber-400 mt-1">
                                            <Star v-for="i in 5" :key="i" class="w-3 h-3" :class="i <= review.rating ? 'fill-current' : 'text-gray-300'" />
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ formatDate(review.created_at) }}</span>
                                </div>
                                
                                <p class="text-gray-600 text-sm mt-3 leading-relaxed">
                                    {{ review.comment || 'Tidak ada komentar tertulis.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-10 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                    <div class="mx-auto w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                        <Star class="w-6 h-6 text-gray-400" />
                    </div>
                    <p class="text-gray-600 font-medium">Belum ada ulasan</p>
                    <p class="text-sm text-gray-500 mt-1">Jadilah yang pertama mengulas produk ini setelah membeli!</p>
                </div>
            </div>

        </div>
    </div>
</template>