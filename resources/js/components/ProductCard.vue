<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { useFormatting } from '@/composables/useFormatting'; 
import { computed } from 'vue';
import { ArrowRight } from 'lucide-vue-next'; 

const props = defineProps({
    product: {
        type: Object,
        required: true
    }
});

const { formatCurrency } = useFormatting();
const page = usePage();

// --- LOGIC (TETAP SAMA) ---
const user = computed(() => page.props.auth.user); 
const isPerfectMatch = computed(() => {
    const profile = user.value?.skin_profile || null; 
    if (!profile || !profile.skin_type) return false;
    const productTags = props.product.suitability_tags;
    if (!productTags || productTags.length === 0) return false;
    const userSkinType = profile.skin_type; 
    const cleanUserType = userSkinType.split(' ')[0]; 
    return productTags.includes(userSkinType) || productTags.includes(cleanUserType);
});
const reviewCount = computed(() => props.product.review_count || props.product.reviews?.length || 0);
const ratingValue = computed(() => {
    if (props.product.rating !== undefined && props.product.rating !== null) { return Number(props.product.rating); }
    if (props.product.reviews?.length > 0) {
        const total = props.product.reviews.reduce((acc, review) => acc + Number(review.rating), 0);
        return (total / props.product.reviews.length).toFixed(1);
    }
    return 0;
});
const displayPrice = computed(() => {
    const price = props.product.variants?.length > 0 ? props.product.variants[0].price : (props.product.price || 0);
    return Number(price);
});
const displayFinalPrice = computed(() => {
    if (props.product.variants?.length > 0) {
        const v = props.product.variants[0];
        return Number(v.final_price ?? v.price);
    }
    return Number(props.product.price || 0);
});
const hasDiscount = computed(() => {
    const original = displayPrice.value;
    const final = displayFinalPrice.value;
    return final < original && original > 0;
});
const displayImage = computed(() => (props.product.variants?.length > 0 && props.product.variants[0].image_url) ? props.product.variants[0].image_url : (props.product.thumbnail || null));
const displayBrand = computed(() => props.product.brand?.name || '');
const discountBadge = computed(() => props.product.variants?.length > 0 ? props.product.variants[0].discount_info : null);
const totalStock = computed(() => props.product.variants?.length > 0 ? props.product.variants.reduce((sum, v) => sum + v.stock, 0) : 0);
const displayTags = computed(() => props.product.tags ? props.product.tags.slice(0, 3) : []);
</script>

