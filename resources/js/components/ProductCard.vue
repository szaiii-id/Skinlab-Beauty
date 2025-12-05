<script setup>
import { Link } from '@inertiajs/vue3';
import { useFormatting } from '@/composables/useFormatting'; 
import { computed } from 'vue';

const props = defineProps({
    product: {
        type: Object,
        required: true
    }
});

const { formatCurrency } = useFormatting();

// --- LOGIC (TIDAK BERUBAH) ---
const displayPrice = computed(() => {
    return props.product.variants && props.product.variants.length > 0 
        ? props.product.variants[0].price 
        : (props.product.price || 0);
});

const displayImage = computed(() => {
    if (props.product.variants && props.product.variants.length > 0 && props.product.variants[0].image_url) {
        return props.product.variants[0].image_url;
    }
    return props.product.thumbnail || null; 
});

const displayCategory = computed(() => props.product.category?.name || 'Beauty');
const displayBrand = computed(() => props.product.brand?.name || '');

const discountBadge = computed(() => {
    if (props.product.variants && props.product.variants.length > 0) {
        return props.product.variants[0].discount_info; 
    }
    return null;
});

const displayFinalPrice = computed(() => {
    if (props.product.variants && props.product.variants.length > 0) {
        return props.product.variants[0].final_price;
    }
    return props.product.price || 0;
});

const hasDiscount = computed(() => {
    return displayFinalPrice.value < displayPrice.value;
});

const totalStock = computed(() => {
    if (props.product.variants && props.product.variants.length > 0) {
        return props.product.variants.reduce((sum, v) => sum + v.stock, 0);
    }
    return 0;
});

const displayTags = computed(() => {
    return props.product.tags ? props.product.tags.slice(0, 3) : [];
});

// --- LOGIC RATING ---
// Pastikan di Backend ProductResource sudah dikirim 'rating' dan 'review_count'
// Jika belum ada, default ke 0 agar tidak error
const ratingValue = computed(() => props.product.rating || 0);
const reviewCount = computed(() => props.product.review_count || 0);
</script>

<template>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col h-full overflow-hidden group">
        
        <Link :href="`/products/${product.slug}/${product.id}`" class="relative w-full aspect-square bg-[#F5F5F7] overflow-hidden block">
            
            <img 
                v-if="displayImage" 
                :src="displayImage" 
                :alt="product.name"
                class="w-full h-full object-cover object-center transition-transform duration-700 ease-in-out group-hover:scale-110"
                :class="{ 'grayscale opacity-70': totalStock === 0 }"
            />
            <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-300">
                <span class="text-xs font-medium">No Image</span>
            </div>

            <div class="absolute top-3 left-3 flex flex-col items-start gap-1.5 z-10">
                <div v-if="discountBadge" 
                     class="bg-rose-600 text-white text-[10px] font-bold px-2.5 py-1 rounded shadow-sm tracking-wide uppercase">
                    {{ discountBadge.type === 'percent' ? `${discountBadge.value}% OFF` : 'SALE' }}
                </div>
                <slot name="badge"></slot>
            </div>

            <div v-if="totalStock === 0" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] flex items-center justify-center z-20">
                <span class="bg-black text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-widest border border-white/20">
                    Sold Out
                </span>
            </div>
        </Link>
        
        <div class="p-4 flex flex-col flex-1 relative">
            
            <div class="flex justify-between items-center mb-1.5">
                <span class="text-[10px] font-extrabold text-gray-400 uppercase tracking-widest truncate max-w-[60%] hover:text-rose-500 transition-colors">
                    {{ displayBrand }}
                </span>
                <span class="text-[10px] font-semibold flex items-center gap-1.5" 
                      :class="totalStock > 0 ? 'text-emerald-600' : 'text-gray-300'">
                    <span class="w-1.5 h-1.5 rounded-full" :class="totalStock > 0 ? 'bg-emerald-500' : 'bg-gray-300'"></span>
                    <span class="opacity-80">{{ totalStock > 0 ? `Stock: ${totalStock}` : '-' }}</span>
                </span>
            </div>

            <Link :href="`/products/${product.slug}/${product.id}`">
                <h2 class="text-sm font-bold text-gray-900 leading-snug mb-1 line-clamp-2 min-h-[2.5rem] group-hover:text-rose-600 transition-colors">
                    {{ product.name }}
                </h2>
            </Link>

            <div class="flex items-center gap-1 mb-3">
                <svg class="w-3 h-3" :class="ratingValue > 0 ? 'text-yellow-400 fill-current' : 'text-gray-300 fill-current'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                
                <span class="text-[10px] font-bold text-gray-700 pt-0.5">
                    {{ ratingValue > 0 ? ratingValue : 'No reviews' }}
                </span>
                <span v-if="reviewCount > 0" class="text-[10px] text-gray-400 pt-0.5">
                    ({{ reviewCount }})
                </span>
            </div>

            <div class="flex flex-wrap gap-1 mb-4 min-h-[22px]">
                <span v-for="tag in displayTags" :key="tag" 
                      class="px-2 py-0.5 bg-gray-100 text-gray-500 text-[9px] rounded-full font-semibold uppercase tracking-wide border border-gray-100">
                    {{ tag }}
                </span>
            </div>

            <div class="mt-auto flex items-end justify-between border-t border-gray-50 pt-3">
                <div class="flex flex-col">
                    <div v-if="hasDiscount" class="flex flex-col leading-none">
                        <span class="text-[10px] text-gray-400 line-through decoration-rose-300 mb-0.5">
                            {{ formatCurrency(displayPrice) }}
                        </span>
                        <span class="text-base font-extrabold text-rose-600">
                            {{ formatCurrency(displayFinalPrice) }}
                        </span>
                    </div>
                    <div v-else>
                        <span class="text-base font-bold text-gray-900">
                            {{ formatCurrency(displayPrice) }}
                        </span>
                    </div>
                </div>

                <Link :href="`/products/${product.slug}/${product.id}`" 
                      class="px-4 py-1.5 bg-gray-900 text-white rounded-full hover:bg-rose-600 transition-all shadow-sm hover:shadow-md text-[11px] font-bold tracking-wide">
                    View
                </Link>
            </div>
        </div>
    </div>
</template>