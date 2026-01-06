<script setup>
import { Head, router, Link, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import Swal from 'sweetalert2';
import { Trash2, ShoppingCart, Minus, Plus, CheckCircle2, Package, Store, Eye, PackageCheck, AlertCircle, PackageX, ArrowRight, Tag } from 'lucide-vue-next';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    cart: { type: Object, default: () => ({}) }
});

// --- INFINITE SCROLL ---
const ITEMS_PER_PAGE = 6;
const displayLimit = ref(ITEMS_PER_PAGE);
const observerTarget = ref(null);
let observer = null;

const allCartEntries = computed(() => Object.entries(props.cart || {}));
const visibleCart = computed(() => Object.fromEntries(allCartEntries.value.slice(0, displayLimit.value)));
const hasMoreItems = computed(() => displayLimit.value < allCartEntries.value.length);

const loadMore = () => { if (hasMoreItems.value) setTimeout(() => { displayLimit.value += ITEMS_PER_PAGE; }, 300); };

onMounted(() => {
    observer = new IntersectionObserver((entries) => { if (entries[0].isIntersecting && hasMoreItems.value) loadMore(); }, { rootMargin: '100px' });
    if (observerTarget.value) observer.observe(observerTarget.value);
});
onUnmounted(() => { if (observer) observer.disconnect(); });

// --- LOGIC ---
const selectedItems = ref(new Set(Object.keys(props.cart || {})));
const formatCurrency = (amount) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount || 0);
const getImageUrl = (path) => path ? (path.startsWith('http') ? path : `/storage/${path}`) : '/images/default-product.png';

const cartTotal = computed(() => {
    let total = 0;
    Object.entries(props.cart || {}).forEach(([variantId, item]) => {
        if (selectedItems.value.has(variantId)) {
            const price = item.final_price || item.price;
            total += price * item.quantity;
        }
    });
    return total;
});

const cartItemsCount = computed(() => Object.keys(props.cart || {}).length);
const selectedItemsCount = computed(() => selectedItems.value.size);
const isAllSelected = computed(() => selectedItemsCount.value === cartItemsCount.value && cartItemsCount.value > 0);

// Helper Stok
const getStockStatus = (stock) => {
    if (stock <= 0) return { label: 'Sold Out', color: 'text-gray-400', bg: 'bg-gray-100 border-gray-200', icon: PackageX };
    if (stock < 5) return { label: `Hurry! Only ${stock} left`, color: 'text-orange-600', bg: 'bg-orange-50 border-orange-100', icon: AlertCircle, animate: true };
    return { label: 'In Stock', color: 'text-emerald-600', bg: 'bg-emerald-50 border-emerald-100', icon: PackageCheck };
};

const getDiscountPercent = (item) => {
    if (!item.final_price || item.final_price === 0 || item.final_price >= item.price) return 0;
    return Math.round(((item.price - item.final_price) / item.price) * 100);
};

const getSavedAmount = (item) => {
    const price = Number(item.price) || 0;
    const finalPrice = Number(item.final_price) || 0;
    if (finalPrice === 0 || finalPrice >= price) return 0;
    return price - finalPrice;
};

// --- ACTIONS ---
const toggleSelectAll = () => { selectedItems.value = isAllSelected.value ? new Set() : new Set(Object.keys(props.cart || {})); };
const toggleSelectItem = (id) => { selectedItems.value.has(id) ? selectedItems.value.delete(id) : selectedItems.value.add(id); };
const isSelected = (id) => selectedItems.value.has(id);

const updateQuantity = (variantId, quantity) => { if (quantity < 1) return; router.patch(`/cart/${variantId}`, { quantity }, { preserveScroll: true }); };

