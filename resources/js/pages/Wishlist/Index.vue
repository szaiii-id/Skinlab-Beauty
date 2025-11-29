<script setup>
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useCart } from '@/composables/useCart';
import Swal from 'sweetalert2';

defineOptions({
    layout: DashboardLayout
});

// Props dari Backend
const props = defineProps({
    wishlist: {
        type: [Array, Object], // Bisa Array atau Object untuk keamanan
        default: () => []
    }
});

const { addToCart } = useCart();

// --- LOGIC INFINITE SCROLL ---
const ITEMS_PER_PAGE = 10;
const displayLimit = ref(ITEMS_PER_PAGE);
const observerTarget = ref(null);
let observer = null;

// Konversi Data ke Array Entry agar konsisten
const allWishlistEntries = computed(() => {
    // Apapun bentuk datanya (Array/Object), kita jadikan entries
    return Object.entries(props.wishlist || {});
});

// Slice data untuk ditampilkan
const visibleWishlist = computed(() => {
    const sliced = allWishlistEntries.value.slice(0, displayLimit.value);
    // Kembalikan ke Object agar v-for bekerja stabil
    return Object.fromEntries(sliced);
});

const hasMoreItems = computed(() => {
    return displayLimit.value < allWishlistEntries.value.length;
});

const loadMore = () => {
    if (hasMoreItems.value) {
        setTimeout(() => {
            displayLimit.value += ITEMS_PER_PAGE;
        }, 300);
    }
};

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && hasMoreItems.value) {
            loadMore();
        }
    }, { rootMargin: '100px' });

    if (observerTarget.value) observer.observe(observerTarget.value);
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

// --- HELPER & COMPUTED ---

const formatCurrency = (amount) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0
    }).format(amount);
};

const wishlistItemsCount = computed(() => Object.keys(props.wishlist || {}).length);

// --- ACTIONS (FIXED IDs) ---

