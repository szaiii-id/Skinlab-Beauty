<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Star, X, MessageSquarePlus, Camera, Trash2, ChevronDown } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    product: Object,
    orderId: Number,
});

const emit = defineEmits(['close']);

const form = useForm({
    product_id: '',
    order_id: '',
    rating: 0,
    comment: '',
    image: null
});

const imagePreview = ref(null);

// Dynamic Helper Text
const ratingText = computed(() => {
    const texts = ['Tap a Star', 'Terrible', 'Bad', 'Average', 'Good', 'Perfect!'];
    return texts[form.rating];
});

// Reset form on open
watch(() => props.show, (newVal) => {
    if (newVal && props.product) {
        form.reset();
        form.product_id = props.product.id;
        form.order_id = props.orderId;
        form.rating = 0;
        imagePreview.value = null;
    }
});

const setRating = (star) => form.rating = star;

const handleImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const removeImage = () => {
    form.image = null;
    imagePreview.value = null;
};

const submitReview = () => {
    if (form.rating === 0) return;
    
    form.post(route('reviews.store'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            emit('close');
            Swal.fire({
                icon: 'success',
                title: 'Review Submitted',
                text: 'Thanks for your feedback!',
                confirmButtonColor: '#e11d48'
            });
        },
    });
};
</script>

