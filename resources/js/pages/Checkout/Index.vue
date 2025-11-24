<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import axios from 'axios';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import AddressManager from '@/components/AddressManager.vue';
import PaymentMethodSelector from '@/components/PaymentMethodSelector.vue';

defineOptions({ layout: AppNavbarLayout });

const props = defineProps({
    items: { type: Array, default: () => [] },
    subtotal: { type: Number, default: 0 },
    shipping_fee: { type: Number, default: 0 },
    total: { type: Number, default: 0 },
    user_address: { type: Object, default: null }, // Alamat default
    midtrans_client_key: { type: String, default: '' }
});

const page = usePage();

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0
    }).format(amount);
};

// --- STATE ---
const selectedAddress = ref(null);
const rawShippingRates = ref([]); 
const selectedCourier = ref(null); 
const selectedService = ref(null); 
const isLoadingShipping = ref(false);
const apiError = ref('');

const selectedPayment = ref('online_payment');
const voucherCode = ref('');

const form = useForm({
    items: props.items.map(item => ({ variant_id: item.variant_id, quantity: item.quantity })),
    shipping_address_id: '',
    shipping_cost: 0,
    shipping_courier: '',
    payment_method: selectedPayment,
    voucher_code: voucherCode,
    notes: ''
});

// --- GROUPING KURIR ---
const groupedRates = computed(() => {
    const groups = {};
    // Sort termurah dulu
    const sortedRates = [...rawShippingRates.value].sort((a, b) => a.price - b.price);

    sortedRates.forEach(rate => {
        const name = rate.courier_name;
        if (!groups[name]) {
            groups[name] = { name: name, services: [] };
        }
        groups[name].services.push(rate);
    });
    return groups;
});

// --- LOGIC ONGKIR ---
const fetchShippingRates = async (addressId) => {
    if (!addressId) return;

    isLoadingShipping.value = true;
    rawShippingRates.value = [];
    selectedService.value = null;
    apiError.value = '';

    try {
        const response = await axios.post('/api/shipping/check-rates', {
            address_id: addressId,
            items: props.items.map(item => ({ variant_id: item.variant_id, quantity: item.quantity }))
        });

        // Filter harga > 1 Juta & Sort
        const cleanRates = response.data.rates
            .filter(r => r.price < 1000000) 
            .sort((a, b) => a.price - b.price);

        rawShippingRates.value = cleanRates;

        // Auto Select Termurah
        if (cleanRates.length > 0) {
            const cheapest = cleanRates[0];
            selectService(cheapest);
            selectedCourier.value = cheapest.courier_name; 
        }

    } catch (error) {
        console.error("Ongkir Error:", error);
        apiError.value = error.response?.data?.message || "Gagal memuat ongkir.";
    } finally {
        isLoadingShipping.value = false;
    }
};

const selectService = (service) => {
    selectedService.value = service;
    form.shipping_cost = service.price;
    form.shipping_courier = `${service.courier_name} - ${service.service_type}`;
};

// --- HANDLE ADDRESS ---
const handleAddressSelected = (address) => {
    // Logic tetap jalan: simpan address ke state & cari ongkir
    selectedAddress.value = address;
    form.shipping_address_id = address.id;
    
    // Langsung cari ongkir
    fetchShippingRates(address.id);
};

// --- INIT ---
onMounted(() => {
    const script = document.createElement('script');
    script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
    script.setAttribute('data-client-key', props.midtrans_client_key);
    document.head.appendChild(script);

    // Auto Select Default Address (Logic berjalan, tapi UI tidak double)
    if (props.user_address) {
        handleAddressSelected(props.user_address);
    }
});

const finalTotal = computed(() => {
    const shippingPrice = selectedService.value ? selectedService.value.price : 0;
    return props.subtotal + shippingPrice;
});

