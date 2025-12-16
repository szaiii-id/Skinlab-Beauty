<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { useCart } from '@/composables/useCart';
import { useFormatting } from '@/composables/useFormatting';
import { useWishlist } from '@/composables/useWishlist';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import ProductReviews from '@/components/ProductReviews.vue'; 
// Icons Lengkap
import { Heart, ShoppingCart, Zap, Star, ChevronDown, ChevronUp, Share2, ShieldCheck, Truck, CheckCircle, PackageCheck, AlertCircle, Copy, Minus, Plus } from 'lucide-vue-next'; 

// Swiper
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Pagination, Navigation, EffectFade } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/navigation';
import 'swiper/css/effect-fade';
import Swal from 'sweetalert2';

defineOptions({ layout: AppNavbarLayout });

const props = defineProps({
    product: Object,
    wishlistItems: { type: Array, default: () => [] }
});

// --- STATE ---
const defaultVariant = props.product.variants.find(v => v.stock > 0) || props.product.variants[0];
const selectedVariantId = ref(defaultVariant?.variant_id || '');
const quantity = ref(1);
const isDescExpanded = ref(false);

// Notification
const showCartPopup = ref(false);
const showWishlistPopup = ref(false);
const showSharePopup = ref(false);
const wishlistMessage = ref('');

// Swiper
const modules = [Pagination, Navigation, EffectFade];
const swiperInstance = ref(null);

// Composables
const { addToCart, isAddingToCart } = useCart();
const { formatCurrency } = useFormatting();
const { addToWishlist, removeFromWishlist, isAdding, isRemoving } = useWishlist();

// --- LOGIC IMAGE ---
const allImages = computed(() => {
    const images = [];
    if (props.product.thumbnail) images.push({ src: props.product.thumbnail, id: 'main' });
    if (props.product.variants?.length > 0) {
        props.product.variants.forEach(v => {
            if (v.image_url && !images.some(img => img.src === v.image_url)) {
                images.push({ src: v.image_url, id: v.variant_id });
            }
        });
    }
    if (images.length === 0) images.push({ src: '/images/default-product.png', id: 'default' });
    return images;
});

const onSwiper = (swiper) => { swiperInstance.value = swiper; };

watch(selectedVariantId, (newId) => {
    quantity.value = 1;
    if (swiperInstance.value) {
        const variant = props.product.variants.find(v => v.variant_id === newId);
        if (variant && variant.image_url) {
            const index = allImages.value.findIndex(img => img.src === variant.image_url);
            if (index !== -1) swiperInstance.value.slideTo(index);
        }
    }
});

// --- COMPUTED ---
const currentVariant = computed(() => props.product.variants.find(v => v.variant_id === selectedVariantId.value));
const isOutOfStock = computed(() => !currentVariant.value || currentVariant.value.stock <= 0);
const isLowStock = computed(() => currentVariant.value && currentVariant.value.stock > 0 && currentVariant.value.stock < 5);
const isWished = computed(() => (props.wishlistItems || []).includes(currentVariant.value?.variant_id.toString()));

const averageRating = computed(() => {
    if (!props.product.reviews?.length) return 0;
    const total = props.product.reviews.reduce((acc, r) => acc + r.rating, 0);
    return (total / props.product.reviews.length).toFixed(1);
});

// --- LOGIC HARGA & DISKON (DIPERBAIKI) ---

// 1. Ambil Harga Asli (Pastikan Number)
const displayPrice = computed(() => {
    if (!currentVariant.value) return 0;
    return Number(currentVariant.value.price);
});

// 2. Ambil Harga Akhir (Handle Null dengan '??')
const displayFinalPrice = computed(() => {
    if (!currentVariant.value) return 0;
    // Jika final_price NULL, otomatis pakai price
    return Number(currentVariant.value.final_price ?? currentVariant.value.price);
});

// 3. Cek Diskon (Lebih Ketat)
const hasDiscount = computed(() => {
    const original = displayPrice.value;
    const final = displayFinalPrice.value;
    // Diskon valid HANYA jika final < original dan original > 0
    return final < original && original > 0;
});

// 4. Hitung Persentase
const discountPercentage = computed(() => {
    if (!hasDiscount.value) return 0;
    const price = displayPrice.value;
    const final = displayFinalPrice.value;
    return Math.round(((price - final) / price) * 100);
});

// 5. Label Diskon
const discountLabel = computed(() => {
    if (!hasDiscount.value) return '';
    return currentVariant.value.discount_info?.type === 'percent' ? `${discountPercentage.value}% OFF` : 'SAVE'; 
});