// 1. Remove Item
const removeFromWishlist = (item) => { // Terima object Item, bukan key
    Swal.fire({
        title: '<span class="text-gray-900 font-bold">Remove Item?</span>',
        text: "Are you sure you want to remove this from your wishlist?",
        icon: 'warning',
        iconColor: '#f43f5e', // Rose-500
        showCancelButton: true,
        confirmButtonText: 'Yes, remove it',
        cancelButtonText: 'Cancel',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-3xl shadow-xl border border-rose-100 p-2',
            title: 'text-xl',
            confirmButton: 'bg-gradient-to-r from-rose-500 to-pink-600 text-white font-bold py-3 px-6 rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-200 mx-2',
            cancelButton: 'bg-white text-gray-500 font-medium py-3 px-6 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors duration-200 mx-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // GUNAKAN item.variant_id (ID Asli dari database)
            router.delete(`/wishlist/${item.variant_id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Removed!',
                        text: 'Item has been removed.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end',
                        iconColor: '#10b981' // Green-500 for success checkmark
                    });
                }
            });
        }
    });
};

// 2. Move to Cart
const moveToCart = (item) => {
    // GUNAKAN item.variant_id
    router.post(`/wishlist/${item.variant_id}/move-to-cart`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                title: 'Moved to Cart!',
                text: 'Item is now in your cart.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                iconColor: '#10b981'
            });
        },
        onError: (errors) => {
            const msg = errors.quantity || errors.error || 'Failed to move item.';
            Swal.fire({
                title: 'Oops...',
                text: msg,
                icon: 'error',
                confirmButtonText: 'Okay',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'bg-rose-500 text-white font-bold py-2 px-6 rounded-lg'
                }
            });
        }
    });
};

// 3. Add to Cart (Keep in wishlist)
const addToCartFromWishlist = (item) => {
    // GUNAKAN item.variant_id
    addToCart(item.variant_id, 1, {
        onSuccess: () => {
            Swal.fire({
                title: 'Added to Cart!',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end',
                iconColor: '#10b981'
            });
        }
    });
};
</script>

<template>
    <Head title="My Wishlist" />

    <div class="min-h-screen bg-gradient-to-br from-rose-50 to-pink-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-8 flex items-end justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-1">My Wishlist</h1>
                    <p class="text-gray-500 font-medium">{{ wishlistItemsCount }} saved items</p>
                </div>
            </div>

            <div v-if="wishlistItemsCount === 0" class="bg-white rounded-3xl shadow-sm border border-rose-100 p-16 text-center">
                <div class="w-24 h-24 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="text-5xl">❤️</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Your wishlist is empty</h3>
                <p class="text-gray-500 mb-8 max-w-md mx-auto">Save items you love here and buy them later.</p>
                <button @click="router.get('/catalog')" class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-rose-500 to-pink-600 text-white font-bold rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-300">
                    Browse Products
                </button>
            </div>

            <div v-else class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-hidden">
                    <div class="divide-y divide-rose-50">
                        <div 
                            v-for="(item, key) in visibleWishlist" 
                            :key="key" 
                            class="flex flex-col sm:flex-row items-start sm:items-center gap-6 p-6 transition-all duration-300 hover:bg-rose-50/30"
                        >
                            <div class="relative overflow-hidden rounded-xl border border-gray-100 shadow-sm w-24 h-24 flex-shrink-0 bg-white">
                                <img 
                                    :src="item.image_url || '/images/default-product.png'" 
                                    :alt="item.name"
                                    class="w-full h-full object-cover"
                                />
                                <div v-if="item.stock <= 0" class="absolute inset-0 bg-black/60 flex items-center justify-center backdrop-blur-[1px]">
                                    <span class="text-white text-[10px] font-bold px-2 py-1 bg-red-600 rounded uppercase tracking-wider">Sold Out</span>
                                </div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-gray-900 text-lg leading-tight mb-1">{{ item.name }}</h3>
                                <p class="text-gray-500 text-sm mb-3">Variant: {{ item.volume }}</p>
                                
                                <div class="flex items-center gap-3">
                                    <p class="text-rose-500 font-extrabold text-lg">{{ formatCurrency(item.price) }}</p>
                                    
                                    <span 
                                        class="text-xs font-bold px-2.5 py-1 rounded-full flex items-center gap-1.5 border"
                                        :class="item.stock > 0 ? 'bg-green-50 text-green-700 border-green-200' : 'bg-red-50 text-red-700 border-red-200'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="item.stock > 0 ? 'bg-green-500' : 'bg-red-500'"></span>
                                        {{ item.stock > 0 ? `In Stock: ${item.stock}` : 'Out of Stock' }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-wrap sm:flex-nowrap items-center gap-3 w-full sm:w-auto mt-4 sm:mt-0">
                                
                                <button 
                                    @click="addToCartFromWishlist(item)"
                                    :disabled="item.stock === 0"
                                    class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2.5 bg-white border border-gray-200 text-gray-700 font-bold rounded-xl hover:bg-gray-50 hover:border-rose-200 hover:text-rose-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-sm group"
                                    title="Add copy to Cart"
                                >
                                    <svg class="w-5 h-5 mr-2 text-gray-400 group-hover:text-rose-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                    Add
                                </button>

                                <button 
                                    @click="moveToCart(item)"
                                    :disabled="item.stock === 0"
                                    class="flex-1 sm:flex-none inline-flex items-center justify-center px-5 py-2.5 bg-gradient-to-r from-rose-500 to-pink-600 text-white font-bold rounded-xl hover:shadow-lg hover:shadow-rose-500/30 hover:scale-105 disabled:from-gray-300 disabled:to-gray-400 disabled:cursor-not-allowed disabled:scale-100 disabled:shadow-none transition-all shadow-md"
                                    title="Move to Cart (Remove from Wishlist)"
                                >
                                    Move to Cart
                                </button>

                                <button 
                                    @click="removeFromWishlist(item)"
                                    class="p-2.5 bg-white border border-transparent text-gray-400 hover:text-red-500 hover:bg-red-50 hover:border-red-100 rounded-xl transition-all"
                                    title="Remove from Wishlist"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="hasMoreItems" ref="observerTarget" class="py-8 text-center">
                     <div class="inline-flex items-center gap-3 px-4 py-2 bg-white rounded-full shadow-sm border border-rose-100 text-rose-500">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-semibold">Loading your wishlist...</span>
                     </div>
                </div>

                <div v-if="!hasMoreItems" class="text-center pt-4 pb-12">
                    <button 
                        @click="router.get('/catalog')"
                        class="inline-flex items-center px-6 py-2 border border-rose-200 text-rose-600 bg-white font-bold rounded-xl hover:bg-rose-50 hover:border-rose-300 transition-colors shadow-sm"
                    >
                        Continue Shopping
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>