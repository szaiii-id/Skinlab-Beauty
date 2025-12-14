<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    ChevronLeft, User, Calendar, Send, 
    ShoppingBag, Package, Search, Check, ChevronDown, Sparkles, Activity, Gift
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

// 1. IMPORT REUSABLE COMPONENT
import GiftVoucherModal from '@/components/GiftVoucherModal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    profile: Object,
    products: Array,
    giftRewards: Array // Ensure Controller passes this!
});

// --- STATE FOR GIFT MODAL ---
const showGiftModal = ref(false);

// Convert single user ID to Set (Required by GiftVoucherModal)
const targetUserSet = computed(() => new Set([props.profile.user.id]));


// --- 1. LOGIC PRODUCT SEARCH ---
const isDropdownOpen = ref(false);
const productSearch = ref('');
const selectedProductDisplay = ref(null);

const filteredProducts = computed(() => {
    if (!productSearch.value) return props.products;
    return props.products.filter(p => 
        p.name.toLowerCase().includes(productSearch.value.toLowerCase())
    );
});

const selectProduct = (product) => {
    form.product_id = product.id;
    selectedProductDisplay.value = product; 
    isDropdownOpen.value = false;
};

// --- 2. FORM SUBMISSION ---
const form = useForm({
    product_id: '',
    message: ''
});

const submitRecommendation = () => {
    if (!form.product_id) {
        Swal.fire('Product Required', 'Please select a product to recommend.', 'warning');
        return;
    }

    form.post(route('admin.skin-analysis.recommend', props.profile.id), {
        onSuccess: () => {
            form.reset();
            selectedProductDisplay.value = null;
            Swal.fire({
                title: 'Sent!',
                text: 'Product recommendation sent to user successfully.',
                icon: 'success',
                confirmButtonColor: '#e11d48'
            });
        },
        onError: () => {
            Swal.fire('Failed', 'System error occurred.', 'error');
        }
    });
};

const formatDate = (date) => new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'long', year: 'numeric' });
const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
</script>

