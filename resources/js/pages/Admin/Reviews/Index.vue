<script setup>
import { ref, watch } from 'vue';
import { Head, router, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Swal from 'sweetalert2';
import { 
    Search, Star, Eye, EyeOff, Trash2, 
    MessageSquare, Reply, User, Filter, X, Send, Quote, ImageIcon, ShieldCheck
} from 'lucide-vue-next';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    reviews: Object,
    filters: Object
});

// --- State Management ---
const search = ref(props.filters.search || '');
const ratingFilter = ref(props.filters.rating || 'all');

// --- Modal State (Reply) ---
const showReplyModal = ref(false);
const selectedReview = ref(null);
const replyForm = useForm({
    reply: ''
});

// --- LIGHTBOX STATE (POPUP GAMBAR) ---
const showLightbox = ref(false);
const activeImage = ref(null);

const openLightbox = (imageUrl) => {
    activeImage.value = imageUrl;
    showLightbox.value = true;
    // Mencegah scroll pada body background saat popup terbuka
    document.body.style.overflow = 'hidden';
};

const closeLightbox = () => {
    showLightbox.value = false;
    activeImage.value = null;
    // Mengaktifkan kembali scroll
    document.body.style.overflow = 'auto';
};

// --- Search & Filter Watcher ---
let timeout = null;
watch([search, ratingFilter], () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('admin.reviews.index'), { 
            search: search.value, 
            rating: ratingFilter.value 
        }, { preserveState: true, replace: true });
    }, 300);
});

// --- Actions ---

const toggleHidden = (review) => {
    router.patch(route('admin.reviews.toggle', review.id), {}, { preserveScroll: true });
};

const deleteReview = (id) => {
    Swal.fire({
        title: 'Delete Review?',
        text: "This action is permanent. Use 'Hide' for temporary removal.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, Delete',
        cancelButtonColor: '#6b7280',
        background: '#fff',
        borderRadius: '1rem'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.reviews.destroy', id));
        }
    });
};

// Open Modal & Pre-fill Data
const openReplyModal = (review) => {
    selectedReview.value = review;
    replyForm.reply = review.admin_reply || ''; 
    showReplyModal.value = true;
};

