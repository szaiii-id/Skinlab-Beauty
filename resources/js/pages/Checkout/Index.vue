<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { ArrowLeft } from 'lucide-vue-next'; 
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import AddressManager from '@/components/AddressManager.vue';
import PaymentMethodSelector from '@/components/PaymentMethodSelector.vue';
import OrderSummary from '@/components/OrderSummary.vue'; 
import ShippingSelection from '@/components/ShippingSelection.vue'; 
import Swal from 'sweetalert2';

defineOptions({ layout: AppNavbarLayout });

const props = defineProps({
    items: { type: Array, default: () => [] },
    subtotal: { type: Number, default: 0 },
    user_address: { type: Object, default: null }, 
    midtrans_client_key: { type: String, default: '' },
    available_vouchers: { type: Array, default: () => [] } 
});

const page = usePage();

// --- STATE ---
const selectedAddress = ref(null);
const rawShippingRates = ref([]); 
const selectedService = ref(null); 
const isLoadingShipping = ref(false);
const apiError = ref('');
const selectedVoucher = ref(null);

// Form Inertia
const form = useForm({
    items: props.items.map(item => ({ variant_id: item.variant_id, quantity: item.quantity })),
    shipping_address_id: '',
    shipping_cost: 0,
    shipping_courier: '',
    payment_method: 'online_payment',
    voucher_code: '',
    notes: ''
});

// --- METHODS ---
const goBack = () => {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit('/'); 
    }
};

// 1. Voucher Actions
const applyVoucher = (voucher) => {
    selectedVoucher.value = voucher;
    form.voucher_code = voucher.code;
};

const removeVoucher = () => {
    selectedVoucher.value = null;
    form.voucher_code = '';
};

// 2. Shipping Actions
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
        
        const cleanRates = response.data.rates
            .filter(r => r.price < 1000000)
            .sort((a, b) => a.price - b.price);
            
        rawShippingRates.value = cleanRates;
        
        if (cleanRates.length > 0) {
            selectService(cleanRates[0]);
        }
    } catch (error) {
        apiError.value = error.response?.data?.message || "Failed to load shipping rates.";
    } finally {
        isLoadingShipping.value = false;
    }
};

const selectService = (service) => {
    selectedService.value = service;
    form.shipping_cost = service.price;
    form.shipping_courier = `${service.courier_name} - ${service.service_type}`;
};

const handleAddressSelected = (address) => {
    selectedAddress.value = address;
    form.shipping_address_id = address.id;
    fetchShippingRates(address.id);
};

// 3. Submit Order
const submitOrder = () => {
    if (!selectedAddress.value) { 
        Swal.fire('Address Missing', 'Please select a shipping address.', 'warning');
        return; 
    }
    if (!selectedService.value) { 
        Swal.fire('Courier Missing', 'Please select a shipping courier.', 'warning');
        return; 
    }

    form.post('/checkout', {
        preserveScroll: true,
        onSuccess: () => {
            if (form.payment_method === 'online_payment') {
                const snapToken = page.props.flash?.snap_token;
                if (snapToken) {
                    window.snap.pay(snapToken, {
                        onSuccess: () => router.visit('/checkout/success'),
                        onPending: () => router.visit('/checkout/success'),
                        onError: () => Swal.fire('Payment Failed', 'Transaction could not be completed.', 'error'),
                        onClose: () => console.log('Popup closed')
                    });
                }
            } else {
                router.visit('/checkout/success');
            }
        },
        onError: (errors) => {
            if (errors.shipping_address_id) Swal.fire('Error', 'Invalid Shipping Address', 'error');
        }
    });
};

onMounted(() => {
    const script = document.createElement('script');
    script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
    script.setAttribute('data-client-key', props.midtrans_client_key);
    document.head.appendChild(script);
    
    if (props.user_address) handleAddressSelected(props.user_address);
});
</script>

<template>
    <Head title="Checkout - SkinLab Beauty" />
    
    <div class="min-h-screen bg-rose-50 py-4 md:py-8 text-gray-900 pb-32 lg:pb-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-4 md:mb-6">
                <button 
                    @click="goBack" 
                    class="group inline-flex items-center gap-2 text-sm font-medium text-gray-500 hover:text-rose-600 transition-all duration-200"
                >
                    <div class="p-1 rounded-full group-hover:bg-rose-100 transition-colors bg-white shadow-sm border border-gray-100">
                        <ArrowLeft class="w-4 h-4" />
                    </div>
                    <span>Back</span>
                </button>
            </div>
            
            <div class="mb-6 md:mb-8">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-1">Checkout</h1>
                <p class="text-sm md:text-base text-gray-600">Complete your purchase securely.</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
                
                <div class="lg:col-span-2 space-y-4 md:space-y-6">
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                        <h2 class="text-base md:text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-bold">1</span>
                            Shipping Address
                        </h2>
                        <AddressManager @address-selected="handleAddressSelected" />
                    </div>

                    <ShippingSelection 
                        :rates="rawShippingRates"
                        :is-loading="isLoadingShipping"
                        :error="apiError"
                        :selected-service="selectedService"
                        :has-address="!!selectedAddress"
                        @select-service="selectService"
                    />

                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                        <h2 class="text-base md:text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-bold">2</span>
                            Payment Method
                        </h2>
                        <PaymentMethodSelector v-model="form.payment_method" />
                    </div>
                    
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                        <h2 class="text-base md:text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-bold">3</span>
                            Notes <span class="text-gray-400 font-normal text-xs">(Optional)</span>
                        </h2>
                        <textarea 
                            v-model="form.notes" 
                            placeholder="Example: Please pack carefully..." 
                            class="w-full px-4 py-3 border border-gray-200 bg-gray-50 rounded-xl focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 text-gray-900 text-sm transition-all resize-none" 
                            rows="2"
                        ></textarea>
                    </div>
                </div>

                <div class="space-y-6">
                    <OrderSummary 
                        :items="items"
                        :subtotal="subtotal"
                        :shipping-cost="form.shipping_cost"
                        :available-vouchers="available_vouchers"
                        :selected-voucher="selectedVoucher"
                        :is-processing="form.processing"
                        :can-submit="!!(selectedAddress && selectedService)"
                        @apply-voucher="applyVoucher"
                        @remove-voucher="removeVoucher"
                        @submit-order="submitOrder"
                    />
                </div>
            </div>
        </div>
    </div>
</template>