const removeFromCart = (variantId) => {
    Swal.fire({
        title: 'Remove Item?', text: "Remove this item from your cart?", icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#f43f5e', confirmButtonText: 'Yes, remove', cancelButtonText: 'No',
        customClass: { popup: 'rounded-2xl' }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/cart/${variantId}`, { preserveScroll: true });
            selectedItems.value.delete(variantId);
        }
    });
};

const checkout = () => {
    const user = usePage().props.auth.user;
    if (user && user.is_banned) {
        return Swal.fire({
            icon: 'error', title: 'Account Restricted', text: 'Your purchasing privileges have been suspended.', confirmButtonColor: '#e11d48', confirmButtonText: 'Contact Support', background: '#fff', customClass: { popup: 'rounded-2xl shadow-xl' }
        });
    }
    if (selectedItemsCount.value === 0) return Swal.fire('Select Items', 'Please select at least 1 item.', 'info');
    const items = Array.from(selectedItems.value);
    const qtyMap = {};
    items.forEach(id => { if (props.cart[id]) qtyMap[id] = props.cart[id].quantity; });
    router.get('/checkout', { items, quantity: qtyMap, from_cart: true });
};
</script>

<template>
    <Head title="Shopping Cart" />

    <div class="min-h-screen bg-[#F8F9FA] py-6 sm:py-12 pb-40 sm:pb-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-6 sm:mb-8 flex items-end justify-between border-b border-gray-200 pb-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">Shopping Cart</h1>
                    <p class="text-xs sm:text-base text-gray-500 font-medium">{{ cartItemsCount }} items ready for checkout</p>
                </div>
            </div>

            <div v-if="cartItemsCount === 0" class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 p-10 sm:p-20 text-center">
                <div class="w-20 h-20 sm:w-24 sm:h-24 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <ShoppingCart class="w-8 h-8 sm:w-10 sm:h-10 text-rose-400" />
                </div>
                <h3 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2">Your cart is empty</h3>
                <button @click="router.get('/catalog')" class="mt-6 inline-flex items-center gap-2 px-6 py-3 sm:px-8 sm:py-3.5 bg-gradient-to-r from-rose-500 to-pink-600 text-white font-bold rounded-xl hover:shadow-lg hover:shadow-rose-200 hover:-translate-y-1 transition-all duration-300 text-sm sm:text-base">
                    Start Shopping <ArrowRight class="w-4 h-4" />
                </button>
            </div>

            <div v-else class="space-y-4 sm:space-y-6">
                
                <div class="bg-white rounded-2xl p-3 sm:p-4 border border-gray-100 flex items-center justify-between sticky top-4 z-20 shadow-sm ring-1 ring-black/5">
                    <div class="text-xs sm:text-sm font-bold text-rose-600 bg-rose-50 px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl border border-rose-100">
                        Total: <span class="text-sm sm:text-lg ml-1">{{ formatCurrency(cartTotal) }}</span>
                    </div>

                    <label class="flex items-center gap-2 sm:gap-3 cursor-pointer select-none group border-l border-gray-200 pl-3 sm:pl-4">
                        <span class="font-bold text-xs sm:text-base text-gray-600 group-hover:text-rose-600 transition-colors">Select All <span class="hidden sm:inline">({{ selectedItemsCount }})</span></span>
                        <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="hidden" />
                        <div class="w-6 h-6 sm:w-8 sm:h-8 border-2 rounded-lg sm:rounded-xl flex items-center justify-center transition-all duration-300"
                            :class="isAllSelected ? 'bg-white border-rose-500 shadow-md scale-110' : 'bg-white border-gray-300 group-hover:border-rose-300'">
                            <CheckCircle2 v-if="isAllSelected" class="w-4 h-4 sm:w-5 sm:h-5 text-rose-500" />
                        </div>
                    </label>
                </div>

                <div class="space-y-4 sm:space-y-5">
                    <div v-for="(item, variantId) in visibleCart" :key="variantId">
                        
                        <div class="block sm:hidden bg-white rounded-2xl border border-gray-100 p-4 shadow-sm hover:shadow-md transition-all relative overflow-hidden"
                             :class="{ 'ring-1 ring-rose-200 bg-rose-50/10': isSelected(variantId) }">
                            
                            <div class="flex gap-3">
                                <div class="shrink-0 flex items-center justify-center self-center">
                                    <label class="cursor-pointer p-1 -ml-1">
                                        <input type="checkbox" :checked="isSelected(variantId)" @change="toggleSelectItem(variantId)" class="hidden" />
                                        <div class="w-6 h-6 border-2 border-gray-300 rounded-full flex items-center justify-center transition-all shadow-sm"
                                            :class="isSelected(variantId) ? 'border-rose-500 bg-rose-500' : 'bg-white'">
                                            <CheckCircle2 v-if="isSelected(variantId)" class="w-4 h-4 text-white" />
                                        </div>
                                    </label>
                                </div>

                                <div class="shrink-0 w-20 h-20 bg-[#F9FAFB] rounded-xl border border-gray-100 overflow-hidden relative">
                                    <img :src="getImageUrl(item.image_url)" :alt="item.name" class="w-full h-full object-contain mix-blend-multiply" />
                                    <div v-if="item.stock <= 0" class="absolute inset-0 bg-white/80 flex items-center justify-center backdrop-blur-[1px]">
                                        <span class="text-[9px] font-bold uppercase bg-gray-900 text-white px-2 py-1 rounded">Habis</span>
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-center gap-1 mb-1">
                                            <Store class="w-3 h-3 text-rose-400" />
                                            <span v-if="item.brand_name" class="text-[9px] font-bold text-gray-400 uppercase truncate">{{ item.brand_name }}</span>
                                        </div>
                                        <Link :href="`/products/${item.product_slug}/${item.product_id}`" class="text-sm font-bold text-gray-900 leading-tight line-clamp-2 hover:text-rose-600">
                                            {{ item.name }}
                                        </Link>
                                    </div>

                                    <div class="mt-1">
                                        <div v-if="Number(item.final_price) < Number(item.price) && Number(item.final_price) > 0">
                                            <div class="flex items-center gap-1">
                                                <span class="text-[10px] text-gray-400 line-through">{{ formatCurrency(item.price) }}</span>
                                                <span class="text-[9px] font-bold text-rose-600 bg-rose-50 px-1 rounded">-{{ getDiscountPercent(item) }}%</span>
                                            </div>
                                            <div class="text-sm font-black text-rose-600">{{ formatCurrency(item.final_price) }}</div>
                                        </div>
                                        <div v-else class="text-sm font-black text-gray-900">{{ formatCurrency(item.price) }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="h-px bg-gray-100 my-3"></div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1 text-[9px] font-bold px-2 py-1 rounded border uppercase"
                                    :class="[getStockStatus(item.stock).color, getStockStatus(item.stock).bg]">
                                    <component :is="getStockStatus(item.stock).icon" class="w-3 h-3" />
                                    {{ getStockStatus(item.stock).label }}
                                </div>

                                <div class="flex items-center gap-3">
                                    <button @click="removeFromCart(variantId)" class="p-1.5 text-gray-400 hover:text-red-500">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                    
                                    <div class="flex items-center bg-gray-50 rounded-lg border border-gray-200 p-0.5">
                                        <button @click="updateQuantity(variantId, item.quantity - 1)" :disabled="item.quantity <= 1" class="w-7 h-7 flex items-center justify-center bg-white rounded shadow-sm text-gray-600 disabled:opacity-50"><Minus class="w-3 h-3" /></button>
                                        <span class="w-8 text-center text-xs font-bold text-gray-800">{{ item.quantity }}</span>
                                        <button @click="updateQuantity(variantId, item.quantity + 1)" :disabled="item.quantity >= item.stock" class="w-7 h-7 flex items-center justify-center bg-white rounded shadow-sm text-gray-600 disabled:opacity-50"><Plus class="w-3 h-3" /></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="hidden sm:flex bg-white rounded-[2rem] border border-gray-100 p-5 shadow-sm hover:shadow-lg transition-all duration-300 group relative overflow-hidden flex-row gap-6"
                             :class="{ 'ring-2 ring-rose-100 bg-rose-50/20': isSelected(variantId) }">
                            
                            <div class="relative w-40 h-auto shrink-0 bg-[#F9FAFB] flex items-center justify-center border-r border-gray-100 group-hover:border-rose-100 transition-colors p-4 rounded-2xl">
                                <img :src="getImageUrl(item.image_url)" :alt="item.name" class="w-full h-full object-contain mix-blend-multiply" />
                                <div v-if="item.stock <= 0" class="absolute inset-0 bg-white/80 flex items-center justify-center backdrop-blur-[1px]">
                                    <span class="text-[10px] font-bold uppercase bg-gray-900 text-white px-2 py-1 rounded">Sold Out</span>
                                </div>
                            </div>

                            <div class="flex-1 flex flex-col justify-center min-w-0 gap-2">
                                <div class="flex justify-between items-start gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <Store class="w-3 h-3 text-rose-400" />
                                            <span v-if="item.brand_name" class="text-[10px] font-extrabold text-gray-400 uppercase tracking-wider">{{ item.brand_name }}</span>
                                        </div>
                                        <Link :href="`/products/${item.product_slug}/${item.product_id}`" class="font-black text-gray-900 text-xl leading-tight truncate hover:text-rose-600 transition-colors block mb-1">
                                            {{ item.name }}
                                        </Link>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs font-medium text-gray-600 bg-white border border-gray-200 px-2.5 py-1 rounded-lg flex items-center gap-1.5 shadow-sm">
                                                <Package class="w-3.5 h-3.5 text-gray-400" /> {{ item.volume }}
                                            </span>
                                            <div v-if="item.tags && item.tags.length > 0" class="flex flex-wrap gap-1.5">
                                                <span v-for="tag in (item.tags || []).slice(0, 2)" :key="tag" 
                                                      class="text-[9px] font-bold text-indigo-600 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded uppercase flex items-center gap-1">
                                                    <Tag class="w-3 h-3" /> {{ tag }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-right shrink-0">
                                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wide mb-0.5">Subtotal</p>
                                        <div v-if="Number(item.final_price) < Number(item.price) && Number(item.final_price) > 0" class="flex flex-col items-end">
                                            <span v-if="item.discount_type === 'fixed'" class="bg-blue-600 text-white text-[9px] font-bold px-2 py-0.5 rounded shadow-sm mb-1">
                                                SAVE {{ formatCurrency(getSavedAmount(item) * item.quantity) }}
                                            </span>
                                            <span v-else-if="getDiscountPercent(item) > 0" class="bg-rose-500 text-white text-[9px] font-bold px-2 py-0.5 rounded shadow-sm mb-1 animate-pulse">
                                                {{ getDiscountPercent(item) }}% OFF
                                            </span>
                                            <span class="text-xs text-gray-400 line-through decoration-rose-300">
                                                {{ formatCurrency(item.price * item.quantity) }}
                                            </span>
                                            <span class="text-xl font-black text-rose-600 tracking-tight">
                                                {{ formatCurrency(item.final_price * item.quantity) }}
                                            </span>
                                        </div>
                                        <div v-else>
                                            <span class="text-xl font-black text-gray-900 tracking-tight">
                                                {{ formatCurrency(item.price * item.quantity) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-1 w-fit flex items-center gap-1.5 text-[10px] font-bold px-2.5 py-1 rounded-lg border uppercase tracking-wide"
                                    :class="[getStockStatus(item.stock).color, getStockStatus(item.stock).bg, getStockStatus(item.stock).animate ? 'animate-pulse' : '']">
                                    <component :is="getStockStatus(item.stock).icon" class="w-3.5 h-3.5" />
                                    {{ getStockStatus(item.stock).label }}
                                </div>
                            </div>

                            <div class="flex flex-col justify-center gap-3 border-l border-gray-100 pl-6 pr-4 py-2 w-56 bg-gray-50/30 rounded-r-xl">
                                <div class="flex items-center bg-white rounded-xl border border-gray-200 p-1 shadow-sm justify-between">
                                    <button @click="updateQuantity(variantId, item.quantity - 1)" :disabled="item.quantity <= 1" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-600 hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30 transition-colors"><Minus class="w-4 h-4" /></button>
                                    <span class="w-8 text-center font-bold text-gray-900 text-sm">{{ item.quantity }}</span>
                                    <button @click="updateQuantity(variantId, item.quantity + 1)" :disabled="item.quantity >= item.stock" class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-600 hover:bg-rose-50 hover:text-rose-600 disabled:opacity-30 transition-colors"><Plus class="w-4 h-4" /></button>
                                </div>

                                <div class="flex justify-end gap-2">
                                    <Link 
                                        :href="`/products/${item.product_slug}/${item.product_id}`"
                                        class="flex-1 p-2.5 text-gray-400 bg-white border border-gray-200 rounded-xl hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition-all shadow-sm flex justify-center items-center"
                                        title="View Details"
                                    >
                                        <Eye class="w-4 h-4" />
                                    </Link>

                                    <button 
                                        @click="removeFromCart(variantId)" 
                                        class="flex-1 p-2.5 text-gray-400 bg-white border border-gray-200 rounded-xl hover:text-red-600 hover:border-red-200 hover:bg-red-50 transition-all shadow-sm flex justify-center items-center"
                                        title="Remove Item"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <label class="w-20 bg-gray-50 border-l border-gray-200 flex flex-col items-center justify-center cursor-pointer hover:bg-rose-50/50 transition-colors shrink-0 group/chk">
                                <input type="checkbox" :checked="isSelected(variantId)" @change="toggleSelectItem(variantId)" class="hidden" />
                                <div class="w-10 h-10 border-2 rounded-xl flex items-center justify-center transition-all duration-300 shadow-sm bg-white" 
                                        :class="isSelected(variantId) ? 'border-rose-500 scale-110 shadow-rose-200' : 'border-gray-300 group-hover/chk:border-rose-400'">
                                    <CheckCircle2 v-if="isSelected(variantId)" class="w-6 h-6 text-rose-500" />
                                </div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase mt-2 group-hover/chk:text-rose-500 transition-colors">Select</span>
                            </label>

                        </div>

                    </div>
                </div>

                <div v-if="hasMoreItems" ref="observerTarget" class="py-6 sm:py-8 text-center">
                     <span class="inline-block px-3 py-1 sm:px-4 sm:py-1 bg-white border border-gray-100 rounded-full text-[10px] font-bold text-gray-400 uppercase tracking-widest animate-pulse shadow-sm">Loading...</span>
                </div>

                <div class="block sm:hidden fixed bottom-[70px] left-0 w-full px-4 z-40">
                    <div class="bg-white/95 backdrop-blur-md border border-rose-200 shadow-[0_-5px_20px_rgba(0,0,0,0.1)] rounded-2xl p-4 flex flex-col gap-3">
                        <div class="flex justify-between items-end">
                            <span class="text-xs font-bold text-gray-400 uppercase">Total</span>
                            <span class="text-xl font-black text-rose-600">{{ formatCurrency(cartTotal) }}</span>
                        </div>
                        <button 
                            @click="checkout"
                            :disabled="selectedItemsCount === 0"
                            class="w-full py-3 bg-gradient-to-r from-rose-600 to-pink-600 text-white font-bold rounded-xl shadow-lg active:scale-95 transition-all disabled:opacity-50"
                        >
                            Checkout ({{ selectedItemsCount }})
                        </button>
                    </div>
                </div>

                <div class="hidden sm:flex sticky bottom-4 z-30 bg-white/90 backdrop-blur-md border border-rose-200 shadow-2xl rounded-2xl p-5 items-center justify-between gap-4 animate-in slide-in-from-bottom-6 duration-500">
                    <div class="text-left">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Total Payment</p>
                        <p class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-rose-600 to-pink-600 tracking-tighter leading-none">{{ formatCurrency(cartTotal) }}</p>
                    </div>
                    
                    <button 
                        @click="checkout"
                        :disabled="selectedItemsCount === 0"
                        class="px-12 py-4 bg-gradient-to-r from-rose-500 to-pink-600 text-white font-bold rounded-xl shadow-lg shadow-rose-200 hover:shadow-xl hover:scale-[1.02] active:scale-95 transition-all disabled:from-gray-300 disabled:to-gray-400 disabled:shadow-none disabled:cursor-not-allowed flex items-center justify-center gap-3 group"
                    >
                        <span>Checkout</span>
                        <span v-if="selectedItemsCount > 0" class="bg-white/20 px-2.5 py-0.5 rounded-lg text-xs font-black">{{ selectedItemsCount }}</span>
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>