const submitOrder = () => {
    if (!selectedAddress.value) {
        alert('Silakan pilih alamat pengiriman terlebih dahulu'); return;
    }
    if (!selectedService.value) {
        alert('Silakan pilih kurir pengiriman'); return;
    }

    form.post('/checkout', {
        preserveScroll: true,
        onSuccess: () => {
            if (form.payment_method === 'online_payment') {
                const snapToken = page.props.flash?.snap_token;
                if (snapToken) {
                    window.snap.pay(snapToken, {
                        onSuccess: function(result) { router.visit('/checkout/success'); },
                        onPending: function(result) { router.visit('/checkout/success'); },
                        onError: function(result) { alert("Pembayaran Gagal!"); },
                        onClose: function() { console.log('Popup closed'); }
                    });
                }
            }
        },
        onError: (errors) => {
            if (errors.shipping_address_id) alert('Pilih alamat valid');
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
                        
                        </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-black mb-4">Metode Pengiriman</h2>
                        
                        <div v-if="isLoadingShipping" class="py-6 text-center">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-rose-600 mx-auto mb-2"></div>
                            <p class="text-gray-500 text-sm">Mencari opsi pengiriman...</p>
                        </div>

                        <div v-else-if="apiError" class="p-3 bg-red-50 text-red-600 text-sm rounded border border-red-100">
                            {{ apiError }}
                        </div>

                        <div v-else-if="!selectedAddress" class="text-gray-400 text-center py-4 border border-dashed rounded">
                            Pilih alamat di atas untuk melihat ongkir.
                        </div>

                        <div v-else class="space-y-3">
                            <div v-for="(group, name) in groupedRates" :key="name" class="border rounded-lg overflow-hidden">
                                <div 
                                    class="p-3 bg-gray-50 flex justify-between items-center cursor-pointer hover:bg-gray-100 transition"
                                    @click="selectedCourier = (selectedCourier === name ? null : name)"
                                >
                                    <span class="font-bold text-gray-800 flex items-center gap-2">
                                        🚚 {{ name }}
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-green-600">
                                            Mulai {{ formatCurrency(group.services[0].price) }}
                                        </span>
                                        <svg :class="{'rotate-180': selectedCourier === name}" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </div>
                                </div>

                                <div v-if="selectedCourier === name" class="p-2 bg-white space-y-2 border-t">
                                    <div 
                                        v-for="service in group.services" 
                                        :key="service.id"
                                        class="flex justify-between items-center p-3 border rounded cursor-pointer transition-all hover:shadow-sm"
                                        :class="selectedService?.id === service.id ? 'border-rose-500 bg-rose-50 ring-1 ring-rose-500' : 'border-gray-100 hover:border-gray-300'"
                                        @click="selectService(service)"
                                    >
                                        <div>
                                            <div class="font-bold text-sm text-gray-800">{{ service.service_type }}</div>
                                            <div class="text-xs text-gray-500">Estimasi: {{ service.duration }}</div>
                                        </div>
                                        <div class="font-bold text-rose-600">
                                            {{ formatCurrency(service.price) }}
                                        </div>
                                    </div>
                                </div>
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
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-800">Ongkos Kirim</span>
                                <span class="font-bold text-black">
                                    {{ selectedService ? formatCurrency(selectedService.price) : '-' }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center border-t border-gray-200 pt-4 mt-2">
                                <span class="font-bold text-black text-lg">Total Bayar</span>
                                <span class="text-xl font-bold text-rose-600">{{ formatCurrency(finalTotal) }}</span>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button @click="submitOrder" :disabled="form.processing || !selectedService"
                                class="w-full py-4 bg-rose-600 text-black font-bold rounded-lg hover:bg-rose-500 disabled:bg-gray-300 disabled:cursor-not-allowed transition-all shadow-lg shadow-rose-200">
                                <span v-if="form.processing">Memproses...</span>
                                <span v-else-if="!selectedAddress">Pilih Alamat Dulu</span>
                                <span v-else-if="!selectedService">Pilih Kurir Dulu</span>
                                <span v-else>{{ selectedPayment === 'cod' ? 'Pesan (COD)' : 'Bayar Sekarang' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>