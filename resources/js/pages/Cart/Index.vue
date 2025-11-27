<script setup>
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
// Tambahkan onMounted & onUnmounted untuk Observer
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

defineOptions({
    layout: DashboardLayout
});

const props = defineProps({
    cart: Object // Data cart dari session/props (Semua data ada di sini)
});

// --- LOGIC INFINITE SCROLL CLIENT-SIDE ---
const itemsPerPage = 10;
const displayLimit = ref(10);
const observerTarget = ref(null);
let observer = null;

// Mengubah Object Cart menjadi Array [key, value] agar bisa dihitung index-nya
const allCartEntries = computed(() => Object.entries(props.cart || {}));

// Hanya ambil sebagian data sesuai limit untuk ditampilkan
const visibleCart = computed(() => {
    // Slice array dari 0 sampai limit
    const sliced = allCartEntries.value.slice(0, displayLimit.value);
    // Kembalikan ke bentuk Object agar logic checkbox di bawah tetap jalan
    return Object.fromEntries(sliced);
});

// Cek apakah masih ada barang tersembunyi
const hasMoreItems = computed(() => {
    return displayLimit.value < allCartEntries.value.length;
});

// Fungsi Load More (Cukup tambah limit, tidak perlu request server)
const loadMore = () => {
    if (hasMoreItems.value) {
        // Simulasi loading sebentar biar terasa natural (opsional)
        setTimeout(() => {
            displayLimit.value += itemsPerPage;
        }, 300);
    }
};

// Setup Observer
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

// --- LOGIC CART LAINNYA (SAMA SEPERTI SEBELUMNYA) ---
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

const removeFromCart = (variantId) => {
    router.delete(`/cart/${variantId}`, {
        preserveScroll: true, // PENTING: Agar tidak scroll ke atas saat hapus
    });
    selectedItems.value.delete(variantId);
};

const updateQuantity = (variantId, quantity) => {
    if (quantity < 1) return;
    router.patch(`/cart/${variantId}`, { quantity }, { preserveScroll: true });
};

const checkout = () => {
    if (selectedItemsCount.value === 0) {
        alert('Pilih minimal 1 produk untuk checkout');
        return;
    }
    
    // Logic Checkout (Kirim ID dan Qty)
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
    <Head title="Keranjang" />

    <div class="min-h-screen bg-gradient-to-br from-rose-50 to-pink-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-8">
                <h1 class="text-3xl font-light text-gray-900 mb-2">Keranjang Belanja</h1>
                <p class="text-gray-600">{{ cartItemsCount }} item di keranjang Anda</p>
            </div>

            <div v-if="cartItemsCount === 0" class="bg-white rounded-2xl shadow border border-gray-100 p-12 text-center">
                <div class="text-6xl mb-4">🛒</div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Keranjang Kosong</h3>
                <p class="text-gray-600 mb-6">Belum ada produk di keranjang Anda</p>
                <button @click="router.get('/catalog')" class="inline-flex items-center px-6 py-3 bg-rose-600 text-white font-semibold rounded-lg hover:bg-rose-700 transition-colors">
                    Mulai Belanja
                </button>
            </div>

            <div v-else class="space-y-6">
                <div class="bg-white rounded-2xl shadow border border-gray-100 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" :checked="isAllSelected" @change="toggleSelectAll" class="hidden" />
                                <div class="w-6 h-6 border-2 border-rose-300 rounded-lg flex items-center justify-center transition-all"
                                    :class="{ 'bg-rose-500 border-rose-500': isAllSelected, 'hover:border-rose-400': !isAllSelected }">
                                    <svg v-if="isAllSelected" class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <span class="ml-3 text-sm font-medium text-gray-700">Pilih Semua ({{ selectedItemsCount }}/{{ cartItemsCount }})</span>
                            </label>
                        </div>
                        <div class="text-sm text-gray-600">Subtotal: {{ formatCurrency(cartTotal) }}</div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
                    <div class="divide-y divide-gray-100">
                        <div 
                            v-for="(item, variantId) in visibleCart" 
                            :key="variantId" 
                            class="flex items-center gap-4 p-6 transition-colors"
                            :class="{ 'bg-rose-50': isSelected(variantId), 'hover:bg-gray-50': !isSelected(variantId) }"
                        >
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" :checked="isSelected(variantId)" @change="toggleSelectItem(variantId)" class="hidden" />
                                <div class="w-5 h-5 border-2 border-rose-300 rounded flex items-center justify-center transition-all"
                                    :class="{ 'bg-rose-500 border-rose-500': isSelected(variantId), 'hover:border-rose-400': !isSelected(variantId) }">
                                    <svg v-if="isSelected(variantId)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </label>

                            <img :src="item.image_url || '/images/default-product.png'" :alt="item.name" class="w-20 h-20 rounded-lg object-cover" />
                            
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900 text-lg">{{ item.name }}</h3>
                                <p class="text-rose-600 font-medium mt-1">{{ formatCurrency(item.price) }}</p>
                            </div>

                            <div class="flex items-center gap-3">
                                <button @click="updateQuantity(variantId, item.quantity - 1)" class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">-</button>
                                <span class="w-12 text-center font-medium">{{ item.quantity }}</span>
                                <button @click="updateQuantity(variantId, item.quantity + 1)" class="w-8 h-8 flex items-center justify-center border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">+</button>
                            </div>

                            <div class="text-right min-w-24">
                                <p class="font-semibold text-gray-900 text-lg">{{ formatCurrency(item.price * item.quantity) }}</p>
                            </div>

                            <button @click="removeFromCart(variantId)" class="p-2 text-gray-400 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50" title="Hapus">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="hasMoreItems" ref="observerTarget" class="py-6 text-center">
                     <div class="inline-flex items-center gap-2 text-rose-600">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium">Memuat produk lainnya...</span>
                     </div>
                </div>

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