<template>
    <div class="bg-white rounded-xl md:rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col h-full overflow-hidden group relative">
        
        <Link :href="`/products/${product.slug}/${product.id}`" class="relative w-full aspect-square bg-[#F5F5F7] overflow-hidden block p-3">
            
            <img 
                v-if="displayImage" 
                :src="displayImage" 
                :alt="product.name"
                class="w-full h-full object-cover object-center rounded-lg md:rounded-xl transition-transform duration-700 ease-in-out group-hover:scale-105 mix-blend-multiply"
                :class="{ 'grayscale opacity-70': totalStock === 0 }"
            />
            <div v-else class="w-full h-full flex flex-col items-center justify-center text-gray-300">
                <span class="text-xs font-medium">No Image</span>
            </div>

            <div class="absolute top-2 left-2 md:top-3 md:left-3 flex flex-col items-start gap-1 md:gap-2 z-10">
                <div v-if="isPerfectMatch" 
                     class="bg-emerald-500 text-white text-[9px] md:text-[10px] font-bold px-2 py-0.5 md:px-3 md:py-1 rounded-full shadow-md tracking-wide uppercase flex items-center gap-1 animate-in fade-in zoom-in duration-300 border border-emerald-400/50 backdrop-blur-sm bg-opacity-95">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 md:w-3.5 md:h-3.5 fill-current" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
                    <span class="hidden sm:inline">Perfect Match</span>
                    <span class="sm:hidden">Match</span>
                </div>
                <slot name="badge"></slot>
            </div>

            <div v-if="discountBadge && hasDiscount" 
                 class="absolute top-2 right-2 md:top-3 md:right-3 z-10 bg-rose-600 text-white text-[9px] md:text-[10px] font-bold px-2 py-0.5 md:px-3 md:py-1 rounded-full shadow-sm tracking-wide uppercase border border-rose-400/50 backdrop-blur-sm bg-opacity-95">
                {{ discountBadge.type === 'percent' ? `-${discountBadge.value}%` : 'SALE' }}
            </div>

            <div v-if="totalStock === 0" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] flex items-center justify-center z-20">
                <span class="bg-black text-white text-[9px] md:text-[10px] font-bold px-2 py-1 md:px-3 md:py-1 rounded-full uppercase tracking-widest border border-white/20">
                    Sold Out
                </span>
            </div>
        </Link>
        
        <div class="p-2.5 md:p-4 flex flex-col flex-1 relative">
            
            <div class="flex justify-between items-center mb-1 gap-1">
                <span class="text-[9px] md:text-[10px] font-extrabold text-gray-400 uppercase tracking-widest truncate max-w-[60%] hover:text-rose-500 transition-colors">
                    {{ displayBrand }}
                </span>
                <span class="text-[9px] md:text-[10px] font-semibold flex items-center gap-1" 
                      :class="totalStock > 0 ? 'text-emerald-600' : 'text-gray-300'">
                    <span class="w-1.5 h-1.5 rounded-full" :class="totalStock > 0 ? 'bg-emerald-500' : 'bg-gray-300'"></span>
                    <span class="opacity-80">{{ totalStock > 0 ? `${totalStock}` : '-' }}</span>
                </span>
            </div>

            <Link :href="`/products/${product.slug}/${product.id}`">
                <h2 class="text-xs md:text-sm font-bold text-gray-900 leading-snug mb-1 line-clamp-2 min-h-[2.5em] group-hover:text-rose-600 transition-colors">
                    {{ product.name }}
                </h2>
            </Link>

            <div class="flex items-center gap-1 mb-2 md:mb-3">
                <svg class="w-3 h-3 md:w-3.5 md:h-3.5" :class="ratingValue > 0 ? 'text-amber-400 fill-current' : 'text-gray-300 fill-current'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                
                <span class="text-[10px] md:text-[11px] font-bold text-gray-800 pt-0.5">
                    {{ ratingValue > 0 ? ratingValue : 'New' }}
                </span>
                <span class="text-[9px] md:text-[10px] text-gray-400 pt-0.5 ml-0.5">
                    ({{ reviewCount }})
                </span>
            </div>

            <div class="hidden sm:flex flex-wrap gap-1 md:gap-1.5 mb-2 md:mb-4 min-h-[20px] md:min-h-[22px]">
                <span v-for="tag in displayTags" :key="tag" 
                      class="px-1.5 py-0.5 bg-gray-50 border border-gray-100 text-gray-500 text-[8px] md:text-[9px] rounded font-bold uppercase tracking-wider">
                    {{ tag }}
                </span>
            </div>

            <div class="mt-auto flex items-end justify-between border-t border-gray-50 pt-2 md:pt-3">
                <div class="flex flex-col">
                    <div v-if="hasDiscount" class="flex flex-col leading-none">
                        <span class="text-[9px] md:text-[10px] text-gray-400 line-through decoration-rose-300 mb-0.5">
                            {{ formatCurrency(displayPrice) }}
                        </span>
                        <span class="text-sm md:text-base font-extrabold text-rose-600">
                            {{ formatCurrency(displayFinalPrice) }}
                        </span>
                    </div>
                    <div v-else>
                        <span class="text-sm md:text-base font-bold text-gray-900">
                            {{ formatCurrency(displayPrice) }}
                        </span>
                    </div>
                </div>

                <Link :href="`/products/${product.slug}/${product.id}`" 
                      class="hidden md:flex items-center gap-1 px-4 py-1.5 bg-rose-50 text-rose-600 border border-rose-100 rounded-full hover:bg-rose-600 hover:text-white hover:border-rose-600 transition-all shadow-sm hover:shadow-md text-[11px] font-bold tracking-wide group/btn">
                    <span>View</span>
                    <ArrowRight class="w-3 h-3 transition-transform group-hover/btn:translate-x-0.5" />
                </Link>
            </div>
        </div>
    </div>
</template>