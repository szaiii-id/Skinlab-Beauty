<!-- resources/js/pages/cart/index.vue -->
<script setup>
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { ref, computed } from 'vue';

defineOptions({
    layout: DashboardLayout
});

const props = defineProps({
    cart: Object
});

// State untuk selected items
const selectedItems = ref(new Set(Object.keys(props.cart || {})));

const formatCurrency = (amount) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
};

// Computed properties
const cartTotal = computed(() => {
    let total = 0;
    Object.entries(props.cart || {}).forEach(([variantId, item]) => {
        if (selectedItems.value.has(variantId)) {
            total += item.price * item.quantity;
        }
    });
    return total;
});

const cartItemsCount = computed(() => {
    return Object.keys(props.cart || {}).length;
});

const selectedItemsCount = computed(() => {
    return selectedItems.value.size;
});

const isAllSelected = computed(() => {
    return selectedItemsCount.value === cartItemsCount.value && cartItemsCount.value > 0;
});

// Methods
const removeFromCart = (variantId) => {
    router.delete(`/cart/${variantId}`);
    selectedItems.value.delete(variantId);
};

const updateQuantity = (variantId, quantity) => {
    if (quantity < 1) return;
    router.patch(`/cart/${variantId}`, { quantity });
};

// Update fungsi checkout di cart
const checkout = () => {
    if (selectedItemsCount.value === 0) {
        alert('Pilih minimal 1 produk untuk checkout');
        return;
    }
    
    // Redirect ke checkout dengan items yang dipilih
    router.get('/checkout', { 
        items: Array.from(selectedItems.value),
        from_cart: true // Flag untuk membedakan dari Buy Now
    });
};

// Selection methods
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

const isSelected = (variantId) => {
    return selectedItems.value.has(variantId);
};
</script>

<template>
    <Head title="Keranjang" />

    <div class="min-h-screen bg-gradient-to-br from-rose-50 to-pink-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-light text-gray-900 mb-2">
                    Keranjang Belanja
                </h1>
                <p class="text-gray-600">
                    {{ cartItemsCount }} item di keranjang Anda
                </p>
            </div>

            <!-- Empty State -->
            <div v-if="cartItemsCount === 0" class="bg-white rounded-2xl shadow border border-gray-100 p-12 text-center">
                <div class="text-6xl mb-4">🛒</div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Keranjang Kosong</h3>
                <p class="text-gray-600 mb-6">Belum ada produk di keranjang Anda</p>
                <button 
                    @click="router.get('/catalog')"
                    class="inline-flex items-center px-6 py-3 bg-rose-600 text-white font-semibold rounded-lg hover:bg-rose-700 transition-colors"
                >
                    Mulai Belanja
                </button>
            </div>

            <!-- Cart with Items -->
            <div v-else class="space-y-6">
                <!-- Selection Header -->
                <div class="bg-white rounded-2xl shadow border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <!-- Custom Checkbox -->
                            <label class="flex items-center cursor-pointer">
                                <input 
                                    type="checkbox"
                                    :checked="isAllSelected"
                                    @change="toggleSelectAll"
                                    class="hidden"
                                />
                                <div class="w-6 h-6 border-2 border-rose-300 rounded-lg flex items-center justify-center transition-all"
                                    :class="{
                                        'bg-rose-500 border-rose-500': isAllSelected,
                                        'hover:border-rose-400': !isAllSelected
                                    }">
                                    <svg v-if="isAllSelected" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="ml-3 text-sm font-medium text-gray-700">
                                    Pilih Semua ({{ selectedItemsCount }}/{{ cartItemsCount }})
                                </span>
                            </label>
                        </div>
                        <div class="text-sm text-gray-600">
                            Subtotal: {{ formatCurrency(cartTotal) }}
                        </div>
                    </div>
                </div>

                <!-- Cart Items List -->
                <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
                    <div class="divide-y divide-gray-100">
                        <div 
                            v-for="(item, variantId) in cart" 
                            :key="variantId" 
                            class="flex items-center gap-4 p-6 transition-colors"
                            :class="{
                                'bg-rose-50': isSelected(variantId),
                                'hover:bg-gray-50': !isSelected(variantId)
                            }"
                        >
                            <!-- Custom Checkbox -->
                            <label class="flex items-center cursor-pointer">
                                <input 
                                    type="checkbox"
                                    :checked="isSelected(variantId)"
                                    @change="toggleSelectItem(variantId)"
                                    class="hidden"
                                />
                                <div class="w-5 h-5 border-2 border-rose-300 rounded flex items-center justify-center transition-all"
                                    :class="{
                                        'bg-rose-500 border-rose-500': isSelected(variantId),
                                        'hover:border-rose-400': !isSelected(variantId)
                                    }">
                                    <svg v-if="isSelected(variantId)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </label>

                            <!-- Product Image -->
                            <img 
                                :src="item.image_url || '/images/default-product.png'" 
                                :alt="item.name"
                                class="w-20 h-20 rounded-lg object-cover"
                            />
                            
                            <!-- Product Details -->
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 text-lg">{{ item.name }}</h3>
                                <p class="text-rose-600 font-medium mt-1">{{ formatCurrency(item.price) }}</p>
                            </div>

                            <!-- Quantity Controls -->
                            <div class="flex items-center gap-3">
                                <button 
                                    @click="updateQuantity(variantId, item.quantity - 1)"
                                    class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    -
                                </button>
                                <span class="w-12 text-center font-medium">{{ item.quantity }}</span>
                                <button 
                                    @click="updateQuantity(variantId, item.quantity + 1)"
                                    class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    +
                                </button>
                            </div>

                            <!-- Total Price -->
                            <div class="text-right min-w-24">
                                <p class="font-semibold text-gray-900 text-lg">{{ formatCurrency(item.price * item.quantity) }}</p>
                            </div>

                            <!-- Remove Button -->
                            <button 
                                @click="removeFromCart(variantId)"
                                class="p-2 text-gray-400 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50"
                                title="Hapus dari keranjang"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="bg-white rounded-2xl shadow border border-gray-100 p-6">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-lg">
                            <span class="text-gray-600">Item Terpilih:</span>
                            <span class="font-semibold text-gray-900">{{ selectedItemsCount }} produk</span>
                        </div>
                        <div class="flex justify-between items-center text-xl font-semibold border-t border-gray-200 pt-4">
                            <span>Total Pembayaran</span>
                            <span class="text-rose-600 text-2xl">{{ formatCurrency(cartTotal) }}</span>
                        </div>
                        
                        <button 
                            @click="checkout"
                            :disabled="selectedItemsCount === 0"
                            class="w-full py-4 bg-rose-600 text-white font-semibold rounded-lg hover:bg-rose-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors mt-4 text-lg"
                        >
                            Checkout ({{ selectedItemsCount }} Produk)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>