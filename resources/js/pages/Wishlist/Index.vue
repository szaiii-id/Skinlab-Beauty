<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useCart } from '@/composables/useCart';
import { Trash2, ShoppingCart, ArrowRight, HeartOff, Star, AlertCircle, PackageCheck, PackageX, Package, Store, Eye, Tag } from 'lucide-vue-next';
import Swal from 'sweetalert2';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    wishlist: [Array, Object]
});

const { addToCart } = useCart();

// --- INFINITE SCROLL ---
const ITEMS_PER_PAGE = 6;
const displayLimit = ref(ITEMS_PER_PAGE);
const observerTarget = ref(null);
let observer = null;

const allWishlistEntries = computed(() => Object.values(props.wishlist || {}));
const visibleWishlist = computed(() => allWishlistEntries.value.slice(0, displayLimit.value));
const hasMoreItems = computed(() => displayLimit.value < allWishlistEntries.value.length);

const loadMore = () => {
    if (hasMoreItems.value) setTimeout(() => { displayLimit.value += ITEMS_PER_PAGE; }, 300);
};

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && hasMoreItems.value) loadMore();
    }, { rootMargin: '100px' });
    if (observerTarget.value) observer.observe(observerTarget.value);
});
onUnmounted(() => { if (observer) observer.disconnect(); });

const wishlistItemsCount = computed(() => allWishlistEntries.value.length);

// --- HELPERS ---
const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount || 0);

const getImageUrl = (path) => {
    if (!path) return '/images/default-product.png';
    return path.startsWith('http') ? path : `/storage/${path}`;
};

const getDiscountPercent = (item) => {
    const price = Number(item.price);
    const finalPrice = Number(item.final_price);
    if (!finalPrice || finalPrice === 0 || finalPrice >= price) return 0;
    return Math.round(((price - finalPrice) / price) * 100);
};

const getSavedAmount = (item) => {
    const price = Number(item.price) || 0;
    const finalPrice = Number(item.final_price) || 0;
    if (finalPrice === 0 || finalPrice >= price) return 0;
    return price - finalPrice;
};

// Helper Stok
const getStockStatus = (stock) => {
    if (stock <= 0) return { label: 'Sold Out', color: 'text-gray-400', bg: 'bg-gray-100 border-gray-200', icon: PackageX };
    if (stock < 5) return { label: `Hurry! Only ${stock} left`, color: 'text-orange-600', bg: 'bg-orange-50 border-orange-100', icon: AlertCircle, animate: true };
    return { label: 'In Stock', color: 'text-emerald-600', bg: 'bg-emerald-50 border-emerald-100', icon: PackageCheck };
};

// --- ACTIONS ---
const removeFromWishlist = (item) => {
    Swal.fire({
        title: 'Remove?',
        text: "Remove this item from wishlist?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f43f5e',
        cancelButtonColor: '#e5e7eb',
        confirmButtonText: 'Yes, Remove',
        customClass: { popup: 'rounded-2xl' }
    }).then((result) => {
        if (result.isConfirmed) router.delete(`/wishlist/${item.variant_id}`, { preserveScroll: true });
    });
};