// Submit Reply Form
const submitReply = () => {
    if (!replyForm.reply.trim()) return;

    replyForm.post(route('admin.reviews.reply', selectedReview.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showReplyModal.value = false;
            replyForm.reset();
            Swal.fire({
                icon: 'success',
                title: 'Sent!',
                text: 'Reply has been posted successfully.',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
};

const formatDate = (date) => new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
</script>

<template>
    <Head title="Reviews" />

    <div class="max-w-7xl mx-auto min-h-screen">
        
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
            <div>
                <h1 class="text-4xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                    <span class="bg-gradient-to-r from-pink-500 to-rose-600 bg-clip-text text-transparent">User Reviews</span>
                </h1>
                <p class="text-gray-500 mt-2 font-medium">Manage customer feedback & reputation</p>
            </div>

            <div class="flex flex-wrap gap-3 w-full md:w-auto">
                <div class="relative">
                    <Filter class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <select v-model="ratingFilter" class="pl-10 pr-8 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-700 focus:ring-pink-500 focus:border-pink-500 shadow-sm cursor-pointer appearance-none hover:border-pink-300 transition-colors">
                        <option value="all">All Stars</option>
                        <option value="5">5 Stars Only</option>
                        <option value="4">4 Stars Only</option>
                        <option value="3">3 Stars Only</option>
                        <option value="2">2 Stars Only</option>
                        <option value="1">1 Star Only</option>
                    </select>
                </div>

                <div class="relative flex-1 md:w-64">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input 
                        v-model="search" 
                        type="text" 
                        placeholder="Search content..." 
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm text-gray-900 focus:ring-pink-500 focus:border-pink-500 shadow-sm transition-all placeholder:text-gray-400"
                    >
                </div>
            </div>
        </div>

        <div v-if="reviews.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            
            <div v-for="review in reviews.data" :key="review.id" 
                class="group relative bg-white rounded-3xl border transition-all duration-300 flex flex-col"
                :class="[
                    review.is_hidden 
                        ? 'border-red-100 bg-red-50/30' 
                        : 'border-gray-100 hover:border-pink-200 hover:shadow-xl hover:shadow-pink-500/5'
                ]"
            >
                <div v-if="review.is_hidden" class="absolute top-4 right-4 px-3 py-1 bg-red-100 text-red-600 text-[10px] font-black uppercase tracking-wider rounded-full flex items-center gap-1 z-10 border border-red-200">
                    <EyeOff class="w-3 h-3" /> Hidden
                </div>

                <div class="px-6 pt-6 flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-gray-500 font-bold border border-white shadow-sm">
                            <span v-if="review.user?.name">{{ review.user.name.charAt(0) }}</span>
                            <User v-else class="w-5 h-5" />
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 text-sm leading-tight flex items-center gap-1">
                                {{ review.user?.name }}
                                <ShieldCheck class="w-3 h-3 text-emerald-500" fill="currentColor" />
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ formatDate(review.created_at) }}</p>
                        </div>
                    </div>
                </div>

                <div class="px-6 flex-1">
                    <div class="flex gap-0.5 mb-3">
                        <Star v-for="i in 5" :key="i" class="w-4 h-4" 
                            :class="i <= review.rating ? 'fill-amber-400 text-amber-400' : 'fill-gray-100 text-gray-200'" 
                        />
                    </div>

                    <div v-if="review.image" class="mb-4">
                        <div 
                            @click="openLightbox(`/storage/${review.image}`)" 
                            class="block w-24 h-24 rounded-2xl overflow-hidden border border-gray-200 relative group cursor-zoom-in bg-gray-50 shadow-sm hover:shadow-md transition-all"
                        >
                            <img :src="`/storage/${review.image}`" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" />
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center">
                                <ImageIcon class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition-opacity drop-shadow-sm" />
                            </div>
                        </div>
                    </div>

                    <div class="relative mb-4">
                        <MessageSquare class="absolute -top-1 -left-1 w-4 h-4 text-gray-200 transform -scale-x-100" />
                        <p class="text-gray-700 text-sm leading-relaxed pl-5 italic relative z-10 font-medium">
                            "{{ review.comment || 'No textual comment provided.' }}"
                        </p>
                    </div>
                </div>

                <div class="px-6 mt-auto">
                    <div class="flex items-center gap-2 bg-gray-50 p-2 rounded-xl border border-gray-100 group-hover:bg-pink-50/50 group-hover:border-pink-100 transition-colors">
                        <div class="w-8 h-8 rounded-lg bg-white p-0.5 flex-shrink-0 border border-gray-100">
                            <img :src="review.product?.image_url || '/images/placeholder.png'" class="w-full h-full object-cover rounded-md" />
                        </div>
                        <p class="text-xs font-bold text-gray-600 line-clamp-1 group-hover:text-pink-700 transition-colors">
                            {{ review.product?.name }}
                        </p>
                    </div>
                </div>

                <div v-if="review.admin_reply" class="px-6 mt-4">
                    <div class="bg-blue-50/80 p-3 rounded-xl border border-blue-100 relative">
                        <div class="absolute -top-2 left-6 w-4 h-4 bg-blue-50 border-t border-l border-blue-100 transform rotate-45"></div>
                        <p class="text-[10px] font-bold text-blue-600 uppercase tracking-wide mb-1 flex items-center gap-1">
                            <Reply class="w-3 h-3" /> You Replied:
                        </p>
                        <p class="text-xs text-blue-800 leading-relaxed font-medium">{{ review.admin_reply }}</p>
                    </div>
                </div>

                <div class="p-4 mt-4 border-t border-gray-100 flex justify-between items-center bg-gray-50/30 rounded-b-3xl">
                    <button 
                        @click="openReplyModal(review)"
                        class="text-xs font-bold px-3 py-2 rounded-lg transition-all flex items-center gap-1.5"
                        :class="review.admin_reply ? 'text-blue-600 hover:bg-blue-50' : 'text-gray-600 hover:text-pink-600 hover:bg-white hover:shadow-sm'"
                    >
                        <Reply class="w-3.5 h-3.5" />
                        {{ review.admin_reply ? 'Edit Reply' : 'Reply' }}
                    </button>

                    <div class="flex gap-1">
                        <button 
                            @click="toggleHidden(review)"
                            class="p-2 rounded-lg transition-all text-gray-400 hover:bg-white hover:shadow-sm"
                            :class="review.is_hidden ? 'text-emerald-600 hover:text-emerald-700' : 'hover:text-gray-600'"
                            :title="review.is_hidden ? 'Show Review' : 'Hide Review'"
                        >
                            <component :is="review.is_hidden ? Eye : EyeOff" class="w-4 h-4" />
                        </button>
                        
                        <button 
                            @click="deleteReview(review.id)"
                            class="p-2 rounded-lg transition-all text-gray-400 hover:text-red-600 hover:bg-white hover:shadow-sm"
                            title="Delete Permanently"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

        </div>

        <div v-else class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-dashed border-gray-300">
            <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                <Star class="w-10 h-10 text-gray-300" />
            </div>
            <h3 class="text-lg font-bold text-gray-900">No reviews found</h3>
            <p class="text-gray-500 text-sm mt-1">Try adjusting your search or filters.</p>
            <button @click="ratingFilter = 'all'; search = ''" class="mt-4 text-pink-600 font-bold text-sm hover:underline">
                Clear Filters
            </button>
        </div>

        <div v-if="reviews.links.length > 3" class="mt-8 flex justify-center">
            <div class="flex flex-wrap gap-1 bg-white p-1.5 rounded-2xl shadow-sm border border-gray-100">
                <Link v-for="(link, k) in reviews.links" :key="k" :href="link.url || '#'" 
                    class="px-4 py-2 rounded-xl text-sm font-bold transition-all"
                    :class="link.active 
                        ? 'bg-pink-600 text-white shadow-md shadow-pink-200' 
                        : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'"
                    v-html="link.label" />
            </div>
        </div>

        <div v-if="showReplyModal" class="fixed inset-0 z-[100] flex items-center justify-center px-4 sm:px-0">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="showReplyModal = false"></div>
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all animate-in zoom-in-95 duration-300 ring-1 ring-gray-100">
                <div class="px-8 py-5 border-b border-gray-100 flex justify-between items-center bg-white">
                    <div>
                        <h3 class="text-xl font-black text-gray-900 flex items-center gap-2">
                            <Reply class="w-5 h-5 text-pink-600" /> Reply to Review
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Engage with your customer professionally.</p>
                    </div>
                    <button @click="showReplyModal = false" class="text-gray-400 hover:text-gray-600 hover:bg-gray-100 p-2 rounded-full transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                <div class="p-8">
                    <div class="bg-gray-50 rounded-2xl p-5 mb-6 border border-gray-100 relative">
                        <div class="absolute top-4 right-4 text-gray-200"><Quote class="w-8 h-8 opacity-50" /></div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="font-bold text-sm text-gray-900">{{ selectedReview?.user?.name }}</span>
                            <div class="flex"><Star v-for="i in 5" :key="i" class="w-3 h-3" :class="i <= selectedReview?.rating ? 'fill-amber-400 text-amber-400' : 'text-gray-300'" /></div>
                        </div>
                        <p class="text-sm text-gray-600 italic leading-relaxed relative z-10 line-clamp-3">"{{ selectedReview?.comment || 'No textual comment.' }}"</p>
                    </div>
                    <div class="relative">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2 ml-1">Your Response</label>
                        <textarea v-model="replyForm.reply" rows="5" class="w-full border-gray-200 bg-white rounded-2xl text-sm text-gray-900 focus:border-pink-500 focus:ring-4 focus:ring-pink-500/10 transition-all placeholder:text-gray-400 p-4 resize-none shadow-sm" placeholder="Type a thank you note or apology here..."></textarea>
                        <div class="flex justify-between mt-2 ml-1">
                            <p v-if="replyForm.errors.reply" class="text-red-500 text-xs font-bold">{{ replyForm.errors.reply }}</p>
                            <p v-else class="text-[10px] text-gray-400">Please be polite.</p>
                        </div>
                    </div>
                </div>
                <div class="px-8 py-5 bg-gray-50 border-t border-gray-100 flex justify-end gap-3">
                    <button @click="showReplyModal = false" class="px-5 py-2.5 text-sm font-bold text-gray-600 hover:bg-white hover:text-gray-800 hover:shadow-sm border border-transparent hover:border-gray-200 rounded-xl transition-all">Cancel</button>
                    <button @click="submitReply" :disabled="replyForm.processing || !replyForm.reply" class="px-6 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-pink-600 to-rose-600 rounded-xl hover:shadow-lg hover:shadow-pink-200 hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span v-if="replyForm.processing" class="animate-spin">⏳</span><Send v-else class="w-4 h-4" /> {{ replyForm.processing ? 'Posting...' : 'Post Reply' }}
                    </button>
                </div>
            </div>
        </div>

        <div 
            v-if="showLightbox" 
            class="fixed inset-0 z-[9999] bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200" 
            @click="closeLightbox"
        >
            <button 
                class="absolute top-6 right-6 text-white/70 hover:text-white bg-white/10 hover:bg-white/20 p-2 rounded-full transition-all z-50 backdrop-blur-md" 
                @click="closeLightbox"
            >
                <X class="w-8 h-8" />
            </button>

            <img 
                :src="activeImage" 
                class="max-w-full max-h-[85vh] rounded-lg shadow-2xl object-contain animate-in zoom-in-95 duration-300 select-none" 
                @click.stop 
            />
            
            <p class="absolute bottom-6 text-white/60 text-xs font-bold tracking-widest uppercase">
                Tap outside to close
            </p>
        </div>

    </div>
</template>