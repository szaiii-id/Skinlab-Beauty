<script setup>
import { Head, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import DashboardLayout from '@/layouts/DashboardLayout.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    orders: Array,
    currentStatus: String
});

// --- FORMATTER ---
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0
    }).format(amount);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });
};

// --- STATUS TABS ---
const tabs = [
    { id: 'all', label: 'Semua' },
    { id: 'pending', label: 'Belum Bayar' },
    { id: 'paid', label: 'Diproses' },
    { id: 'shipped', label: 'Dikirim' },
    { id: 'completed', label: 'Selesai' },
    { id: 'canceled', label: 'Dibatalkan' }
];

const filterStatus = (status) => {
    router.get('/orders', { status }, { preserveState: true });
};

// --- STATUS BADGE ---
const getStatusClass = (status) => {
    switch(status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'paid': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'shipped': return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'completed': return 'bg-green-100 text-green-800 border-green-200';
        case 'canceled': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
};

const getStatusLabel = (status) => {
    switch(status) {
        case 'pending': return 'Menunggu Pembayaran';
        case 'paid': return 'Sedang Diproses';
        case 'shipped': return 'Sedang Dikirim';
        case 'completed': return 'Selesai';
        case 'canceled': return 'Dibatalkan';
        default: return status;
    }
};

// --- FITUR TRACKING (LACAK PAKET) ---
const showTrackingModal = ref(false);
const trackingData = ref(null);
const isLoadingTrack = ref(false);
const trackingError = ref('');

const openTracking = async (order) => {
    if (!order.shipping_tracking_number) return;
    
    showTrackingModal.value = true;
    isLoadingTrack.value = true;
    trackingData.value = null;
    trackingError.value = '';

    try {
        const response = await axios.get(`/api/orders/${order.id}/track`);
        trackingData.value = response.data.data;
    } catch (error) {
        trackingError.value = error.response?.data?.message || "Gagal melacak paket.";
    } finally {
        isLoadingTrack.value = false;
    }
};

// --- BAYAR ULANG (Untuk Pending) ---
const payNow = (snapToken) => {
    if (window.snap && snapToken) {
        window.snap.pay(snapToken, {
            onSuccess: () => router.reload(),
            onPending: () => router.reload(),
            onError: () => alert("Pembayaran Gagal")
        });
    }
};
</script>

<template>
    <Head title="Pesanan Saya" />

    <div class="min-h-screen bg-gradient-to-br from-rose-50 to-pink-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-8">
                <h1 class="text-3xl font-light text-gray-900 mb-2">Pesanan Saya</h1>
                <p class="text-gray-600">Riwayat belanja Anda di SkinLabBeauty</p>
            </div>

            <div class="bg-white rounded-2xl shadow border border-gray-100 p-2 mb-6 overflow-x-auto">
                <div class="flex space-x-2 min-w-max">
                    <button 
                        v-for="tab in tabs" 
                        :key="tab.id"
                        @click="filterStatus(tab.id)"
                        class="px-6 py-2 rounded-xl text-sm font-medium transition-all duration-200"
                        :class="currentStatus === tab.id 
                            ? 'bg-rose-600 text-white shadow-md shadow-rose-200' 
                            : 'text-gray-600 hover:bg-rose-50'"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <div v-if="orders.length === 0" class="bg-white rounded-2xl shadow border border-gray-100 p-12 text-center">
                <div class="text-6xl mb-4">📦</div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Belum ada pesanan</h3>
                <p class="text-gray-600 mb-6">Yuk mulai belanja produk favoritmu!</p>
                <Link href="/" class="inline-flex items-center px-6 py-3 bg-rose-600 text-white font-semibold rounded-lg hover:bg-rose-700 transition-colors">
                    Mulai Belanja
                </Link>
            </div>

            <div v-else class="space-y-6">
                <div v-for="order in orders" :key="order.id" class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden">
                    
                    <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50">
                        <div>
                            <div class="flex items-center gap-3 mb-1">
                                <span class="font-mono font-bold text-gray-900 text-lg">{{ order.order_number }}</span>
                                <span class="text-gray-400">|</span>
                                <span class="text-sm text-gray-500">{{ formatDate(order.created_at) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full text-xs font-bold border uppercase tracking-wide" :class="getStatusClass(order.order_status)">
                                    {{ getStatusLabel(order.order_status) }}
                                </span>
                                <span v-if="order.shipping_tracking_number" class="text-sm font-mono text-gray-600 bg-gray-200 px-2 py-1 rounded">
                                    Resi: {{ order.shipping_tracking_number }}
                                </span>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button v-if="order.order_status === 'pending' && order.snap_token" 
                                @click="payNow(order.snap_token)"
                                class="px-4 py-2 bg-rose-600 text-white rounded-lg hover:bg-rose-700 text-sm font-bold shadow-lg shadow-rose-200">
                                Bayar Sekarang
                            </button>

                            <button v-if="['shipped', 'completed'].includes(order.order_status) && order.shipping_tracking_number" 
                                @click="openTracking(order)"
                                class="px-4 py-2 bg-white border border-rose-600 text-rose-600 rounded-lg hover:bg-rose-50 text-sm font-bold flex items-center gap-2">
                                🚚 Lacak Paket
                            </button>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4">
                            <div v-for="item in order.items" :key="item.id" class="flex items-start gap-4">
                                <img :src="item.product_variant?.product?.image_url || '/images/default-product.png'" class="w-16 h-16 rounded-lg bg-gray-100 object-cover border border-gray-200" />
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900">{{ item.product_name }}</h4>
                                    <p class="text-sm text-gray-500">Qty: {{ item.quantity }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold text-gray-900">{{ formatCurrency(item.price) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            Kurir: <span class="font-bold uppercase">{{ order.shipping_courier || 'Reguler' }}</span>
                        </div>
                        <div class="text-right">
                            <span class="text-sm text-gray-500 mr-2">Total Pesanan</span>
                            <span class="text-xl font-bold text-rose-600">{{ formatCurrency(order.total_amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showTrackingModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showTrackingModal = false">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden animate-fade-in-up">
                <div class="bg-rose-600 px-6 py-4 flex justify-between items-center text-white">
                    <h3 class="font-bold text-lg flex items-center gap-2">
                        <span>🚚</span> Lacak Paket
                    </h3>
                    <button @click="showTrackingModal = false" class="hover:bg-rose-700 rounded-full p-1">✕</button>
                </div>

                <div class="p-6 max-h-[70vh] overflow-y-auto">
                    <div v-if="isLoadingTrack" class="py-12 text-center">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-rose-600 mx-auto mb-3"></div>
                        <p class="text-gray-500">Menghubungi Ekspedisi...</p>
                    </div>

                    <div v-else-if="trackingError" class="p-4 bg-red-50 text-red-700 rounded-xl border border-red-100 text-center">
                        <p class="font-bold mb-1">Gagal Melacak</p>
                        <p class="text-sm">{{ trackingError }}</p>
                    </div>

                    <div v-else-if="trackingData" class="space-y-6">
                        <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 text-center">
                            <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Nomor Resi</p>
                            <p class="font-mono text-2xl font-bold text-gray-900">{{ trackingData.airway_bill }}</p>
                        </div>

                        <div class="text-center">
                            <div class="inline-block px-4 py-1 rounded-full bg-blue-100 text-blue-800 font-bold text-sm border border-blue-200 mb-2">
                                STATUS TERKINI
                            </div>
                            <p class="text-lg font-bold text-gray-800">{{ trackingData.last_status }}</p>
                        </div>

                        <div class="relative pl-4 border-l-2 border-gray-200 space-y-6">
                            <div v-for="(log, index) in trackingData.history" :key="index" class="relative">
                                <div class="absolute -left-[21px] top-1 w-4 h-4 rounded-full border-2 border-white"
                                    :class="index === 0 ? 'bg-green-500 ring-4 ring-green-100' : 'bg-gray-300'">
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 font-mono">{{ log.date }}</p>
                                    <p class="font-medium text-gray-800 mt-0.5">{{ log.desc }}</p>
                                    <span class="text-[10px] font-bold px-2 py-0.5 bg-gray-100 text-gray-600 rounded uppercase tracking-wide">
                                        {{ log.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out forwards; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
</style>