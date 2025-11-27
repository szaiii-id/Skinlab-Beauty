<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Star, X } from 'lucide-vue-next';

const props = defineProps({
    show: Boolean,
    product: Object, // Data produk yang mau direview
    orderId: Number, // ID Order terkait
});

const emit = defineEmits(['close']);

const form = useForm({
    product_id: '',
    order_id: '',
    rating: 0,
    comment: ''
});

// Reset form saat modal dibuka
watch(() => props.show, (newVal) => {
    if (newVal && props.product) {
        form.reset();
        form.product_id = props.product.id;
        form.order_id = props.orderId;
        form.rating = 0;
    }
});

const setRating = (star) => {
    form.rating = star;
};

const submitReview = () => {
    if (form.rating === 0) {
        alert("Mohon pilih bintang terlebih dahulu.");
        return;
    }
    form.post(route('reviews.store'), {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden animate-fade-in-up">
            
            <div class="bg-rose-50 px-6 py-4 flex justify-between items-center border-b border-rose-100">
                <h3 class="font-bold text-gray-800">Tulis Ulasan</h3>
                <button @click="$emit('close')" class="text-gray-400 hover:text-rose-600 transition">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="p-6">
                <div class="flex items-center gap-4 mb-6">
                    <img :src="product?.image || '/images/placeholder.png'" class="w-16 h-16 rounded-lg object-cover bg-gray-100 border border-gray-200" />
                    <div>
                        <p class="text-xs text-rose-600 font-bold uppercase mb-1">Produk yang diulas</p>
                        <p class="font-medium text-gray-900 line-clamp-2">{{ product?.name }}</p>
                    </div>
                </div>

                <div class="mb-6 text-center">
                    <p class="text-sm text-gray-500 mb-2">Berikan rating Anda</p>
                    <div class="flex justify-center gap-2">
                        <button 
                            v-for="star in 5" 
                            :key="star"
                            @click="setRating(star)"
                            type="button"
                            class="transition-transform hover:scale-110 focus:outline-none"
                        >
                            <Star 
                                class="w-8 h-8" 
                                :class="star <= form.rating ? 'fill-amber-400 text-amber-400' : 'text-gray-300'" 
                            />
                        </button>
                    </div>
                    <p v-if="form.errors.rating" class="text-xs text-red-500 mt-1">{{ form.errors.rating }}</p>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ulasan (Opsional)</label>
                    <textarea 
                        v-model="form.comment"
                        rows="4" 
                        class="w-full border-gray-300 rounded-xl focus:border-rose-500 focus:ring-rose-500 text-sm"
                        placeholder="Bagaimana kualitas produk ini? Ceritakan pengalaman Anda..."
                    ></textarea>
                    <p v-if="form.errors.comment" class="text-xs text-red-500 mt-1">{{ form.errors.comment }}</p>
                </div>

                <button 
                    @click="submitReview" 
                    :disabled="form.processing"
                    class="w-full bg-rose-600 text-white font-bold py-3 rounded-xl hover:bg-rose-700 transition shadow-lg shadow-rose-200 disabled:opacity-50"
                >
                    {{ form.processing ? 'Mengirim...' : 'Kirim Ulasan' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>