<!-- [file name]: pages/Checkout.vue - Update bagian address saja -->
<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
// ✅ IMPORT KOMPONEN BARU
import AddressManager from '@/components/AddressManager.vue';

defineOptions({
    layout: AppNavbarLayout
});

const props = defineProps({
    items: {
        type: Array,
        default: () => []
    },
    subtotal: {
        type: Number,
        default: 0
    },
    shipping_fee: {
        type: Number,
        default: 0
    },
    total: {
        type: Number,
        default: 0
    },
    isDirectPurchase: {
        type: Boolean,
        default: false
    }
});

const formatCurrency = (amount) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
};

// State untuk form
const selectedAddress = ref(null);
const selectedShipping = ref('standard');
const selectedPayment = ref('credit_card');
const voucherCode = ref('');

// Shipping options
const shippingOptions = [
    { id: 'standard', name: 'Standard Delivery', price: 15000, estimate: '3-5 days' },
    { id: 'express', name: 'Express Delivery', price: 30000, estimate: '1-2 days' },
    { id: 'same_day', name: 'Same Day Delivery', price: 50000, estimate: 'Today' }
];

// Payment methods
const paymentMethods = [
    { id: 'credit_card', name: 'Credit Card', icon: '💳' },
    { id: 'bank_transfer', name: 'Bank Transfer', icon: '🏦' },
    { id: 'ewallet', name: 'E-Wallet', icon: '📱' },
    { id: 'cod', name: 'Cash on Delivery', icon: '💰' }
];

// Form untuk checkout
const form = useForm({
    items: props.items.map(item => ({
        variant_id: item.variant_id,
        quantity: item.quantity
    })),
    shipping_address_id: '', // ✅ Update ke shipping_address_id
    shipping_method: selectedShipping,
    payment_method: selectedPayment,
    voucher_code: voucherCode,
    notes: ''
});

// Computed untuk update total ketika shipping berubah
const currentShipping = computed(() => {
    return shippingOptions.find(option => option.id === selectedShipping.value) || shippingOptions[0];
});

const finalTotal = computed(() => {
    return props.subtotal + currentShipping.value.price;
});

// ✅ FUNGSI UNTUK HANDLE ADDRESS YANG DIPILIH
const handleAddressSelected = (address) => {
    selectedAddress.value = address;
    form.shipping_address_id = address.id; // Set ID address ke form checkout
};

const submitOrder = () => {
    // Validasi: pastikan alamat sudah dipilih
    if (!selectedAddress.value) {
        alert('Silakan pilih alamat pengiriman terlebih dahulu');
        return;
    }

    // Update form dengan values terbaru
    form.shipping_method = selectedShipping.value;
    form.payment_method = selectedPayment.value;
    form.voucher_code = voucherCode.value;

    form.post('/checkout', {
        preserveScroll: true,
        onSuccess: () => {
            // Akan di-redirect ke success page oleh controller
        },
        onError: (errors) => {
            if (errors.shipping_address_id) {
                alert('Silakan pilih alamat pengiriman yang valid');
            }
        }
    });
};
</script>