<template>
    <div v-if="show">
        
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-[100] transition-opacity" @click="$emit('close')"></div>

        <div class="hidden md:flex fixed inset-0 z-[101] items-center justify-center p-4 pointer-events-none">
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] animate-in zoom-in-95 duration-200 ring-1 ring-white/20 pointer-events-auto">
                
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-white shrink-0 shadow-sm z-10">
                    <h3 class="font-black text-xl text-gray-900 flex items-center gap-2">
                        <MessageSquarePlus class="w-6 h-6 text-rose-600" />
                        Write a Review
                    </h3>
                    <button @click="$emit('close')" class="p-2 rounded-full hover:bg-gray-100 text-gray-500 transition-colors">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 overflow-y-auto custom-scrollbar bg-white">
                    
                    <div class="flex items-center gap-4 mb-6 bg-rose-50 p-3 rounded-2xl border border-rose-100">
                        <div class="w-14 h-14 rounded-xl bg-white p-1 shadow-sm border border-rose-100 flex-shrink-0">
                            <img :src="product?.image || '/images/placeholder.png'" class="w-full h-full object-cover rounded-lg" />
                        </div>
                        <div>
                            <p class="text-[10px] font-bold tracking-wider text-rose-500 uppercase mb-0.5">Purchased Item</p>
                            <p class="font-bold text-gray-900 text-sm leading-snug line-clamp-2">{{ product?.name }}</p>
                        </div>
                    </div>

                    <div class="mb-8 text-center border-b border-gray-100 pb-6">
                        <p class="text-sm font-bold text-gray-600 mb-3">How was the quality?</p>
                        <div class="flex justify-center gap-3 mb-2">
                            <button 
                                v-for="star in 5" 
                                :key="star"
                                @click="setRating(star)"
                                type="button"
                                class="transition-all duration-200 hover:scale-110 focus:outline-none group"
                            >
                                <Star 
                                    class="w-10 h-10 transition-colors duration-200" 
                                    :class="star <= form.rating 
                                        ? 'fill-amber-400 text-amber-400 drop-shadow-sm' 
                                        : 'text-gray-300 group-hover:text-amber-300'" 
                                    stroke-width="2"
                                />
                            </button>
                        </div>
                        <p class="text-sm font-bold h-6 transition-all duration-300" :class="form.rating > 0 ? 'text-rose-600' : 'text-gray-300'">
                            {{ ratingText }}
                        </p>
                        <p v-if="form.errors.rating" class="text-xs text-red-500 mt-2 font-bold bg-red-50 px-3 py-1 rounded-full inline-block">{{ form.errors.rating }}</p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-800 mb-2 ml-1">Add Photo (Optional)</label>
                        
                        <div v-if="!imagePreview" class="relative group cursor-pointer">
                            <input type="file" @change="handleImageUpload" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                            <div class="border-2 border-dashed border-gray-300 rounded-2xl p-5 flex flex-col items-center justify-center bg-gray-50 group-hover:bg-rose-50 group-hover:border-rose-400 transition-all">
                                <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center mb-2 text-gray-400 group-hover:text-rose-500 group-hover:scale-110 transition-all">
                                    <Camera class="w-6 h-6" />
                                </div>
                                <p class="text-xs font-bold text-gray-600 group-hover:text-rose-600">Tap to upload photo</p>
                                <p class="text-[10px] text-gray-400 mt-1">JPG, PNG (Max 2MB)</p>
                            </div>
                        </div>

                        <div v-else class="relative w-full h-48 rounded-2xl overflow-hidden border border-gray-200 group shadow-sm">
                            <img :src="imagePreview" class="w-full h-full object-cover" />
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                <button @click="removeImage" class="px-4 py-2 bg-white rounded-full text-red-600 font-bold text-xs hover:bg-red-50 shadow-lg flex items-center gap-1">
                                    <Trash2 class="w-4 h-4" /> Remove
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.image" class="text-xs text-red-500 mt-1 ml-1 font-bold">{{ form.errors.image }}</p>
                    </div>

                    <div class="mb-2 relative">
                        <label class="block text-sm font-bold text-gray-800 mb-2 ml-1">Tell us more</label>
                        <textarea 
                            v-model="form.comment"
                            rows="3" 
                            class="w-full bg-white border border-gray-300 rounded-2xl p-4 text-sm text-gray-900 placeholder:text-gray-400 focus:border-rose-500 focus:ring-4 focus:ring-rose-500/10 transition-all resize-none shadow-sm"
                            placeholder="What did you like? What could be better?"
                        ></textarea>
                        <div class="flex justify-end mt-1 px-1">
                            <span class="text-[10px] text-gray-400 font-medium">{{ form.comment.length }} chars</span>
                        </div>
                        <p v-if="form.errors.comment" class="text-xs text-red-500 mt-1 ml-1 font-bold">{{ form.errors.comment }}</p>
                    </div>

                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-3 shrink-0">
                    <button @click="$emit('close')" class="px-6 py-3 text-sm font-bold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-100 transition-all shadow-sm">
                        Cancel
                    </button>
                    <button 
                        @click="submitReview" 
                        :disabled="form.processing || form.rating === 0"
                        class="px-6 py-3 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-rose-600 to-pink-600 hover:shadow-lg hover:shadow-rose-200 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center gap-2"
                    >
                        <span v-if="form.processing" class="animate-spin">⏳</span>
                        {{ form.processing ? 'Sending...' : 'Submit Review' }}
                    </button>
                </div>
            </div>
        </div>

        <div class="md:hidden fixed inset-x-0 bottom-0 z-[101] flex flex-col max-h-[90vh] animate-in slide-in-from-bottom duration-300">
            <div class="bg-white rounded-t-[2rem] shadow-[0_-10px_40px_rgba(0,0,0,0.2)] flex flex-col flex-1 overflow-hidden">
                
                <div class="w-full flex justify-center pt-3 pb-2 bg-white" @click="$emit('close')">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                </div>

                <div class="px-5 pb-4 border-b border-gray-100 bg-white sticky top-0 z-10 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg flex items-center gap-2">
                            Review Product
                        </h3>
                        <p class="text-xs text-gray-500">Share your experience</p>
                    </div>
                    <button @click="$emit('close')" class="p-2 bg-gray-100 rounded-full text-gray-500">
                        <ChevronDown class="w-5 h-5" />
                    </button>
                </div>

                <div class="overflow-y-auto flex-1 bg-gray-50/30 p-5 space-y-6">
                    
                    <div class="flex items-center gap-4 bg-white p-3 rounded-2xl border border-gray-100 shadow-sm">
                        <div class="w-14 h-14 rounded-xl bg-gray-50 p-1 flex-shrink-0">
                            <img :src="product?.image || '/images/placeholder.png'" class="w-full h-full object-cover rounded-lg" />
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 text-sm leading-snug line-clamp-2">{{ product?.name }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">Order #{{ orderId }}</p>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-2xl border border-gray-100 text-center shadow-sm">
                        <p class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-3">Rate Quality</p>
                        <div class="flex justify-center gap-3 mb-2">
                            <button 
                                v-for="star in 5" 
                                :key="star"
                                @click="setRating(star)"
                                type="button"
                                class="focus:outline-none active:scale-125 transition-transform"
                            >
                                <Star 
                                    class="w-10 h-10 transition-colors" 
                                    :class="star <= form.rating 
                                        ? 'fill-amber-400 text-amber-400' 
                                        : 'text-gray-200'" 
                                    stroke-width="2"
                                />
                            </button>
                        </div>
                        <p class="text-sm font-bold h-5" :class="form.rating > 0 ? 'text-rose-600' : 'text-gray-300'">
                            {{ ratingText }}
                        </p>
                        <p v-if="form.errors.rating" class="text-xs text-red-500 mt-2 font-bold">{{ form.errors.rating }}</p>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                        <label class="block text-xs font-bold text-gray-800 mb-2 uppercase tracking-wide">Add Photo (Optional)</label>
                        
                        <div v-if="!imagePreview" class="relative group cursor-pointer">
                            <input type="file" @change="handleImageUpload" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                            <div class="border-2 border-dashed border-gray-200 rounded-xl p-4 flex flex-col items-center justify-center bg-gray-50">
                                <Camera class="w-8 h-8 text-gray-400 mb-1" />
                                <p class="text-xs font-bold text-gray-600">Upload Photo</p>
                            </div>
                        </div>

                        <div v-else class="relative w-full h-40 rounded-xl overflow-hidden border border-gray-200">
                            <img :src="imagePreview" class="w-full h-full object-cover" />
                            <button @click="removeImage" class="absolute top-2 right-2 p-1.5 bg-red-600 text-white rounded-full shadow-lg">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-sm">
                        <label class="block text-xs font-bold text-gray-800 mb-2 uppercase tracking-wide">Your Review</label>
                        <textarea 
                            v-model="form.comment"
                            rows="3" 
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm text-gray-900 focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
                            placeholder="Write your experience here..."
                        ></textarea>
                        <div class="text-right mt-1">
                            <span class="text-[10px] text-gray-400">{{ form.comment.length }} chars</span>
                        </div>
                    </div>

                </div>

                <div class="p-4 bg-white border-t border-gray-100">
                    <button 
                        @click="submitReview" 
                        :disabled="form.processing || form.rating === 0"
                        class="w-full py-3.5 bg-gradient-to-r from-rose-600 to-pink-600 text-white rounded-xl font-bold shadow-lg shadow-rose-200 active:scale-95 transition-all disabled:opacity-50 disabled:shadow-none flex items-center justify-center gap-2"
                    >
                        <span v-if="form.processing" class="animate-spin">⏳</span>
                        {{ form.processing ? 'Submitting...' : 'Submit Review' }}
                    </button>
                </div>

            </div>
        </div>

    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #4b5563;
    border-radius: 4px;
    border: 2px solid #f3f4f6;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: #1f2937;
}
</style>