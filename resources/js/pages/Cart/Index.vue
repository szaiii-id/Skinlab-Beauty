<script setup>
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import Swal from 'sweetalert2';

defineOptions({
    layout: DashboardLayout
});

// --- PROPS ---
const props = defineProps({
    cart: {
        type: Object,
        default: () => ({})
    }
});

// --- INFINITE SCROLL LOGIC ---
const ITEMS_PER_PAGE = 10;
const displayLimit = ref(ITEMS_PER_PAGE);
const observerTarget = ref(null);
let observer = null;

const allCartEntries = computed(() => Object.entries(props.cart || {}));

const visibleCart = computed(() => {
    const sliced = allCartEntries.value.slice(0, displayLimit.value);
    return Object.fromEntries(sliced);
});

const hasMoreItems = computed(() => {
    return displayLimit.value < allCartEntries.value.length;
});

const loadMore = () => {
    if (hasMoreItems.value) {
        setTimeout(() => {
            displayLimit.value += ITEMS_PER_PAGE;
        }, 300);
    }
};

onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && hasMoreItems.value) {
            loadMore();
        }
    }, { rootMargin: '100px' });

    if (observerTarget.value) observer.observe(observerTarget.value);
});

onUnmounted(() => {
    if (observer) observer.disconnect();
});

// --- CART BUSINESS LOGIC ---

const selectedItems = ref(new Set(Object.keys(props.cart || {})));

const formatCurrency = (amount) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0
    }).format(amount);
};

const cartTotal = computed(() => {
    let total = 0;
    Object.entries(props.cart || {}).forEach(([variantId, item]) => {
        if (selectedItems.value.has(variantId)) {
            total += item.price * item.quantity;
        }
    });
    return total;
});

const cartItemsCount = computed(() => Object.keys(props.cart || {}).length);
const selectedItemsCount = computed(() => selectedItems.value.size);
const isAllSelected = computed(() => selectedItemsCount.value === cartItemsCount.value && cartItemsCount.value > 0);

// [UPDATED] Remove Item with Themed Popup
const removeFromCart = (variantId) => {
    Swal.fire({
        title: '<span class="text-gray-800">Remove Item?</span>',
        text: "Are you sure you want to remove this item from your cart?",
        icon: 'warning',
        iconColor: '#fb7185', // Rose-400 color
        showCancelButton: true,
        // Styling Buttons to match App Theme
        confirmButtonText: 'Yes, Remove it',
        cancelButtonText: 'Cancel',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-2xl border border-rose-100 shadow-xl',
            confirmButton: 'bg-gradient-to-r from-rose-500 to-pink-600 text-white font-bold py-3 px-6 rounded-xl hover:shadow-lg hover:scale-105 transition-transform duration-200 mx-2',
            cancelButton: 'bg-white text-gray-500 font-medium py-3 px-6 rounded-xl border border-gray-200 hover:bg-gray-50 hover:text-gray-700 transition-colors duration-200 mx-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(`/cart/${variantId}`, {
                preserveScroll: true, 
            });
            selectedItems.value.delete(variantId);

            // Themed Success Toast
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: 'success',
                title: 'Item removed successfully'
            });
        }
    });
};

const updateQuantity = (variantId, quantity) => {
    if (quantity < 1) return;
    router.patch(`/cart/${variantId}`, { quantity }, { preserveScroll: true });
};

const checkout = () => {
    if (selectedItemsCount.value === 0) {
        Swal.fire({
            title: 'No items selected',
            text: 'Please select at least 1 item to proceed.',
            icon: 'info',
            confirmButtonText: 'Okay',
            buttonsStyling: false,
            customClass: {
                popup: 'rounded-2xl',
                confirmButton: 'bg-rose-500 text-white font-bold py-2 px-6 rounded-lg'
            }
        });
        return;
    }
    
    const selectedVariantIds = Array.from(selectedItems.value);
    const quantities = {};
    
    selectedVariantIds.forEach(id => {
        if (props.cart[id]) {
            quantities[id] = props.cart[id].quantity;
        }
    });

    router.get('/checkout', { 
        items: selectedVariantIds,
        quantity: quantities,
        from_cart: true 
    });
};

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        selectedItems.value.clear();
    } else {
        selectedItems.value = new Set(Object.keys(props.cart || {}));
    }
};

const toggleSelectItem = (variantId) => {
    if (selectedItems.value.has(variantId)) {
        selectedItems.value.delete(variantId);
    } else {
        selectedItems.value.add(variantId);
    }
};

const isSelected = (variantId) => selectedItems.value.has(variantId);
</script>

