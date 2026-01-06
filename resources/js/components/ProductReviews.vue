<script setup>
import { computed, ref } from 'vue';
import { Star, ShieldCheck, X, ImageIcon, Store, ThumbsUp } from 'lucide-vue-next';

const props = defineProps({
    reviews: {
        type: Array,
        default: () => []
    }
});

// --- LIGHTBOX STATE ---
const showLightbox = ref(false);
const activeImage = ref(null);

const openLightbox = (imageUrl) => {
    activeImage.value = imageUrl;
    showLightbox.value = true;
    document.body.style.overflow = 'hidden';
};

const closeLightbox = () => {
    showLightbox.value = false;
    activeImage.value = null;
    document.body.style.overflow = 'auto';
};

// --- STATS LOGIC ---
const averageRating = computed(() => {
    if (!props.reviews.length) return 0;
    const sum = props.reviews.reduce((acc, r) => acc + r.rating, 0);
    return (sum / props.reviews.length).toFixed(1);
});

const totalReviews = computed(() => props.reviews.length);

const starCounts = computed(() => {
    const counts = { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };
    props.reviews.forEach(r => counts[r.rating]++);
    return counts;
});

const getPercentage = (star) => {
    if (!totalReviews.value) return 0;
    return Math.round((starCounts.value[star] / totalReviews.value) * 100);
};

// --- HELPERS ---
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric'
    });
};

const getInitials = (name) => name ? name.charAt(0).toUpperCase() : 'U';
</script>

