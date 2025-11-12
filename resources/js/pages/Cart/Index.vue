<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useFormatting } from '@/composables/useFormatting';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import { X, Plus, Minus, Trash2 } from 'lucide-vue-next';
import ConfirmationModal from '@/components/ConfirmationModal.vue'; // <-- Ini sudah benar

defineOptions({
    layout: AppNavbarLayout
});

const props = defineProps({
    cart: Object
});

const { formatCurrency } = useFormatting();

const selectedItems = ref(Object.keys(props.cart)); 

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

// --- FUNGSI INTERAKTIF ---

let updateTimeout = null;
const updateQuantity = (variantId, quantity) => {
    if (quantity < 1) return; 
    props.cart[variantId].quantity = quantity;
    if (updateTimeout) clearTimeout(updateTimeout);
    updateTimeout = setTimeout(() => {
        router.patch(`/cart/${variantId}`, { quantity: quantity }, { preserveScroll: true });
    }, 300);
};

// --- LOGIKA MODAL KONFIRMASI (DARI KODE ANDA) ---
const isModalOpen = ref(false);
const itemToRemove = ref(null); 

const openRemoveModal = (variantId) => {
    itemToRemove.value = variantId; 
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    itemToRemove.value = null;
};

const confirmRemove = () => {
    if (itemToRemove.value) {
        const variantId = itemToRemove.value;
        selectedItems.value = selectedItems.value.filter(id => id !== variantId);
        
        router.delete(`/cart/${variantId}`, {
            preserveScroll: true,
            onSuccess: () => {
                closeModal(); // Tutup modal setelah sukses
            }
        });
    }
};

const handleCheckout = () => {
    if (selectedItems.value.length === 0) {
        alert('Please select at least one item to checkout.');
        return;
    }
    alert('Proceeding to checkout with ' + selectedItems.value.length + ' items.');
};

</script>

<template>
    <Head title="Shopping Cart" />

    <div class="bg-rose-50 min-h-screen py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Judul Halaman -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-light text-gray-900 tracking-tight">
                    Shopping Cart
                </h1>
            </div>

            <!-- Jika Keranjang Kosong -->
            <div v-if="isCartEmpty" class="bg-white rounded-lg shadow-xl p-12 text-center">
                <p class="text-gray-600 text-lg">Your cart is currently empty.</p>
                <Link 
                    href="/catalog" 
                    class="inline-block mt-8 px-6 py-3 bg-rose-600 text-white text-sm font-semibold rounded-full shadow-lg hover:bg-rose-700 transition-colors"
                >
                    &larr; Continue Shopping
                </Link>
            </div>

            <!-- Jika Ada Isi (Layout 2 Kolom) -->
            <div v-else class="flex flex-col lg:flex-row gap-8">
                
                <!-- KOLOM KIRI: Daftar Item -->
                <div class="w-full lg:w-2/3">
                    <div class="bg-white rounded-lg shadow-xl overflow-hidden divide-y divide-gray-200">
                        
                        <!-- Loop untuk setiap item di keranjang -->
                        <div v-for="(item, variantId) in cart" :key="variantId" class="p-6 flex space-x-4">
                            
                            <!-- Gambar Item -->
                            <div class="flex-shrink-0">
                                <img 
                                    :src="item.image_url || '/images/default-product.png'" 
                                    :alt="item.name"
                                    class="w-24 h-24 rounded-md object-cover"
                                />
                            </div>
                            
                            <!-- Detail Item (Nama, Harga, Stepper) -->
                            <div class="flex-1 flex flex-col justify-between">
                                <div>
                                    <Link 
                                        :href="`/products/${item.product_slug}/${item.product_id}`"
                                        class="text-lg font-medium text-gray-900 hover:text-rose-600"
                                    >
                                        {{ item.name }}
                                    </Link>
                                    <p class="text-sm text-gray-600 mt-1">
                                        {{ formatCurrency(item.price) }}
                                    </p>
                                </div>
                                <!-- Quantity Stepper -->
                                <div class="flex items-center space-x-2 mt-2">
                                    <button
                                        @click="updateQuantity(variantId, item.quantity - 1)"
                                        :disabled="item.quantity <= 1"
                                        class="w-7 h-7 flex items-center justify-center border border-gray-300 text-gray-700 rounded-full hover:bg-gray-100 transition-colors"
                                    >
                                        <Minus class="w-4 h-4" />
                                    </button>
                                    <input 
                                        type="text" 
                                        :value="item.quantity"
                                        readonly
                                        class="w-10 h-8 text-center border-gray-300 text-gray-900 rounded-md focus:outline-none bg-gray-50"
                                    />
                                    <button
                                        @click="updateQuantity(variantId, item.quantity + 1)"
                                        class="w-7 h-7 flex items-center justify-center border border-gray-300 text-gray-700 rounded-full hover:bg-gray-100 transition-colors"
                                    >
                                        <Plus class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <!-- Harga Total Item & Tombol Hapus -->
                            <div class="text-right flex flex-col justify-between items-end">
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ formatCurrency(item.price * item.quantity) }}
                                </p>
                                <button
                                    @click="openRemoveModal(variantId)"
                                    class="text-sm text-red-600 hover:text-red-700 flex items-center space-x-1 px-2 py-1 rounded-md hover:bg-red-50 transition-colors"
                                    title="Remove item"
                                >
                                    <Trash2 class="w-4 h-4" /> <span>Remove</span>
                                </button>
                            </div>

                            <!-- Checkbox (di Kanan) -->
                            <div class="flex items-center pl-4">
                                <input 
                                    type="checkbox"
                                    :id="`item-${variantId}`"
                                    :value="variantId"
                                    v-model="selectedItems"
                                    class="h-5 w-5 text-rose-600 border-gray-300 rounded focus:ring-rose-500"
                                />
                            </div>

                        </div>
                    </div>
                </div>

                <!-- KOLOM KANAN: Ringkasan Pesanan -->
                <div class="w-full lg:w-1/3">
                    <div class="bg-white rounded-lg shadow-xl p-6 sticky top-24">
                        <h2 class="text-2xl font-semibold text-gray-900 border-b pb-4">
                            Order Summary
                        </h2>
                        
                        <div classV="space-y-4 mt-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="text-gray-900 font-medium">{{ formatCurrency(cartTotal) }}</span>
                            </div>
                            
                            <div class="border-t pt-4 flex justify-between items-center">
                                <span class="text-lg font-bold text-gray-900">Total</span>
                                <span class="text-2xl font-bold text-gray-900">
                                    {{ formatCurrency(cartTotal) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 text-center pt-2">
                                Shipping and taxes calculated at checkout.
                            </p>
                        </div>
                        
                        <button 
                            @click="handleCheckout"
                            class="w-full bg-rose-600 text-white p-3 rounded-md mt-6 font-semibold
                                   transition-colors duration-300
                                   hover:bg-rose-700 disabled:opacity-50"
                            :disabled="selectedItems.length === 0"
                        >
                            Proceed to Checkout ({{ selectedItems.length }} items)
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- PANGGIL KOMPONEN MODAL DI SINI -->
    <ConfirmationModal 
        :show="isModalOpen" 
        @close="closeModal"
        @confirm="confirmRemove"
    />
</template>