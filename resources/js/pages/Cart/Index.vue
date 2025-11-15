<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useFormatting } from '@/composables/useFormatting';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import { X, Plus, Minus, Trash2, ShoppingBag, ArrowRight, Sparkles } from 'lucide-vue-next';
import ConfirmationModal from '@/components/ConfirmationModal.vue';

defineOptions({
    layout: AppNavbarLayout
});

const props = defineProps({
    cart: Object
});

const { formatCurrency } = useFormatting();

// State management
const selectedItems = ref(Object.keys(props.cart));
const isModalOpen = ref(false);
const itemToRemove = ref(null);
const isLoading = ref(false);
const updateTimeout = ref(null);
const recentlyUpdated = ref(new Set());

// Computed properties
const cartTotal = computed(() => {
    let total = 0;
    selectedItems.value.forEach(variantId => {
        if (props.cart[variantId]) {
            total += props.cart[variantId].price * props.cart[variantId].quantity;
        }
    });
    return total;
});

const isCartEmpty = computed(() => {
    return Object.keys(props.cart).length === 0;
});

const selectedItemsCount = computed(() => selectedItems.value.length);
const totalItemsCount = computed(() => Object.keys(props.cart).length);

// Watch for cart changes to update selected items
watch(() => props.cart, (newCart) => {
    selectedItems.value = selectedItems.value.filter(id => newCart[id]);
}, { deep: true });

// Quantity management with animation feedback
const updateQuantity = async (variantId, newQuantity) => {
    if (newQuantity < 1) return;
    
    recentlyUpdated.value.add(variantId);
    props.cart[variantId].quantity = newQuantity;
    
    if (updateTimeout.value) clearTimeout(updateTimeout.value);
    
    updateTimeout.value = setTimeout(async () => {
        isLoading.value = true;
        try {
            await router.patch(`/cart/${variantId}`, 
                { quantity: newQuantity }, 
                { preserveScroll: true }
            );
        } finally {
            isLoading.value = false;
            setTimeout(() => {
                recentlyUpdated.value.delete(variantId);
            }, 1000);
        }
    }, 500);
};

// Quick quantity adjustments
const quickIncrement = (variantId) => {
    updateQuantity(variantId, props.cart[variantId].quantity + 1);
};

const quickDecrement = (variantId) => {
    if (props.cart[variantId].quantity > 1) {
        updateQuantity(variantId, props.cart[variantId].quantity - 1);
    }
};

// Selection management
const toggleSelectAll = () => {
    if (selectedItems.value.length === totalItemsCount.value) {
        selectedItems.value = [];
    } else {
        selectedItems.value = Object.keys(props.cart);
    }
};

const isItemSelected = (variantId) => {
    return selectedItems.value.includes(variantId);
};

// Modal management
const openRemoveModal = (variantId) => {
    itemToRemove.value = variantId;
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    itemToRemove.value = null;
};

const confirmRemove = async () => {
    if (itemToRemove.value) {
        const variantId = itemToRemove.value;
        selectedItems.value = selectedItems.value.filter(id => id !== variantId);
        
        isLoading.value = true;
        try {
            await router.delete(`/cart/${variantId}`, {
                preserveScroll: true,
                onSuccess: () => {
                    closeModal();
                }
            });
        } finally {
            isLoading.value = false;
        }
    }
};

// Checkout process
const handleCheckout = async () => {
    if (selectedItemsCount.value === 0) {
        showNotification('Please select at least one item to checkout.', 'warning');
        return;
    }
    
    isLoading.value = true;
    try {
        showNotification(`Processing ${selectedItemsCount.value} items for checkout...`, 'success');
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Redirect to checkout page with selected items
        router.post('/checkout', { 
            items: selectedItems.value 
        });
        
    } finally {
        isLoading.value = false;
    }
};