const moveToCart = (item) => {
    if (item.stock <= 0) return;
    router.post(`/wishlist/${item.variant_id}/move-to-cart`, {}, {
        preserveScroll: true,
        onSuccess: () => Swal.fire({ icon: 'success', title: 'Moved to Cart', timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' }),
        onError: () => Swal.fire('Oops...', 'Failed to move item.', 'error')
    });
};
</script>

<template>
    <Head title="My Wishlist" />

    <div class="min-h-screen bg-[#F8F9FA] py-6 sm:py-12 pb-24 sm:pb-12">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-6 sm:mb-10 flex items-end justify-between border-b border-gray-200 pb-4 sm:pb-6">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight mb-0.5 sm:mb-1">My Wishlist</h1>
                    <p class="text-xs sm:text-sm text-gray-500 font-medium">{{ wishlistItemsCount }} items saved for later</p>
                </div>
            </div>

            <div v-if="wishlistItemsCount === 0" class="bg-white rounded-[2rem] shadow-sm border border-gray-100 p-10 sm:p-20 text-center">
                <div class="w-20 h-20 sm:w-24 sm:h-24 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <HeartOff class="w-8 h-8 sm:w-10 sm:h-10 text-rose-400" />
                </div>
                <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Your wishlist is empty</h3>
                <button @click="router.get('/catalog')" class="mt-4 inline-flex items-center gap-2 px-6 py-3 sm:px-8 sm:py-3 bg-gradient-to-r from-rose-500 to-pink-600 text-white font-bold rounded-xl hover:shadow-lg hover:shadow-rose-200 hover:-translate-y-1 transition-all duration-300 text-sm sm:text-base">
                    Browse Products <ArrowRight class="w-4 h-4" />
                </button>
            </div>

            <div v-else class="space-y-4 sm:space-y-6">
                
                <div v-for="(item, key) in visibleWishlist" :key="key">
                    
                    <div class="block sm:hidden bg-white rounded-2xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-all relative overflow-hidden"
                         :class="{'opacity-80 grayscale-[0.5]': item.stock <= 0}">
                        
                        <div class="flex gap-3">
                            <div class="shrink-0 w-20 h-20 bg-[#F9FAFB] rounded-xl border border-gray-100 overflow-hidden relative">
                                <img :src="getImageUrl(item.image_url)" :alt="item.name" class="w-full h-full object-contain mix-blend-multiply" />
                                <div v-if="item.stock <= 0" class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                    <span class="text-[9px] font-bold text-white uppercase tracking-wider">Sold Out</span>
                                </div>
                            </div>

                            <div class="flex-1 min-w-0 flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center gap-1 mb-1">
                                        <Store class="w-3 h-3 text-rose-400" />
                                        <span v-if="item.brand_name" class="text-[9px] font-bold text-gray-400 uppercase truncate">{{ item.brand_name }}</span>
                                    </div>
                                    <h3 class="text-sm font-bold text-gray-900 leading-tight line-clamp-2 mb-1" @click="router.get(`/products/${item.product_slug}/${item.product_id}`)">
                                        {{ item.name }}
                                    </h3>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                        {{ item.volume }}
                                    </span>
                                </div>

                                <div class="mt-1">
                                    <div v-if="Number(item.final_price) < Number(item.price) && Number(item.final_price) > 0">
                                        <div class="flex items-center gap-1">
                                            <span class="text-[10px] text-gray-400 line-through">{{ formatCurrency(item.price) }}</span>
                                            <span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-1 rounded">-{{ getDiscountPercent(item) }}%</span>
                                        </div>
                                        <div class="text-sm font-black text-rose-600">{{ formatCurrency(item.final_price) }}</div>
                                    </div>
                                    <div v-else class="text-sm font-black text-gray-900">{{ formatCurrency(item.price) }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="h-px bg-gray-100 my-3"></div>

                        <div class="flex flex-col gap-3">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-1 text-[9px] font-bold px-2 py-1 rounded border uppercase"
                                    :class="[getStockStatus(item.stock).color, getStockStatus(item.stock).bg]">
                                    <component :is="getStockStatus(item.stock).icon" class="w-3 h-3" />
                                    {{ getStockStatus(item.stock).label }}
                                </div>
                                <button @click="removeFromWishlist(item)" class="text-gray-400 hover:text-red-500 text-xs flex items-center gap-1 font-medium">
                                    <Trash2 class="w-3.5 h-3.5" /> Remove
                                </button>
                            </div>
                            
                            <div class="flex gap-2">
                                <Link :href="`/products/${item.product_slug}/${item.product_id}`" 
                                      class="p-2.5 bg-white border border-gray-200 rounded-xl text-gray-500 hover:bg-gray-50 hover:text-blue-600 transition-colors shadow-sm">
                                    <Eye class="w-4 h-4" />
                                </Link>
                                <button 
                                    @click="moveToCart(item)"
                                    :disabled="item.stock <= 0"
                                    class="flex-1 flex items-center justify-center gap-2 bg-gradient-to-r from-rose-500 to-pink-600 text-white text-xs font-bold rounded-xl shadow-md shadow-rose-200 active:scale-95 transition-all disabled:from-gray-300 disabled:to-gray-400 disabled:shadow-none"
                                >
                                    <ShoppingCart class="w-3.5 h-3.5" /> Move to Cart
                                </button>
                            </div>
                        </div>
                    </div>


                    <div class="hidden sm:flex bg-white rounded-3xl border border-gray-100 p-4 shadow-sm hover:shadow-lg hover:border-rose-100 transition-all duration-300 flex-col sm:flex-row group relative overflow-hidden"
                         :class="{'opacity-80': item.stock <= 0}">
                        
                        <div class="relative w-48 aspect-square shrink-0">
                            <div class="w-full h-full bg-[#F9FAFB] rounded-2xl p-4 flex items-center justify-center relative overflow-hidden border border-gray-50">
                                <img 
                                    :src="getImageUrl(item.image_url)" 
                                    :alt="item.name"
                                    class="w-full h-full object-contain mix-blend-multiply transition-transform duration-500 group-hover:scale-110"
                                    :class="{'grayscale': item.stock <= 0}"
                                />
                                <div v-if="item.stock <= 0" class="absolute inset-0 bg-white/60 flex items-center justify-center backdrop-blur-[1px] z-10">
                                    <span class="bg-gray-800 text-white px-4 py-1.5 rounded-full font-bold text-xs uppercase tracking-wider shadow-lg">Sold Out</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex-1 p-4 flex flex-col justify-center min-w-0">
                            
                            <div class="flex items-center gap-2 mb-1.5">
                                <Store class="w-3 h-3 text-rose-400" />
                                <span v-if="item.brand_name" class="text-[10px] font-extrabold text-rose-500 uppercase tracking-widest truncate">
                                    {{ item.brand_name }}
                                </span>
                                <span v-if="item.category_name" class="text-[10px] font-bold text-gray-400 uppercase tracking-wide truncate border-l border-gray-200 pl-2">
                                    {{ item.category_name }}
                                </span>
                            </div>

                            <h3 class="font-bold text-gray-900 text-lg leading-snug mb-3 cursor-pointer hover:text-rose-600 transition-colors" 
                                @click="router.get(`/products/${item.product_slug}/${item.product_id}`)">
                                {{ item.name }}
                            </h3>

                            <div class="flex flex-wrap items-center gap-3 mb-4">
                                <div class="flex items-center gap-1.5 text-xs font-medium text-gray-700 bg-gray-50 border border-gray-200 px-2.5 py-1 rounded-lg">
                                    <Package class="w-3.5 h-3.5 text-gray-400" />
                                    {{ item.volume }}
                                </div>
                                
                                <div class="flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-1 rounded-lg border uppercase tracking-wide"
                                    :class="[getStockStatus(item.stock).color, getStockStatus(item.stock).bg]">
                                    <component :is="getStockStatus(item.stock).icon" class="w-3.5 h-3.5" />
                                    {{ getStockStatus(item.stock).label }}
                                </div>
                            </div>

                            <div v-if="item.tags && item.tags.length" class="flex flex-wrap gap-2 mt-auto">
                                <span v-for="tag in item.tags.slice(0, 3)" :key="tag" 
                                      class="text-[9px] font-bold text-indigo-600 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded uppercase flex items-center gap-1">
                                    <Tag class="w-3 h-3" /> {{ tag }}
                                </span>
                            </div>
                        </div>

                        <div class="p-4 w-64 flex flex-col justify-center gap-4 border-l border-gray-50 bg-gray-50/30 rounded-r-3xl">
                            
                            <div class="text-left">
                                <p class="text-[10px] text-gray-400 font-bold uppercase mb-0.5">Price</p>
                                
                                <div v-if="Number(item.final_price) < Number(item.price) && Number(item.final_price) > 0" class="flex flex-col items-start">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span v-if="item.discount_type === 'fixed'" class="bg-blue-600 text-white text-[9px] font-bold px-2 py-0.5 rounded shadow-sm">
                                            SAVE {{ formatCurrency(getSavedAmount(item)) }}
                                        </span>
                                        <span v-else-if="getDiscountPercent(item) > 0" class="bg-rose-500 text-white text-[9px] font-bold px-2 py-0.5 rounded shadow-sm animate-pulse">
                                            {{ getDiscountPercent(item) }}% OFF
                                        </span>
                                        <span class="text-xs text-gray-400 line-through decoration-rose-300">{{ formatCurrency(item.price) }}</span>
                                    </div>
                                    <div class="text-2xl font-black text-rose-600 tracking-tight">{{ formatCurrency(item.final_price) }}</div>
                                </div>
                                
                                <div v-else>
                                    <div class="text-2xl font-black text-gray-900 tracking-tight">{{ formatCurrency(item.price) }}</div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2.5">
                                <button 
                                    @click="moveToCart(item)"
                                    :disabled="item.stock <= 0"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-rose-500 to-pink-600 text-white text-xs font-bold uppercase tracking-wide rounded-xl shadow-md shadow-rose-200 hover:shadow-lg hover:scale-[1.02] active:scale-95 transition-all disabled:from-gray-300 disabled:to-gray-400 disabled:cursor-not-allowed disabled:shadow-none"
                                >
                                    <ShoppingCart class="w-3.5 h-3.5" /> Move to Cart
                                </button>

                                <div class="flex gap-2">
                                    <Link 
                                        :href="`/products/${item.product_slug}/${item.product_id}`"
                                        class="flex-1 p-2.5 bg-white border border-rose-100 text-rose-400 rounded-xl hover:border-rose-200 hover:text-white hover:bg-rose-400 transition-all shadow-sm flex justify-center items-center"
                                        title="View Details"
                                    >
                                        <Eye class="w-4 h-4" />
                                    </Link>
                                    <button 
                                        @click="removeFromWishlist(item)"
                                        class="flex-1 p-2.5 bg-white border border-rose-100 text-rose-400 rounded-xl hover:border-rose-200 hover:text-white hover:bg-rose-400 transition-all shadow-sm flex justify-center items-center"
                                        title="Remove"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

            </div>

            <div v-if="hasMoreItems" ref="observerTarget" class="py-12 text-center">
                 <span class="inline-block px-4 py-1 bg-gray-100 rounded-full text-[10px] font-bold text-gray-400 uppercase tracking-widest animate-pulse">Loading...</span>
            </div>
        </div>
    </div>
</template>