// Deskripsi
const shortDescription = computed(() => {
    const desc = props.product.description || '';
    return desc.length <= 180 ? desc : desc.substring(0, 180) + '...';
});
const showReadMoreBtn = computed(() => (props.product.description || '').length > 180);

// Styling
const addToCartButtonClasses = computed(() => showCartPopup.value ? 'bg-emerald-50 border-emerald-500 text-emerald-700' : 'bg-white border-rose-200 text-gray-600 hover:border-rose-400 hover:text-rose-600');
const wishlistButtonClasses = computed(() => isWished.value ? 'bg-rose-50 border-rose-200 text-rose-600 shadow-inner' : 'bg-white border-gray-200 hover:border-rose-300 text-gray-400 hover:text-rose-500');

// --- ACTIONS ---
const increment = () => { if (currentVariant.value && quantity.value < currentVariant.value.stock) quantity.value++; };
const decrement = () => { if (quantity.value > 1) quantity.value--; };

const goBack = () => {
    if (window.history.length > 1) window.history.back();
    else router.visit('/catalog');
};

const handleShare = async () => {
    const shareData = {
        title: props.product.name,
        text: `Check out ${props.product.name} on Skin Lab Beauty!`,
        url: window.location.href,
    };
    if (navigator.share && window.isSecureContext) {
        try { await navigator.share(shareData); } catch (err) { console.log('Share closed'); }
    } else {
        try {
            await navigator.clipboard.writeText(window.location.href);
            showSharePopup.value = true;
            setTimeout(() => { showSharePopup.value = false; }, 2500);
        } catch (err) { alert('Unable to copy link.'); }
    }
};

const handleAddToCartClick = () => {
    if (!selectedVariantId.value) return;
    addToCart(selectedVariantId.value, quantity.value, {
        onSuccess: () => { 
            showCartPopup.value = true; 
            setTimeout(() => { showCartPopup.value = false; }, 2500); 
        },
        onError: (e) => alert('Error adding to cart')
    });
};

const handleBuyNowClick = () => {
    if (!selectedVariantId.value || isOutOfStock.value) return;
    const user = usePage().props.auth.user;
    if (!user) { router.get('/login'); return; }
    if (user.is_banned) {
        return Swal.fire({ icon: 'error', title: 'Account Restricted', text: 'Suspended.', confirmButtonColor: '#e11d48' });
    }
    router.get('/checkout', { items: [selectedVariantId.value], quantity: quantity.value });
};

const handleWishlistClick = () => {
    if (!currentVariant.value) return;
    const user = usePage().props.auth.user;
    if (!user) { router.get('/login'); return; }
    const action = isWished.value ? removeFromWishlist : addToWishlist;
    action(currentVariant.value.variant_id, {
        onSuccess: () => {
            wishlistMessage.value = isWished.value ? 'Removed from Wishlist' : 'Added to Wishlist!';
            showWishlistPopup.value = true;
            setTimeout(() => { showWishlistPopup.value = false; }, 2500);
        }
    });
};
</script>