// Notification system
const showNotification = (message, type = 'info') => {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-transform duration-300 ${
        type === 'warning' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' :
        type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' :
        'bg-blue-100 text-blue-800 border border-blue-200'
    }`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('translate-x-0', 'opacity-100');
    }, 100);
    
    setTimeout(() => {
        notification.classList.remove('translate-x-0', 'opacity-100');
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
};
</script>

<template>
    <Head title="Shopping Cart" />

    <div class="bg-rose-50 min-h-screen py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Enhanced Header -->
            <div class="text-center mb-12">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-rose-100 rounded-full mb-4">
                    <ShoppingBag class="w-8 h-8 text-rose-600" />
                </div>
                <h1 class="text-4xl font-light text-gray-900 tracking-tight mb-2">
                    Shopping Cart
                </h1>
                <p class="text-gray-600" v-if="!isCartEmpty">
                    {{ totalItemsCount }} item{{ totalItemsCount !== 1 ? 's' : '' }} in your cart
                </p>
            </div>

            <!-- Empty Cart State -->
            <div v-if="isCartEmpty" class="bg-white rounded-2xl shadow-xl p-12 text-center max-w-md mx-auto">
                <div class="w-24 h-24 bg-rose-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <ShoppingBag class="w-12 h-12 text-rose-400" />
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Your cart is empty</h3>
                <p class="text-gray-600 mb-6">Discover our amazing products and fill your cart with beauty essentials.</p>
                <Link 
                    href="/catalog" 
                    class="inline-flex items-center px-8 py-4 bg-rose-600 text-white text-base font-semibold rounded-full shadow-lg hover:bg-rose-700 transition-all duration-300 transform hover:scale-105 group"
                >
                    <Sparkles class="w-5 h-5 mr-2 group-hover:rotate-12 transition-transform" />
                    Start Shopping
                    <ArrowRight class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" />
                </Link>
            </div>

            <!-- Cart with Items -->
            <div v-else class="flex flex-col lg:flex-row gap-8">
                
                <!-- Left Column: Cart Items -->
                <div class="w-full lg:w-2/3">
                    <!-- Cart Header -->
                    <div class="bg-white rounded-2xl shadow-xl p-6 mb-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <input 
                                    type="checkbox"
                                    :checked="selectedItems.length === totalItemsCount"
                                    @change="toggleSelectAll"
                                    class="h-5 w-5 text-rose-600 border-gray-300 rounded focus:ring-rose-500"
                                />
                                <span class="text-sm font-medium text-gray-700">
                                    Select all items ({{ selectedItemsCount }}/{{ totalItemsCount }})
                                </span>
                            </div>
                            <div class="text-sm text-gray-500">
                                Subtotal: {{ formatCurrency(cartTotal) }}
                            </div>
                        </div>
                    </div>

                    <!-- Cart Items List -->
                    <div class="bg-white rounded-2xl shadow-xl overflow-hidden divide-y divide-gray-100">
                        <div 
                            v-for="(item, variantId) in cart" 
                            :key="variantId" 
                            class="p-6 flex space-x-4 transition-all duration-300 hover:bg-gray-50 group"
                            :class="{
                                'bg-rose-50 border-l-4 border-l-rose-500': recentlyUpdated.has(variantId),
                                'opacity-60': !isItemSelected(variantId)
                            }"
                        >
                            <!-- Checkbox -->
                            <div class="flex items-center">
                                <input 
                                    type="checkbox"
                                    :id="`item-${variantId}`"
                                    :value="variantId"
                                    v-model="selectedItems"
                                    class="h-5 w-5 text-rose-600 border-gray-300 rounded focus:ring-rose-500 transition-colors"
                                />
                            </div>

                            <!-- Product Image -->
                            <div class="flex-shrink-0 relative">
                                <div class="relative">
                                    <img 
                                        :src="item.image_url || '/images/default-product.png'" 
                                        :alt="item.name"
                                        class="w-20 h-20 rounded-lg object-cover shadow-sm transition-transform duration-300 group-hover:scale-105"
                                    />
                                    <div 
                                        v-if="recentlyUpdated.has(variantId)"
                                        class="absolute -top-2 -right-2 w-6 h-6 bg-rose-500 rounded-full flex items-center justify-center animate-pulse"
                                    >
                                        <Sparkles class="w-3 h-3 text-white" />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Product Details -->
                            <div class="flex-1 flex flex-col justify-between min-w-0">
                                <div>
                                    <Link 
                                        :href="`/products/${item.product_slug}/${item.product_id}`"
                                        class="text-lg font-semibold text-gray-900 hover:text-rose-600 transition-colors line-clamp-2"
                                    >
                                        {{ item.name }}
                                    </Link>
                                    <p class="text-sm text-rose-600 font-medium mt-1">
                                        {{ formatCurrency(item.price) }}
                                    </p>
                                </div>
                                
                                <!-- Quantity Controls -->
                                <div class="flex items-center space-x-3 mt-3">
                                    <button
                                        @click="quickDecrement(variantId)"
                                        :disabled="item.quantity <= 1 || isLoading"
                                        class="w-8 h-8 flex items-center justify-center border border-gray-300 text-gray-600 rounded-full hover:bg-gray-100 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed hover:border-rose-300"
                                        :class="{ 'animate-pulse': isLoading }"
                                    >
                                        <Minus class="w-3 h-3" />
                                    </button>
                                    
                                    <div class="relative">
                                        <input 
                                            type="text" 
                                            :value="item.quantity"
                                            readonly
                                            class="w-12 h-8 text-center border border-gray-300 text-gray-900 rounded-md bg-white font-medium transition-all duration-200"
                                        />
                                        <div 
                                            v-if="recentlyUpdated.has(variantId)"
                                            class="absolute inset-0 border-2 border-rose-400 rounded-md animate-ping opacity-60"
                                        ></div>
                                    </div>
                                    
                                    <button
                                        @click="quickIncrement(variantId)"
                                        :disabled="isLoading"
                                        class="w-8 h-8 flex items-center justify-center border border-gray-300 text-gray-600 rounded-full hover:bg-gray-100 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed hover:border-rose-300"
                                        :class="{ 'animate-pulse': isLoading }"
                                    >
                                        <Plus class="w-3 h-3" />
                                    </button>
                                </div>
                            </div>

                            <!-- Price and Actions -->
                            <div class="text-right flex flex-col justify-between items-end space-y-2">
                                <p class="text-xl font-bold text-gray-900 transition-all duration-300">
                                    {{ formatCurrency(item.price * item.quantity) }}
                                </p>
                                <button
                                    @click="openRemoveModal(variantId)"
                                    class="flex items-center space-x-1 px-3 py-1.5 text-red-600 hover:text-red-700 rounded-lg hover:bg-red-50 transition-all duration-200 group/remove"
                                >
                                    <Trash2 class="w-4 h-4 transition-transform group-hover/remove:scale-110" />
                                    <span class="text-sm font-medium">Remove</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Order Summary -->
                <div class="w-full lg:w-1/3">
                    <div class="bg-white rounded-2xl shadow-xl p-6 sticky top-6 transition-all duration-300 hover:shadow-2xl">
                        <h2 class="text-2xl font-bold text-gray-900 border-b border-gray-200 pb-4 mb-4">
                            Order Summary
                        </h2>
                        
                        <div class="space-y-4">
                            <!-- Simple Total Display -->
                            <div class="flex justify-between items-center py-2">
                                <span class="text-gray-600">Items ({{ selectedItemsCount }})</span>
                                <span class="text-gray-900 font-semibold">{{ formatCurrency(cartTotal) }}</span>
                            </div>
                            
                            <!-- Final Total -->
                            <div class="border-t border-gray-200 pt-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-bold text-gray-900">Total Amount</span>
                                    <span class="text-2xl font-bold text-rose-600">
                                        {{ formatCurrency(cartTotal) }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Simple Info Message -->
                            <p class="text-xs text-gray-500 text-center pt-2">
                                Shipping and taxes will be calculated during checkout
                            </p>
                        </div>
                        
                        <!-- Checkout Button -->
                        <button 
                            @click="handleCheckout"
                            :disabled="selectedItemsCount === 0 || isLoading"
                            class="w-full mt-6 py-4 bg-gradient-to-r from-rose-600 to-pink-600 text-white font-bold rounded-xl shadow-lg transition-all duration-300 transform hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none group relative overflow-hidden"
                        >
                            <div class="relative z-10 flex items-center justify-center">
                                <span>Proceed to Checkout</span>
                                <ArrowRight class="w-5 h-5 ml-2 group-hover:translate-x-1 transition-transform" />
                            </div>
                            <div class="absolute inset-0 bg-gradient-to-r from-rose-700 to-pink-700 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            
                            <!-- Loading overlay -->
                            <div 
                                v-if="isLoading"
                                class="absolute inset-0 bg-rose-600 flex items-center justify-center rounded-xl"
                            >
                                <div class="w-6 h-6 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
                            </div>
                        </button>

                        <!-- Continue Shopping -->
                        <Link 
                            href="/catalog" 
                            class="w-full mt-3 py-3 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all duration-300 text-center block"
                        >
                            Continue Shopping
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <ConfirmationModal 
        :show="isModalOpen" 
        @close="closeModal"
        @confirm="confirmRemove"
        title="Remove Item"
        message="Are you sure you want to remove this item from your cart?"
        confirm-text="Remove"
        cancel-text="Keep Item"
    />
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

* {
    transition-property: color, background-color, border-color, transform, box-shadow;
    transition-duration: 200ms;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>