<template>
    <Head title="Analysis Detail" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <Link :href="route('admin.skin-analysis.index')" class="inline-flex items-center gap-2 text-slate-500 hover:text-rose-600 mb-8 font-semibold transition-colors group">
            <div class="p-1.5 rounded-full group-hover:bg-rose-50 transition-colors">
                <ChevronLeft class="w-5 h-5" />
            </div>
            Back to Database
        </Link>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            
            <div class="xl:col-span-2 space-y-6">
                
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-slate-50 to-white px-8 py-6 border-b border-slate-100 flex items-center gap-5">
                        <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center text-2xl shadow-md border-2 border-rose-50 text-rose-500 font-bold shrink-0">
                            {{ profile.user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-slate-800">{{ profile.user.name }}</h2>
                            <div class="flex flex-wrap items-center gap-4 text-sm text-slate-500 mt-1 font-medium">
                                <span class="flex items-center gap-1.5">
                                    <User class="w-3.5 h-3.5 text-slate-400" /> Member since {{ formatDate(profile.user.created_at) }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <Calendar class="w-3.5 h-3.5 text-slate-400" /> Analyzed: {{ formatDate(profile.updated_at) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-8">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-rose-100 rounded-lg text-rose-600">
                                <Activity class="w-5 h-5" />
                            </div>
                            <h3 class="text-lg font-bold text-slate-800">Skin Diagnosis</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="p-6 bg-rose-50/50 rounded-2xl border border-rose-100 flex flex-col justify-center">
                                <span class="text-xs font-bold text-rose-400 uppercase tracking-wider mb-2">Primary Skin Type</span>
                                <p class="text-3xl font-black text-rose-600 tracking-tight">{{ profile.skin_type }}</p>
                                <p class="text-sm text-rose-700/70 mt-2 font-medium">Based on quiz analysis</p>
                            </div>

                            <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100">
                                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 block">Identified Concerns</span>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="concern in profile.skin_concerns" :key="concern" 
                                          class="px-3 py-1.5 bg-white text-slate-700 border border-slate-200 shadow-sm rounded-lg text-xs font-bold">
                                        {{ concern }}
                                    </span>
                                    <span v-if="!profile.skin_concerns.length" class="text-sm text-slate-400 italic">No specific concerns identified.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2 text-lg">
                            <ShoppingBag class="w-5 h-5 text-slate-400" /> 
                            Purchase History
                        </h3>
                        <span class="text-xs font-bold text-slate-400 bg-slate-50 px-2 py-1 rounded">Last 5 Orders</span>
                    </div>

                    <div v-if="profile.user.orders && profile.user.orders.length > 0" class="space-y-3">
                        <div v-for="order in profile.user.orders" :key="order.id" class="flex justify-between items-center p-4 bg-slate-50/50 rounded-xl border border-slate-100 hover:border-slate-200 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="h-10 w-10 rounded-full bg-white flex items-center justify-center border border-slate-100 text-slate-400 font-bold text-xs">
                                    #{{ order.id }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-800 text-sm">Order #{{ order.order_number }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ formatDate(order.created_at) }}</p>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 bg-white border border-emerald-100 text-emerald-700 text-[10px] font-bold rounded-md uppercase tracking-wide shadow-sm">
                                Completed
                            </span>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-10 bg-slate-50 rounded-xl border border-dashed border-slate-200">
                        <ShoppingBag class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                        <p class="text-sm text-slate-500 font-medium">No completed orders found.</p>
                        <p class="text-xs text-slate-400 mt-1">This might be a new potential customer.</p>
                    </div>
                </div>
            </div>

            <div class="xl:col-span-1 space-y-6">
                
                <div class="bg-gradient-to-br from-white to-rose-50/50 rounded-2xl shadow-sm border border-rose-100 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center text-rose-500 shadow-sm border border-rose-100">
                            <Gift class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900">Customer Care</h3>
                            <p class="text-xs text-slate-500">Loyalty & Retention</p>
                        </div>
                    </div>
                    <p class="text-sm text-slate-600 mb-4 leading-relaxed">
                        Send a special voucher or gift to encourage this user to make their first purchase.
                    </p>
                    <button 
                        @click="showGiftModal = true"
                        class="w-full flex items-center justify-center gap-2 py-3 bg-white border border-rose-200 text-rose-600 font-bold rounded-xl hover:bg-rose-50 hover:border-rose-300 transition-all shadow-sm"
                    >
                        <Gift class="w-4 h-4" /> Send Gift Voucher
                    </button>
                </div>

                <div class="bg-white rounded-2xl shadow-lg border border-rose-100 p-6 sticky top-6">
                    <div class="flex items-center gap-3 mb-6 pb-6 border-b border-slate-100">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-rose-500 to-pink-600 flex items-center justify-center text-white shadow-lg shadow-rose-200">
                            <Package class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-900 text-lg">Product Prescription</h3>
                            <p class="text-xs text-slate-500 font-medium">Send personal recommendation</p>
                        </div>
                    </div>
                    
                    <div class="space-y-6">
                        
                        <div class="relative">
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Select Product</label>
                            
                            <button 
                                @click="isDropdownOpen = !isDropdownOpen"
                                type="button"
                                class="w-full flex items-center justify-between bg-white border border-slate-300 rounded-xl px-4 py-3.5 text-left focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all shadow-sm hover:border-rose-300 group"
                            >
                                <span v-if="selectedProductDisplay" class="flex items-center gap-3 w-full overflow-hidden">
                                    <img 
                                        :src="selectedProductDisplay.image_url || '/images/placeholder.png'" 
                                        class="w-10 h-10 rounded-lg object-cover bg-slate-100 border border-slate-200 shrink-0"
                                        alt="Product"
                                    />
                                    <span class="block truncate flex-1">
                                        <span class="block text-sm font-bold text-slate-800 truncate">{{ selectedProductDisplay.name }}</span>
                                        <span class="block text-xs text-rose-600 font-bold">{{ formatCurrency(selectedProductDisplay.price) }}</span>
                                    </span>
                                </span>
                                <span v-else class="text-sm text-slate-400 font-medium flex items-center gap-2">
                                    <Search class="w-4 h-4" /> Search product...
                                </span>
                                <ChevronDown class="w-4 h-4 text-slate-400 group-hover:text-rose-500 transition-colors" />
                            </button>

                            <div v-if="isDropdownOpen" class="absolute z-50 mt-2 w-full bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                                <div class="p-3 border-b border-slate-100 bg-slate-50 sticky top-0 z-10">
                                    <div class="relative">
                                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                        <input 
                                            v-model="productSearch"
                                            type="text" 
                                            placeholder="Type product name..." 
                                            class="w-full pl-9 pr-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-rose-500 focus:border-rose-500 placeholder-slate-400"
                                            autofocus
                                        >
                                    </div>
                                </div>
                                <ul class="max-h-72 overflow-y-auto py-2 custom-scrollbar">
                                    <li v-for="prod in filteredProducts" :key="prod.id">
                                        <button 
                                            @click="selectProduct(prod)"
                                            class="w-full flex items-center gap-3 px-4 py-3 hover:bg-rose-50 transition-colors text-left group border-b border-slate-50 last:border-0"
                                        >
                                            <img 
                                                :src="prod.image_url || '/images/placeholder.png'" 
                                                class="w-10 h-10 rounded-lg object-cover bg-white border border-slate-200"
                                            />
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-bold text-slate-800 truncate group-hover:text-rose-700">{{ prod.name }}</p>
                                                <div class="flex justify-between items-center mt-1">
                                                    <p class="text-xs text-slate-600 font-bold">{{ formatCurrency(prod.price) }}</p>
                                                    <span class="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-bold border border-slate-200">
                                                        Stock: {{ prod.stock }}
                                                    </span>
                                                </div>
                                            </div>
                                            <Check v-if="form.product_id === prod.id" class="w-5 h-5 text-rose-600" />
                                        </button>
                                    </li>
                                    <li v-if="filteredProducts.length === 0" class="px-6 py-8 text-center">
                                        <p class="text-sm text-slate-400 font-medium">No products found.</p>
                                    </li>
                                </ul>
                            </div>
                            <div v-if="isDropdownOpen" @click="isDropdownOpen = false" class="fixed inset-0 z-40 cursor-default"></div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 ml-1">Consultant Message</label>
                            <div class="relative">
                                <textarea 
                                    v-model="form.message" 
                                    rows="6" 
                                    class="w-full p-4 rounded-xl border border-slate-300 bg-white text-sm font-medium text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 resize-none shadow-sm transition-all"
                                    placeholder="Write your personal message here...&#10;Ex: 'Hi, based on your oily skin profile, I recommend this toner for night routine...'"
                                ></textarea>
                                <div class="absolute bottom-3 right-3 text-[10px] font-bold text-slate-400 bg-white/80 px-2 py-0.5 rounded-md">
                                    {{ form.message.length }}/500
                                </div>
                            </div>
                            <p class="text-xs text-slate-400 italic mt-2 flex items-center gap-1">
                                <Sparkles class="w-3 h-3 text-yellow-500" /> 
                                This message will appear in user notifications.
                            </p>
                        </div>

                        <button 
                            @click="submitRecommendation" 
                            :disabled="form.processing"
                            class="w-full py-4 bg-gradient-to-r from-rose-500 to-pink-600 hover:from-rose-600 hover:to-pink-700 text-white font-bold rounded-xl shadow-lg hover:shadow-rose-200 transition-all flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed transform active:scale-[0.98]"
                        >
                            <Send class="w-4 h-4" /> 
                            {{ form.processing ? 'Sending...' : 'Send Recommendation' }}
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <GiftVoucherModal 
        :show="showGiftModal"
        :users="targetUserSet"
        :rewards="giftRewards" 
        :submit-url="route('admin.customers.send-gift')" 
        @close="showGiftModal = false"
        @success="showGiftModal = false"
    />
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>