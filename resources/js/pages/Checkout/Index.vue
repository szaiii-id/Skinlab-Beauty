<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import AddressManager from '@/components/AddressManager.vue';
import PaymentMethodSelector from '@/components/PaymentMethodSelector.vue';

defineOptions({ layout: AppNavbarLayout });

const props = defineProps({
    items: { type: Array, default: () => [] },
    subtotal: { type: Number, default: 0 },
    shipping_fee: { type: Number, default: 0 },
    total: { type: Number, default: 0 },
    isDirectPurchase: { type: Boolean, default: false },
    user_address: { type: Object, default: null },
    midtrans_client_key: { type: String, default: '' } // Terima Key dari Controller
});

const page = usePage();

const formatCurrency = (amount) => {
    if (!amount) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0
    }).format(amount);
};

const selectedAddress = ref(null);
const selectedShipping = ref('standard');
const selectedPayment = ref('online_payment');
const voucherCode = ref('');

const form = useForm({
    items: props.items.map(item => ({ variant_id: item.variant_id, quantity: item.quantity })),
    shipping_address_id: '',
    shipping_method: selectedShipping,
    payment_method: selectedPayment,
    voucher_code: voucherCode,
    notes: ''
});

const handleAddressSelected = (address) => {
    selectedAddress.value = address;
    form.shipping_address_id = address.id;
};

// ✅ ON MOUNTED: LOAD SCRIPT SNAP
onMounted(() => {
    const script = document.createElement('script');
    
    // ⚠️ PERHATIKAN URL INI:
    // Gunakan 'https://app.sandbox.midtrans.com/snap/snap.js' untuk SANDBOX
    // Gunakan 'https://app.midtrans.com/snap/snap.js' untuk PRODUCTION (jika kunci Anda Production)
    script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
    
    script.setAttribute('data-client-key', props.midtrans_client_key);
    document.head.appendChild(script);

    if (props.user_address) {
        handleAddressSelected(props.user_address);
    }
});

const shippingOptions = [
    { id: 'standard', name: 'Standard Delivery', price: 15000, estimate: '3-5 days' },
    { id: 'express', name: 'Express Delivery', price: 30000, estimate: '1-2 days' },
];

const currentShipping = computed(() => {
    return shippingOptions.find(option => option.id === selectedShipping.value) || shippingOptions[0];
});

const finalTotal = computed(() => {
    return props.subtotal + currentShipping.value.price;
});

const submitOrder = () => {
    if (!selectedAddress.value) {
        alert('Silakan pilih alamat pengiriman terlebih dahulu'); return;
    }

    form.shipping_method = selectedShipping.value;
    form.payment_method = selectedPayment.value;
    form.voucher_code = voucherCode.value;

    form.post('/checkout', {
        preserveScroll: true,
        onSuccess: () => {
            if (form.payment_method === 'online_payment') {
                // ✅ TANGKAP TOKEN DARI FLASH
                const snapToken = page.props.flash?.snap_token;
                
                if (snapToken) {
                    // ✅ BUKA POPUP
                    window.snap.pay(snapToken, {
                        onSuccess: function(result) { router.visit('/checkout/success'); },
                        onPending: function(result) { router.visit('/checkout/success'); },
                        onError: function(result) { alert("Pembayaran Gagal!"); },
                        onClose: function() { console.log('Popup ditutup'); }
                    });
                } else {
                    alert("Token tidak diterima. Refresh halaman.");
                }
            }
        },
        onError: (errors) => {
            if (errors.shipping_address_id) alert('Pilih alamat yang valid');
        }
    });
};
</script>

<template>
    <Head title="Checkout - SkinLab Beauty" />
    <div class="min-h-screen bg-rose-50 py-8 text-black">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h1 class="text-3xl font-light text-black mb-2">Checkout</h1>
                <p class="text-gray-800">Selesaikan pesanan Anda</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-black mb-4">Alamat Pengiriman</h2>
                        <AddressManager @address-selected="handleAddressSelected" />
                        <div v-if="selectedAddress" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex justify-between">
                                <div>
                                    <p class="text-green-800 font-bold">{{ selectedAddress.receiver_name }} ({{ selectedAddress.phone_number }})</p>
                                    <p class="text-green-900 text-sm">
                                        {{ selectedAddress.full_address }}
                                        <span v-if="selectedAddress.district">, {{ selectedAddress.district.name }}</span>
                                        <span v-if="selectedAddress.city">, {{ selectedAddress.city.name }}</span>
                                        <span v-if="selectedAddress.province">, {{ selectedAddress.province.name }}</span>
                                    </p>
                                </div>
                                <button @click="selectedAddress = null; form.shipping_address_id = ''" class="text-red-600 font-bold px-2">✕</button>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-black mb-4">Metode Pengiriman</h2>
                        <div class="space-y-3">
                            <div v-for="option in shippingOptions" :key="option.id"
                                class="flex items-center justify-between p-4 border rounded-lg cursor-pointer"
                                :class="selectedShipping === option.id ? 'border-rose-500 bg-rose-50 ring-1 ring-rose-500' : 'border-gray-200'"
                                @click="selectedShipping = option.id">
                                <div>
                                    <p class="font-bold text-black">{{ option.name }}</p>
                                    <p class="text-sm text-gray-700">{{ option.estimate }}</p>
                                </div>
                                <p class="font-bold text-black">{{ formatCurrency(option.price) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-black mb-4">Metode Pembayaran</h2>
                        <PaymentMethodSelector v-model="selectedPayment" />
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-black mb-4">Catatan (Opsional)</h2>
                        <textarea v-model="form.notes" placeholder="Catatan tambahan..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-rose-500 focus:border-rose-500 text-black" rows="2"></textarea>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-6">
                        <h2 class="text-lg font-semibold text-black mb-4">Ringkasan</h2>
                        <div class="space-y-4 max-h-80 overflow-y-auto pr-2">
                            <div v-for="item in items" :key="item.variant_id" class="flex items-center space-x-3">
                                <img :src="item.image_url || '/images/default-product.png'" class="w-12 h-12 rounded-lg object-cover bg-gray-100" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-bold text-black truncate">{{ item.name }}</p>
                                    <p class="text-xs text-gray-700">Qty: {{ item.quantity }}</p>
                                </div>
                                <p class="text-sm font-bold text-black">{{ formatCurrency(item.price * item.quantity) }}</p>
                            </div>
                        </div>
                        <div class="border-t border-gray-200 mt-4 pt-4 space-y-2">
                            <div class="flex justify-between text-sm"><span class="text-gray-800">Subtotal</span><span class="font-bold text-black">{{ formatCurrency(subtotal) }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-gray-800">Ongkos Kirim</span><span class="font-bold text-black">{{ formatCurrency(currentShipping.price) }}</span></div>
                            <div class="flex justify-between items-center border-t border-gray-200 pt-4 mt-2">
                                <span class="font-bold text-black text-lg">Total</span>
                                <span class="text-xl font-bold text-rose-600">{{ formatCurrency(finalTotal) }}</span>
                            </div>
                        </div>
                        <div class="mt-6">
                            <button @click="submitOrder" :disabled="form.processing || !selectedAddress"
                                class="w-full py-4 bg-rose-600 text-black font-bold rounded-lg hover:bg-rose-500 disabled:bg-gray-300">
                                <span v-if="form.processing">Memproses...</span>
                                <span v-else-if="!selectedAddress">Pilih Alamat Dulu</span>
                                <span v-else>{{ selectedPayment === 'cod' ? 'Pesan (COD)' : 'Bayar Sekarang' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>