<script setup>
import { computed } from 'vue';
import { Star } from 'lucide-vue-next';

// Props: Receive data from parent
const props = defineProps({
    reviews: {
        type: Array,
        default: () => []
    }
});

// Helper: Calculate Average Rating
const averageRating = computed(() => {
    if (!props.reviews || props.reviews.length === 0) return 0;
    const total = props.reviews.reduce((acc, review) => acc + review.rating, 0);
    return (total / props.reviews.length).toFixed(1);
});

// Helper: Format Date (English Format)
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric', month: 'long', day: 'numeric'
    });
};

// Helper: Get User Name safely
const getDisplayName = (user) => {
    return user ? user.name : 'Anonymous User';
};
</script>

<template>
    <div class="bg-white rounded-lg shadow-xl overflow-hidden p-8">
        <div class="flex items-center justify-between mb-6 border-b border-gray-100 pb-4">
            <h2 class="text-2xl font-light text-gray-900">Customer Reviews</h2>
            
            <div class="text-right" v-if="reviews.length > 0">
                <div class="flex items-center gap-2 justify-end">
                    <span class="text-3xl font-bold text-gray-900">{{ averageRating }}</span>
                    <span class="text-gray-400">/ 5.0</span>
                </div>
                <div class="flex text-amber-400 justify-end text-sm">
                    <Star v-for="i in 5" :key="i" class="w-4 h-4" :class="i <= Math.round(averageRating) ? 'fill-current' : 'text-gray-300'" />
                </div>
                <p class="text-xs text-gray-500 mt-1">Based on {{ reviews.length }} reviews</p>
            </div>
        </div>

        <div v-if="reviews.length > 0" class="space-y-6">
            <div v-for="review in reviews" :key="review.id" class="border-b border-gray-50 pb-6 last:border-0">
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
                            {{ review.comment || 'No written comment provided.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-10 bg-gray-50 rounded-lg border border-dashed border-gray-200">
            <div class="mx-auto w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                <Star class="w-6 h-6 text-gray-400" />
            </div>
            <p class="text-gray-600 font-medium">No reviews yet</p>
            <p class="text-sm text-gray-500 mt-1">Be the first to review this product after purchase!</p>
        </div>
    </div>
</template>