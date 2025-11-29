<script setup>
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
// 1. IMPORT TAMBAHAN: ref, onMounted, onUnmounted
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useCart } from '@/composables/useCart';

defineOptions({
    layout: DashboardLayout
});

const props = defineProps({
    wishlist: Object
});

const { addToCart } = useCart();

// ==========================================
// LOGIC INFINITE SCROLL (CLIENT SIDE)
// ==========================================
const itemsPerPage = 10;
const displayLimit = ref(10);
const observerTarget = ref(null);
let observer = null;

// Ubah Object Wishlist menjadi Array agar bisa dihitung/slice
const allWishlistEntries = computed(() => Object.entries(props.wishlist || {}));

// Hanya tampilkan sebagian data sesuai limit
const visibleWishlist = computed(() => {
    // Ambil sebagian data
    const sliced = allWishlistEntries.value.slice(0, displayLimit.value);
    // Kembalikan ke bentuk Object agar v-for di template tidak perlu diubah drastis
    return Object.fromEntries(sliced);
});

// Cek apakah masih ada item tersembunyi
const hasMoreItems = computed(() => {
    return displayLimit.value < allWishlistEntries.value.length;
});

// Fungsi Load More
const loadMore = () => {
    if (hasMoreItems.value) {
        // Simulasi delay sedikit agar terasa natural
        setTimeout(() => {
            displayLimit.value += itemsPerPage;
        }, 300);
    }
};

// Setup Observer
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
// ==========================================
// END LOGIC INFINITE SCROLL
// ==========================================

const formatCurrency = (amount) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
};

// Computed properties
const wishlistItemsCount = computed(() => {
    return Object.keys(props.wishlist || {}).length;
});

// Methods
const removeFromWishlist = (variantId) => {
    router.delete(`/wishlist/${variantId}`, {
        preserveScroll: true // Agar tidak lompat ke atas saat hapus
    });
};

const addToCartFromWishlist = (variantId) => {
    addToCart(variantId, 1, {
        onSuccess: () => {
            // Success handled by cart
        }
    });
};

const moveToCart = (variantId) => {
    router.post(`/wishlist/${variantId}/move-to-cart`, {
        preserveScroll: true,
        onSuccess: () => {
            // Item akan otomatis pindah ke cart dan dihapus dari wishlist
        }
    });
};
</script>

<template>
    <Head title="Wishlist" />

    <div class="min-h-screen bg-gradient-to-br from-rose-50 to-pink-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-8">
                <h1 class="text-3xl font-light text-gray-900 mb-2">
                    Wishlist Saya
                </h1>
                <p class="text-gray-600">
                    {{ wishlistItemsCount }} item di wishlist Anda
                </p>
            </div>

            <div v-if="wishlistItemsCount === 0" class="bg-white rounded-2xl shadow border border-gray-100 p-12 text-center">
                <div class="text-6xl mb-4">❤️</div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Wishlist Kosong</h3>
                <p class="text-gray-600 mb-6">Belum ada produk di wishlist Anda</p>
                <button 
                    @click="router.get('/catalog')"
                    class="inline-flex items-center px-6 py-3 bg-rose-600 text-white font-semibold rounded-lg hover:bg-rose-700 transition-colors"
                >
                    Jelajahi Produk
                </button>
            </div>

            <div v-else class="space-y-6">
                <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
                    <div class="divide-y divide-gray-100">
                        <div 
                            v-for="(item, variantId) in visibleWishlist" 
                            :key="variantId" 
                            class="flex items-center gap-6 p-6 transition-colors hover:bg-gray-50"
                        >
                            <img 
                                :src="item.image_url || '/images/default-product.png'" 
                                :alt="item.name"
                                class="w-20 h-20 rounded-lg object-cover"
                            />
                            
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 text-lg mb-2">{{ item.name }}</h3>
                                <p class="text-gray-600 text-sm mb-1">Varian: {{ item.volume }}</p>
                                <p class="text-rose-600 font-medium text-lg">{{ formatCurrency(item.price) }}</p>
                                <p :class="item.stock > 0 ? 'text-green-600' : 'text-red-600'" class="text-sm mt-1">
                                    {{ item.stock > 0 ? `Stok: ${item.stock}` : 'Stok Habis' }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <button 
                                    @click="addToCartFromWishlist(variantId)"
                                    :disabled="item.stock === 0"
                                    class="inline-flex items-center px-4 py-2 bg-rose-600 text-white text-sm font-medium rounded-lg hover:bg-rose-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    Tambah
                                </button>

                                <button 
                                    @click="moveToCart(variantId)"
                                    :disabled="item.stock === 0"
                                    class="inline-flex items-center px-4 py-2 border border-rose-600 text-rose-600 text-sm font-medium rounded-lg hover:bg-rose-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                >
                                    Pindah
                                </button>

                                <button 
                                    @click="removeFromWishlist(variantId)"
                                    class="p-2 text-gray-400 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50"
                                    title="Hapus dari wishlist"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="hasMoreItems" ref="observerTarget" class="py-6 text-center">
                     <div class="inline-flex items-center gap-2 text-rose-600">
                        

[Image of e-commerce order lifecycle flow diagram]

                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium">Memuat wishlist lainnya...</span>
                     </div>
                </div>

                <div v-if="!hasMoreItems" class="text-center pt-4">
                    <button 
                        @click="router.get('/catalog')"
                        class="inline-flex items-center px-6 py-3 border border-rose-600 text-rose-600 font-semibold rounded-lg hover:bg-rose-50 transition-colors"
                    >
                        Lanjutkan Belanja
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>