<template>
    <Head title="Checkout - SkinLab Beauty" />

    <div class="min-h-screen bg-rose-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-light text-gray-900 mb-2">Checkout</h1>
                <p class="text-gray-600" v-if="isDirectPurchase">
                    Direct Purchase - Buy Now
                </p>
                <p class="text-gray-600" v-else>
                    Shopping Cart Checkout
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form Section -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- ✅ BAGIAN INI YANG DIGANTI: Shipping Address -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Alamat Pengiriman</h2>
                        </div>
                        
                        <!-- ✅ GUNAKAN KOMPONEN AddressManager -->
                        <AddressManager @address-selected="handleAddressSelected" />
                        
                        <!-- ✅ TAMPILKAN ALAMAT YANG DIPILIH -->
                        <div v-if="selectedAddress" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-green-800 font-medium">Alamat Dipilih:</p>
                                    <p class="text-green-700 text-sm">
                                        {{ selectedAddress.receiver_name }} - 
                                        {{ selectedAddress.phone_number }}
                                    </p>
                                    <p class="text-green-600 text-sm">
                                        {{ selectedAddress.full_address }}, 
                                        {{ selectedAddress.district?.name }}, 
                                        {{ selectedAddress.city?.name }}, 
                                        {{ selectedAddress.province?.name }}
                                    </p>
                                </div>
                                <button 
                                    @click="selectedAddress = null; form.shipping_address_id = ''"
                                    class="text-red-600 hover:text-red-800 text-sm"
                                >
                                    ✕
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Method -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Metode Pengiriman</h2>
                        <div class="space-y-3">
                            <div 
                                v-for="option in shippingOptions" 
                                :key="option.id"
                                class="flex items-center justify-between p-4 border rounded-lg cursor-pointer transition-colors"
                                :class="selectedShipping === option.id ? 'border-rose-500 bg-rose-50' : 'border-gray-200 hover:border-rose-300'"
                                @click="selectedShipping = option.id"
                            >
                                <div class="flex items-center space-x-3">
                                    <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                         :class="selectedShipping === option.id ? 'border-rose-500 bg-rose-500' : 'border-gray-300'">
                                        <div v-if="selectedShipping === option.id" class="w-2 h-2 bg-white rounded-full"></div>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ option.name }}</p>
                                        <p class="text-sm text-gray-500">{{ option.estimate }}</p>
                                    </div>
                                </div>
                                <p class="font-semibold text-gray-900">{{ formatCurrency(option.price) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Metode Pembayaran</h2>
                        <div class="grid grid-cols-2 gap-3">
                            <div 
                                v-for="method in paymentMethods" 
                                :key="method.id"
                                class="flex items-center space-x-3 p-4 border rounded-lg cursor-pointer transition-colors"
                                :class="selectedPayment === method.id ? 'border-rose-500 bg-rose-50' : 'border-gray-200 hover:border-rose-300'"
                                @click="selectedPayment = method.id"
                            >
                                <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center"
                                     :class="selectedPayment === method.id ? 'border-rose-500 bg-rose-500' : 'border-gray-300'">
                                    <div v-if="selectedPayment === method.id" class="w-2 h-2 bg-white rounded-full"></div>
                                </div>
                                <span class="text-2xl">{{ method.icon }}</span>
                                <span class="font-medium text-gray-900">{{ method.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Order Notes -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Catatan Pesanan (Opsional)</h2>
                        <textarea
                            v-model="form.notes"
                            placeholder="Instruksi khusus untuk pesanan Anda..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
                            rows="3"
                        ></textarea>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="space-y-6">
                    <!-- Order Items -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Ringkasan Pesanan</h2>
                        
                        <div class="space-y-4">
                            <div 
                                v-for="item in items" 
                                :key="item.variant_id"
                                class="flex items-center space-x-3"
                            >
                                <img 
                                    :src="item.image_url || '/images/default-product.png'" 
                                    :alt="item.name"
                                    class="w-12 h-12 rounded-lg object-cover"
                                />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ item.name }}</p>
                                    <p class="text-xs text-gray-500">Qty: {{ item.quantity }}</p>
                                </div>
                                <p class="text-sm font-semibold text-gray-900">{{ formatCurrency(item.price * item.quantity) }}</p>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 mt-4 pt-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="text-gray-900">{{ formatCurrency(subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Ongkos Kirim</span>
                                <span class="text-gray-900">{{ formatCurrency(currentShipping.price) }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-semibold border-t border-gray-200 pt-2">
                                <span class="text-gray-900">Total</span>
                                <span class="text-rose-600">{{ formatCurrency(finalTotal) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Voucher Code -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Gunakan Voucher</h2>
                        <div class="flex space-x-2">
                            <input
                                v-model="voucherCode"
                                type="text"
                                placeholder="Masukkan kode voucher"
                                class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
                            />
                            <button class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                                Gunakan
                            </button>
                        </div>
                    </div>

                    <!-- Place Order Button -->
                    <button
                        @click="submitOrder"
                        :disabled="form.processing || !selectedAddress"
                        class="w-full py-4 bg-rose-600 text-white font-semibold rounded-lg hover:bg-rose-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors text-lg"
                    >
                        <span v-if="form.processing">Memproses...</span>
                        <span v-else-if="!selectedAddress">Pilih Alamat Dulu</span>
                        <span v-else>Buat Pesanan - {{ formatCurrency(finalTotal) }}</span>
                    </button>

                    <!-- Security Badge -->
                    <div class="text-center text-xs text-gray-500">
                        <p>🔒 Checkout aman • Terenkripsi SSL</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>