<template>
    <Head title="Shopping Cart" />

    <div class="min-h-screen bg-gradient-to-br from-rose-50 to-pink-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-8 flex items-end justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-1">Shopping Cart</h1>
                    <p class="text-gray-500 font-medium">{{ cartItemsCount }} products added</p>
                </div>
            </div>

            <div v-if="cartItemsCount === 0" class="bg-white rounded-3xl shadow-sm border border-rose-100 p-16 text-center">
                <div class="w-24 h-24 bg-rose-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="text-5xl">🛍️</span>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Your cart is empty</h3>
                <p class="text-gray-500 mb-8 max-w-md mx-auto">It looks like you haven't discovered our beauty products yet.</p>
                <button @click="router.get('/catalog')" class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-rose-500 to-pink-600 text-white font-bold rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-300">
                    Browse Products
                </button>
            </div>

            <div v-else class="space-y-6">
                
                <div class="bg-white rounded-2xl shadow-sm border border-rose-100 p-5 flex items-center justify-between sticky top-4 z-20">
                    <div class="flex items-center gap-4">
                        <label class="flex items-center cursor-pointer group select-none relative">
                            <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="hidden" />
                            
                            <div class="w-6 h-6 border-2 rounded-lg flex items-center justify-center transition-all duration-300 ease-in-out"
                                :class="[
                                    isAllSelected 
                                        ? 'bg-gradient-to-br from-rose-500 to-pink-500 border-transparent shadow-md scale-110' 
                                        : 'bg-white border-rose-200 group-hover:border-rose-400'
                                ]">
                                <svg v-if="isAllSelected" class="w-3.5 h-3.5 text-white drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            
                            <span class="ml-3 text-gray-700 font-bold group-hover:text-rose-600 transition-colors">
                                Select All ({{ selectedItemsCount }})
                            </span>
                        </label>
                    </div>
                    <div class="text-gray-500 font-medium text-sm hidden sm:block">
                        Subtotal: <span class="text-rose-600 font-bold text-lg ml-1">{{ formatCurrency(cartTotal) }}</span>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-rose-100 overflow-hidden">
                    <div class="divide-y divide-rose-50">
                        <div 
                            v-for="(item, variantId) in visibleCart" 
                            :key="variantId" 
                            class="flex flex-col sm:flex-row items-start sm:items-center gap-4 p-5 transition-all duration-300 group"
                            :class="{ 'bg-rose-50/30': isSelected(variantId), 'hover:bg-white': !isSelected(variantId) }"
                        >
                            <div class="flex items-center gap-4 w-full">
                                <label class="flex items-center cursor-pointer relative">
                                    <input type="checkbox" :checked="isSelected(variantId)" @change="toggleSelectItem(variantId)" class="hidden" />
                                    
                                    <div class="w-5 h-5 border-2 rounded-md flex items-center justify-center transition-all duration-200"
                                        :class="[
                                            isSelected(variantId)
                                                ? 'bg-gradient-to-br from-rose-500 to-pink-500 border-transparent scale-110' 
                                                : 'bg-white border-gray-300 hover:border-rose-400'
                                        ]">
                                        <svg v-if="isSelected(variantId)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </label>

                                <div class="relative overflow-hidden rounded-xl border border-gray-100 shadow-sm w-20 h-20 flex-shrink-0 group-hover:shadow-md transition-shadow">
                                    <img :src="item.image_url || '/images/default-product.png'" :alt="item.name" class="w-full h-full object-cover transform group-hover:scale-105 transition-transform duration-500" />
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-bold text-gray-900 text-base sm:text-lg truncate leading-tight">{{ item.name }}</h3>
                                    <p class="text-rose-500 font-bold mt-1 text-sm sm:text-base">{{ formatCurrency(item.price) }}</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto pl-9 sm:pl-0">
                                <div class="flex items-center gap-3 bg-gray-50 rounded-lg p-1 border border-gray-200 shadow-inner">
                                    <button 
                                        @click="updateQuantity(variantId, item.quantity - 1)" 
                                        class="w-7 h-7 flex items-center justify-center bg-white border border-gray-200 rounded text-gray-600 hover:text-rose-600 hover:border-rose-300 disabled:opacity-50 transition-colors shadow-sm"
                                        :disabled="item.quantity <= 1"
                                    >-</button>
                                    <span class="w-8 text-center font-bold text-gray-800 text-sm">{{ item.quantity }}</span>
                                    <button 
                                        @click="updateQuantity(variantId, item.quantity + 1)" 
                                        class="w-7 h-7 flex items-center justify-center bg-white border border-gray-200 rounded text-gray-600 hover:text-rose-600 hover:border-rose-300 transition-colors shadow-sm"
                                    >+</button>
                                </div>

                                <div class="text-right min-w-[100px] hidden md:block">
                                    <p class="font-extrabold text-gray-900 text-lg">{{ formatCurrency(item.price * item.quantity) }}</p>
                                </div>

                                <button 
                                    @click="removeFromCart(variantId)" 
                                    class="p-2 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all duration-200" 
                                    title="Remove Item"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="hasMoreItems" ref="observerTarget" class="py-8 text-center">
                     <div class="inline-flex items-center gap-3 px-4 py-2 bg-white rounded-full shadow-sm border border-rose-100 text-rose-500">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-semibold">Loading more products...</span>
                     </div>
                </div>

                <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-rose-200 p-6 sticky bottom-4 z-30">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-center sm:text-left">
                            <p class="text-gray-500 text-sm">Total Payment</p>
                            <p class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-rose-600 to-pink-600">
                                {{ formatCurrency(cartTotal) }}
                            </p>
                        </div>
                        
                        <button 
                            @click="checkout"
                            :disabled="selectedItemsCount === 0"
                            class="w-full sm:w-auto px-8 py-4 bg-gradient-to-r from-rose-500 to-pink-600 text-white font-bold rounded-xl shadow-lg shadow-rose-500/30 hover:shadow-rose-500/50 hover:scale-105 disabled:from-gray-300 disabled:to-gray-400 disabled:shadow-none disabled:cursor-not-allowed disabled:scale-100 transition-all duration-300 flex items-center justify-center gap-2"
                        >
                            <span>Checkout</span>
                            <span v-if="selectedItemsCount > 0" class="bg-white/20 px-2 py-0.5 rounded text-sm">{{ selectedItemsCount }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>