<template>
    <div class="bg-white rounded-2xl md:rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
        
        <div class="p-6 md:p-8 border-b border-gray-100 bg-gradient-to-b from-gray-50/50 to-white">
            <h2 class="text-xl md:text-2xl font-black text-gray-900 mb-6">Customer Reviews</h2>
            
            <div class="flex flex-col md:grid md:grid-cols-3 gap-8 items-center">
                <div class="text-center md:text-left w-full border-b md:border-b-0 md:border-r border-gray-100 pb-6 md:pb-0 md:pr-8">
                    <div class="flex items-end justify-center md:justify-start gap-2 mb-1">
                        <span class="text-5xl md:text-6xl font-black text-gray-900 tracking-tighter">{{ averageRating }}</span>
                        <span class="text-lg md:text-xl font-bold text-gray-400 mb-2">/ 5</span>
                    </div>
                    <div class="flex justify-center md:justify-start gap-1 text-amber-400 mb-2">
                        <Star v-for="i in 5" :key="i" class="w-5 h-5" :class="i <= Math.round(averageRating) ? 'fill-current' : 'text-gray-200'" />
                    </div>
                    <p class="text-sm font-medium text-gray-500">{{ totalReviews }} verified reviews</p>
                </div>

                <div class="col-span-2 space-y-2 w-full">
                    <div v-for="star in [5, 4, 3, 2, 1]" :key="star" class="flex items-center gap-3">
                        <div class="flex items-center gap-1 w-8 md:w-12 shrink-0">
                            <span class="text-sm font-bold text-gray-700">{{ star }}</span>
                            <Star class="w-3.5 h-3.5 text-gray-400 fill-gray-400" />
                        </div>
                        <div class="flex-1 h-2 md:h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            <div 
                                class="h-full bg-amber-400 rounded-full transition-all duration-500 ease-out" 
                                :style="{ width: getPercentage(star) + '%' }"
                            ></div>
                        </div>
                        <span class="text-xs font-medium text-gray-400 w-8 text-right">{{ getPercentage(star) }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 md:p-8">
            <div v-if="reviews.length > 0" class="space-y-8 md:space-y-10">
                <div v-for="review in reviews" :key="review.id" class="border-b border-gray-50 last:border-0 pb-8 md:pb-10 last:pb-0">
                    
                    <div class="flex gap-4">
                        <div class="shrink-0">
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-gradient-to-br from-rose-100 to-pink-200 flex items-center justify-center text-rose-700 font-black text-base md:text-lg shadow-sm border border-white ring-2 ring-rose-50">
                                {{ getInitials(review.user?.name) }}
                            </div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-1.5">
                                <div>
                                    <h4 class="font-bold text-gray-900 text-sm md:text-base flex flex-wrap items-center gap-2">
                                        {{ review.user?.name || 'Anonymous' }}
                                        <span class="inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold uppercase tracking-wider border border-emerald-100">
                                            <ShieldCheck class="w-3 h-3" /> Verified
                                        </span>
                                    </h4>
                                </div>
                                <span class="text-xs font-medium text-gray-400 mt-1 sm:mt-0">{{ formatDate(review.created_at) }}</span>
                            </div>

                            <div class="flex text-amber-400 mb-3">
                                <Star v-for="i in 5" :key="i" class="w-3.5 h-3.5" :class="i <= review.rating ? 'fill-current' : 'text-gray-200'" />
                            </div>

                            <div v-if="review.image" class="mb-4">
                                <div 
                                    @click="openLightbox(`/storage/${review.image}`)"
                                    class="relative w-16 h-16 md:w-20 md:h-20 rounded-xl overflow-hidden border border-gray-200 group cursor-zoom-in shadow-sm hover:shadow-md transition-all bg-gray-50"
                                >
                                    <img :src="`/storage/${review.image}`" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors flex items-center justify-center">
                                        <ImageIcon class="w-5 h-5 md:w-6 md:h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity drop-shadow-md" />
                                    </div>
                                </div>
                            </div>

                            <p class="text-gray-700 text-sm leading-relaxed mb-4 font-medium break-words">
                                {{ review.comment || 'No comment provided.' }}
                            </p>

                            <div v-if="review.admin_reply" class="mt-4 p-4 bg-gray-50 rounded-xl md:rounded-2xl border border-gray-100 relative">
                                <div class="absolute -top-2 left-6 w-4 h-4 bg-gray-50 border-t border-l border-gray-100 transform rotate-45"></div>
                                
                                <div class="flex items-center justify-between mb-2 relative z-10">
                                    <div class="flex items-center gap-2">
                                        <div class="w-5 h-5 md:w-6 md:h-6 rounded-full bg-rose-600 flex items-center justify-center text-white shadow-sm">
                                            <Store class="w-3 h-3" />
                                        </div>
                                        <span class="text-xs md:text-sm font-bold text-gray-900">Skin Lab Beauty</span>
                                        <span class="px-1.5 py-0.5 bg-rose-100 text-rose-600 text-[9px] font-bold rounded uppercase">Seller</span>
                                    </div>
                                    <span class="text-[10px] md:text-xs text-gray-400 font-medium">
                                        {{ formatDate(review.reply_at || review.updated_at) }}
                                    </span>
                                </div>
                                <p class="text-xs md:text-sm text-gray-600 leading-relaxed pl-7 md:pl-8">
                                    {{ review.admin_reply }}
                                </p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-12 md:py-16">
                <div class="w-16 h-16 md:w-20 md:h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                    <Star class="w-6 h-6 md:w-8 md:h-8 text-gray-300" />
                </div>
                <h3 class="text-base md:text-lg font-bold text-gray-900">No reviews yet</h3>
                <p class="text-gray-500 text-sm mt-1">Be the first to share your experience with this product!</p>
            </div>
        </div>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="showLightbox" 
                class="fixed inset-0 z-[9999] bg-black/90 backdrop-blur-sm flex items-center justify-center p-4" 
                @click="closeLightbox"
            >
                <button 
                    class="absolute top-4 right-4 md:top-6 md:right-6 text-white/70 hover:text-white bg-white/10 hover:bg-white/20 p-2 rounded-full transition-all z-50 backdrop-blur-md" 
                    @click="closeLightbox"
                >
                    <X class="w-6 h-6 md:w-8 md:h-8" />
                </button>

                <img 
                    :src="activeImage" 
                    class="max-w-full max-h-[85vh] rounded-lg shadow-2xl object-contain animate-in zoom-in-95 duration-300 select-none" 
                    @click.stop 
                />
            </div>
        </Transition>

    </div>
</template>