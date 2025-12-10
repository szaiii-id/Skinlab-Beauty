<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Star, X, MessageSquarePlus, Camera, Trash2 } from 'lucide-vue-next';
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
    <div v-if="show" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4 transition-all duration-300">
        <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] animate-in zoom-in-95 duration-200 ring-1 ring-white/20">
            
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
</template>

<style scoped>
/* High Visibility Scrollbar (Dark Mode style) */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px; /* Lebih lebar sedikit agar mudah di-tap */
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f3f4f6; /* Abu-abu terang */
    border-radius: 4px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #4b5563; /* Abu-abu Gelap (Gray-600) */
    border-radius: 4px;
    border: 2px solid #f3f4f6; /* Memberi jarak/border putih */
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: #1f2937; /* Hitam (Gray-800) saat di-hover */
}
</style>