<template>
    <Head :title="product.name" />

    <Transition enter-active-class="ease-out duration-300 transform" enter-from-class="opacity-0 translate-y-[-1rem]" enter-to-class="opacity-100 translate-y-0" leave-active-class="ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-[-1rem]">
        <div v-if="showCartPopup" class="fixed top-24 right-4 md:right-8 z-[100] backdrop-blur-md bg-white/95 border border-emerald-100 shadow-xl p-4 rounded-2xl flex items-center gap-4 w-auto min-w-[300px] border-l-4 border-l-emerald-500">
            <div class="bg-emerald-100 p-2.5 rounded-full shadow-sm"><ShoppingCart class="w-5 h-5 text-emerald-600" /></div>
            <div><h4 class="font-bold text-gray-800 text-sm">Added to Cart</h4><p class="text-xs text-gray-500 mt-0.5">Ready for checkout!</p></div>
        </div>
    </Transition>
    <Transition enter-active-class="ease-out duration-300 transform" enter-from-class="opacity-0 translate-y-[-1rem]" enter-to-class="opacity-100 translate-y-0" leave-active-class="ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-[-1rem]">
        <div v-if="showWishlistPopup" class="fixed top-24 right-4 md:right-8 z-[100] backdrop-blur-md bg-white/95 border border-rose-100 shadow-xl p-4 rounded-2xl flex items-center gap-4 w-auto min-w-[300px] border-l-4 border-l-rose-500">
            <div class="bg-rose-100 p-2.5 rounded-full shadow-sm"><Heart class="w-5 h-5 text-rose-600 fill-current" /></div>
            <div><h4 class="font-bold text-gray-800 text-sm">Wishlist Updated</h4><p class="text-xs text-gray-500 mt-0.5">{{ wishlistMessage }}</p></div>
        </div>
    </Transition>
    <Transition enter-active-class="ease-out duration-300 transform" enter-from-class="opacity-0 translate-y-[-1rem]" enter-to-class="opacity-100 translate-y-0" leave-active-class="ease-in duration-200" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-[-1rem]">
        <div v-if="showSharePopup" class="fixed top-24 right-4 md:right-8 z-[100] backdrop-blur-md bg-white/95 border border-blue-100 shadow-xl p-4 rounded-2xl flex items-center gap-4 w-auto min-w-[300px] border-l-4 border-l-blue-500">
            <div class="bg-blue-100 p-2.5 rounded-full shadow-sm"><Copy class="w-5 h-5 text-blue-600" /></div>
            <div><h4 class="font-bold text-gray-800 text-sm">Link Copied!</h4><p class="text-xs text-gray-500 mt-0.5">Share it with your friends.</p></div>
        </div>
    </Transition>

    <div class="min-h-screen py-8 md:py-16 bg-gradient-to-br from-rose-50 via-slate-50 to-rose-100 relative overflow-hidden">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjEiIGZpbGw9IiM5OTkwOTkiIGZpbGwtb3BhY2l0eT0iMC4wNSIvPjwvc3ZnPg==')] opacity-50 pointer-events-none"></div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12 relative z-10">
            
            <div class="bg-white/80 backdrop-blur-sm rounded-[2.5rem] shadow-2xl shadow-rose-100/60 border border-white/60 overflow-hidden relative">
                
                <div class="p-6 md:p-12 md:flex gap-16">
                    
                    <div class="md:w-5/12 flex flex-col relative md:sticky md:top-6 h-fit">
                        <button @click="goBack" class="self-start inline-flex items-center gap-2 text-gray-400 hover:text-gray-800 transition-colors duration-300 mb-6 text-xs font-bold uppercase tracking-widest group">
                            <span class="bg-white border border-gray-100 group-hover:border-gray-300 w-8 h-8 flex items-center justify-center rounded-full transition-all shadow-sm"><ChevronDown class="w-4 h-4 rotate-90" /></span> Back
                        </button>
                        
                        <div class="relative w-full aspect-square bg-white rounded-3xl overflow-hidden group border border-gray-100 shadow-sm">
                            <Swiper :modules="modules" :slides-per-view="1" :space-between="0" :effect="'fade'" :fadeEffect="{ crossFade: true }" :pagination="{ clickable: true, dynamicBullets: true }" :navigation="true" :loop="allImages.length > 1" @swiper="onSwiper" class="h-full w-full">
                                <SwiperSlide v-for="(img, index) in allImages" :key="index">
                                    <div class="w-full h-full flex items-center justify-center relative p-8 cursor-zoom-in">
                                        <img :src="img.src" :alt="product.name" class="h-full w-auto object-contain max-w-full drop-shadow-xl mix-blend-multiply transition-transform duration-700 hover:scale-110" :class="{'grayscale opacity-50': isOutOfStock}" />
                                        <div v-if="isOutOfStock" class="absolute inset-0 flex items-center justify-center bg-white/60 backdrop-blur-[2px] z-20">
                                            <span class="bg-gray-900 text-white px-6 py-2.5 rounded-full font-black text-sm tracking-[0.2em] uppercase border border-white/20 shadow-xl">Sold Out</span>
                                        </div>
                                    </div>
                                </SwiperSlide>
                            </Swiper>
                        </div>
                    </div>

                    <div class="md:w-7/12 mt-10 md:mt-0 flex flex-col">
                        
                        <div class="mb-6 border-b border-gray-100 pb-6">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <span v-if="product.brand" class="text-xs font-black text-rose-500 uppercase tracking-[0.15em]">{{ product.brand.name }}</span>
                                    <span class="w-1 h-1 bg-gray-300 rounded-full"></span>
                                    <span v-if="product.category" class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ product.category.name }}</span>
                                </div>
                                <button @click="handleShare" class="text-gray-300 hover:text-rose-500 transition-colors p-2 hover:bg-rose-50 rounded-full" title="Share">
                                    <Share2 class="w-5 h-5" />
                                </button>
                            </div>
                            
                            <h1 class="text-3xl md:text-5xl font-black text-gray-900 leading-tight mb-4 tracking-tight font-serif">{{ product.name }}</h1>
                            
                            <div class="flex items-center gap-4">
                                <div class="flex items-center gap-1.5 bg-amber-50/50 px-3 py-1.5 rounded-full border border-amber-100">
                                    <Star class="w-4 h-4 text-amber-400 fill-current" />
                                    <span class="text-sm font-bold text-gray-800 pt-0.5">{{ averageRating > 0 ? averageRating : 'New' }}</span>
                                    <span class="text-xs text-gray-400 pt-0.5 ml-1">({{ product.reviews?.length || 0 }} reviews)</span>
                                </div>
                                <div v-if="product.tags" class="flex flex-wrap gap-2">
                                    <span v-for="tag in product.tags.slice(0, 3)" :key="tag" class="text-[10px] font-bold text-gray-500 bg-white border border-gray-200 px-2.5 py-1 rounded-full uppercase tracking-wide">{{ tag }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="currentVariant" class="mb-6">
                            <div class="flex items-end gap-3">
                                <p class="text-5xl font-black text-gray-900 tracking-tighter leading-none">
                                    {{ formatCurrency(displayFinalPrice) }}
                                </p>
                                <div v-if="hasDiscount" class="flex flex-col mb-1">
                                    <span class="bg-rose-100 text-rose-600 text-[10px] font-black px-2 py-0.5 rounded w-fit mb-0.5">{{ discountLabel }}</span>
                                    <span class="text-lg text-gray-400 line-through decoration-rose-300 decoration-2 font-medium">{{ formatCurrency(displayPrice) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h3 class="text-xs font-extrabold text-gray-900 mb-3 uppercase tracking-widest border-l-4 border-rose-500 pl-3">Details</h3>
                            <div class="text-gray-600 text-sm leading-relaxed mb-6">
                                <p :class="{'line-clamp-3': !isDescExpanded && showReadMoreBtn}" class="transition-all duration-300">
                                    {{ isDescExpanded ? product.description : shortDescription }}
                                </p>
                                <button v-if="showReadMoreBtn" @click="isDescExpanded = !isDescExpanded" class="group flex items-center gap-1 text-rose-600 font-bold text-xs mt-2 hover:text-rose-700 transition-colors">
                                    {{ isDescExpanded ? 'Show Less' : 'Read More' }}
                                    <component :is="isDescExpanded ? ChevronUp : ChevronDown" class="w-3 h-3 group-hover:translate-y-0.5 transition-transform" />
                                </button>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <ShieldCheck class="w-5 h-5 text-emerald-500" />
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-900 uppercase">100% Original</span>
                                        <span class="text-[9px] text-gray-400">Money back guarantee</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <PackageCheck class="w-5 h-5 text-blue-500" />
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-900 uppercase">BPOM Certified</span>
                                        <span class="text-[9px] text-gray-400">Safe for skin</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <Truck class="w-5 h-5 text-rose-500" />
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-gray-900 uppercase">Fast Delivery</span>
                                        <span class="text-[9px] text-gray-400">Nationwide shipping</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-auto pt-6 border-t border-gray-100 flex flex-col gap-6">
                            
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-2">
                                <div class="w-full sm:w-auto">
                                    <label class="block text-xs font-extrabold text-gray-900 uppercase tracking-widest mb-3">Select Variant</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button 
                                            v-for="variant in product.variants" 
                                            :key="variant.variant_id" 
                                            @click="selectedVariantId = variant.variant_id" 
                                            :class="[
                                                variant.variant_id === selectedVariantId 
                                                    ? 'ring-2 ring-rose-500 text-rose-600 bg-rose-50 border-transparent shadow-sm font-bold' 
                                                    : 'bg-white border-gray-200 text-gray-600 hover:border-gray-400 hover:text-gray-900',
                                                variant.stock === 0 ? 'opacity-50 border-dashed cursor-not-allowed bg-gray-50 text-gray-400 decoration-line-through' : ''
                                            ]" 
                                            class="px-4 py-2 border rounded-lg text-xs transition-all duration-200 min-w-[70px]"
                                        >
                                            {{ variant.volume }}
                                        </button>
                                    </div>
                                </div>
                                
                                <div v-if="currentVariant && !isOutOfStock" class="flex flex-col items-end">
                                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">STOCK AVAILABLE</span>
                                    <div class="flex items-center gap-2">
                                        <PackageCheck v-if="!isLowStock" class="w-4 h-4 text-emerald-600" />
                                        <AlertCircle v-if="isLowStock" class="w-4 h-4 text-amber-600 animate-pulse" />
                                        <span class="text-xl font-black" 
                                              :class="isLowStock ? 'text-amber-700' : 'text-emerald-700'">
                                            {{ currentVariant.stock }}
                                        </span>
                                    </div>
                                    <div v-if="isLowStock" class="mt-1">
                                        <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">Low stock</span>
                                    </div>
                                </div>
                                
                                <div v-if="isOutOfStock" class="flex flex-col items-end">
                                    <span class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">STOCK STATUS</span>
                                    <div class="flex items-center gap-2">
                                        <AlertCircle class="w-4 h-4 text-gray-500" />
                                        <span class="text-xl font-black text-gray-600">SOLD OUT</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-stretch gap-4">
                                <div class="flex items-center gap-2 shrink-0">
                                    <button @click="decrement" :disabled="quantity <= 1 || isOutOfStock" class="w-11 h-11 flex items-center justify-center rounded-xl border-2 border-gray-100 text-gray-600 hover:border-rose-300 hover:text-rose-600 active:scale-95 transition-all bg-white"><Minus class="w-4 h-4" /></button>
                                    <input type="text" v-model="quantity" readonly class="w-10 text-center bg-transparent border-none text-xl font-black text-gray-900 focus:ring-0 p-0" />
                                    <button @click="increment" :disabled="!currentVariant || quantity >= currentVariant.stock || isOutOfStock" class="w-11 h-11 flex items-center justify-center rounded-xl border-2 border-gray-100 text-gray-600 hover:border-rose-300 hover:text-rose-600 active:scale-95 transition-all bg-white"><Plus class="w-4 h-4" /></button>
                                </div>

                                <button @click="handleWishlistClick" :disabled="!currentVariant || isAdding || isRemoving" :class="wishlistButtonClasses" class="w-[56px] h-[56px] flex items-center justify-center rounded-2xl border-2 transition-all active:scale-95 hover:shadow-lg shrink-0" title="Wishlist"><Heart class="w-6 h-6 transition-transform hover:scale-110" :class="isWished ? 'fill-current' : ''" /></button>

                                <button @click="handleAddToCartClick" :disabled="isOutOfStock || isAddingToCart || showCartPopup" :class="addToCartButtonClasses" class="w-[56px] h-[56px] flex items-center justify-center rounded-2xl border-2 transition-all active:scale-95 disabled:opacity-50 hover:shadow-lg shrink-0" title="Add to Cart"><ShoppingCart class="w-6 h-6 transition-transform hover:scale-110" /></button>

                                <button @click="handleBuyNowClick" :disabled="isOutOfStock || isAddingToCart" class="flex-1 h-[56px] flex items-center justify-center gap-2 bg-gradient-to-r from-rose-500 to-pink-600 text-white rounded-2xl font-bold text-sm shadow-xl shadow-rose-200 hover:shadow-rose-400/50 hover:shadow-2xl transition-all duration-300 active:scale-95 disabled:from-gray-300 disabled:to-gray-300 disabled:shadow-none disabled:cursor-not-allowed group">
                                    <Zap class="w-5 h-5 group-hover:animate-pulse fill-white/20" />
                                    {{ isOutOfStock ? 'SOLD OUT' : 'BUY NOW' }}
                                </button>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>

            <ProductReviews :reviews="product.reviews" />
        </div>
    </div>
</template>

<style>
.swiper-button-next, .swiper-button-prev { 
    color: #111827 !important; 
    background: rgba(255, 255, 255, 0.7); 
    width: 44px; 
    height: 44px; 
    border-radius: 50%; 
    backdrop-filter: blur(8px); 
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); 
    border: 1px solid rgba(255, 255, 255, 0.5); 
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
}
.swiper-button-next:hover, .swiper-button-prev:hover { 
    background: #fff; 
    transform: scale(1.1); 
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1); 
    color: #e11d48 !important; 
}
.swiper-button-next:after, .swiper-button-prev:after { 
    font-size: 18px !important; 
    font-weight: 800; 
}
.swiper-pagination-bullet-active { 
    background-color: #e11d48 !important; 
    transform: scale(1.2); 
}
</style>