<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { 
    ShoppingBag, Truck, CreditCard, Activity, Star, Clock, 
    ChevronRight, ScanFace, Gift, CheckCircle2, Ticket
} from 'lucide-vue-next';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    auth: Object,
    recentOrders: Array,
    stats: Object, // { points, voucher_count, skin_type, routine_progress, routine_count }
    midtrans_client_key: String
});

// --- HELPERS ---
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0
    }).format(amount);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: 'numeric', month: 'short', year: 'numeric'
    });
};

const getStatusColor = (status) => {
    const colors = {
        'pending': 'bg-amber-100 text-amber-700',
        'paid': 'bg-blue-100 text-blue-700',
        'processing': 'bg-indigo-100 text-indigo-700',
        'shipped': 'bg-purple-100 text-purple-700',
        'completed': 'bg-green-100 text-green-700',
        'canceled': 'bg-red-100 text-red-700'
    };
    return colors[status] || 'bg-gray-100 text-gray-600';
};

// --- LOGIC TRACKING & PAYMENT ---
const showTrackingModal = ref(false);
const trackingData = ref(null);
const isLoadingTrack = ref(false);

const trackPackage = async (order) => {
    if (!order.shipping_tracking_number) return;
    showTrackingModal.value = true;
    isLoadingTrack.value = true;
    trackingData.value = null;
    try {
        const response = await axios.get(`/api/orders/${order.id}/track`);
        trackingData.value = response.data.data;
    } catch (e) {
        trackingData.value = { error: "Gagal melacak paket." };
    } finally {
        isLoadingTrack.value = false;
    }
};

const payNow = (snapToken) => {
    if (window.snap && snapToken) {
        window.snap.pay(snapToken, {
            onSuccess: () => router.reload(),
            onPending: () => router.reload(),
            onError: () => alert("Pembayaran Gagal")
        });
    }
};

onMounted(() => {
    const script = document.createElement('script');
    script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
    script.setAttribute('data-client-key', props.midtrans_client_key);
    document.head.appendChild(script);
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        
        <!-- 1. HERO SECTION (Welcome + Quick Points) -->
        <div class="relative bg-gradient-to-r from-rose-600 to-pink-500 rounded-3xl p-8 text-white shadow-xl overflow-hidden">
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <p class="text-rose-100 font-medium mb-1">Selamat Datang Kembali,</p>
                    <h1 class="text-3xl md:text-4xl font-bold">{{ auth.user.name }}! 👋</h1>
                    <p class="mt-2 text-rose-100 text-sm max-w-md">
                        Jangan lupa cek rutinitas skincare hari ini agar kulitmu tetap glowing.
                    </p>
                </div>
                
                <!-- Point Card -->
                <div class="bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-2xl flex items-center gap-4 min-w-[200px]">
                    <div class="bg-amber-400 p-2 rounded-full shadow-lg">
                        <Star class="w-6 h-6 text-white fill-white" />
                    </div>
                    <div>
                        <p class="text-xs text-rose-100 uppercase tracking-wider font-bold">Beauty Points</p>
                        <p class="text-2xl font-bold">{{ stats.points }}</p>
                    </div>
                    <Link href="/rewards" class="ml-auto bg-white text-rose-600 px-3 py-1 rounded-full text-xs font-bold hover:bg-rose-50 transition">
                        Tukar
                    </Link>
                </div>
            </div>
            
            <!-- Decor -->
            <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
            <div class="absolute -top-20 -left-20 w-40 h-40 bg-rose-400 opacity-30 rounded-full blur-2xl"></div>
        </div>

        <!-- 2. STATUS GRID (Features Overview) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- A. Skin Analysis Status -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group relative overflow-hidden">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <ScanFace class="w-6 h-6" />
                    </div>
                    <span v-if="stats.skin_type" class="text-xs font-bold bg-blue-100 text-blue-700 px-2 py-1 rounded-lg">Terdeteksi</span>
                </div>
                <div>
                    <p class="text-gray-500 text-xs uppercase font-bold tracking-wider mb-1">Kondisi Kulit</p>
                    <h3 class="text-xl font-bold text-gray-900">{{ stats.skin_type || 'Belum Analisis' }}</h3>
                    <Link href="/skin-analysis" class="mt-4 inline-flex items-center text-sm text-blue-600 font-medium hover:underline">
                        {{ stats.skin_type ? 'Cek Detail & Produk' : 'Mulai Analisis Sekarang' }} <ChevronRight class="w-4 h-4 ml-1"/>
                    </Link>
                </div>
            </div>

            <!-- B. Routine Progress -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-green-50 text-green-600 rounded-xl group-hover:bg-green-600 group-hover:text-white transition-colors">
                        <CheckCircle2 class="w-6 h-6" />
                    </div>
                    <div class="text-right">
                        <span class="text-2xl font-bold text-gray-900">{{ stats.routine_progress }}%</span>
                    </div>
                </div>
                <div>
                    <p class="text-gray-500 text-xs uppercase font-bold tracking-wider mb-1">Progress Hari Ini</p>
                    <div class="w-full bg-gray-100 rounded-full h-2 mb-2">
                        <div class="bg-green-500 h-2 rounded-full transition-all duration-1000" :style="{ width: stats.routine_progress + '%' }"></div>
                    </div>
                    <Link href="/my-routine" class="inline-flex items-center text-sm text-green-600 font-medium hover:underline">
                        Lanjut Checklist ({{ stats.routine_count }}) <ChevronRight class="w-4 h-4 ml-1"/>
                    </Link>
                </div>
            </div>

            <!-- C. Rewards & Vouchers -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-xl group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <Ticket class="w-6 h-6" />
                    </div>
                    <span class="text-xs font-bold bg-purple-100 text-purple-700 px-2 py-1 rounded-lg">{{ stats.voucher_count }} Aktif</span>
                </div>
                <div>
                    <p class="text-gray-500 text-xs uppercase font-bold tracking-wider mb-1">Dompet Voucher</p>
                    <h3 class="text-xl font-bold text-gray-900">{{ stats.voucher_count }} Voucher</h3>
                    <Link href="/rewards" class="mt-4 inline-flex items-center text-sm text-purple-600 font-medium hover:underline">
                        Lihat Katalog Hadiah <ChevronRight class="w-4 h-4 ml-1"/>
                    </Link>
                </div>
            </div>
        </div>

        <!-- 3. RECENT ORDERS (Actionable Table) -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Pesanan Terbaru</h2>
                    <p class="text-gray-500 text-sm">Pantau status pengiriman belanjaanmu.</p>
                </div>
                <Link href="/orders" class="text-sm font-medium text-rose-600 hover:bg-rose-50 px-3 py-2 rounded-lg transition-colors">
                    Lihat Semua
                </Link>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-4 font-bold">Order ID</th>
                            <th class="px-6 py-4 font-bold">Tanggal</th>
                            <th class="px-6 py-4 font-bold">Total</th>
                            <th class="px-6 py-4 font-bold">Status</th>
                            <th class="px-6 py-4 font-bold text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-mono text-gray-700 font-medium">#{{ order.order_number }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(order.created_at) }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ formatCurrency(order.total_amount) }}</td>
                            <td class="px-6 py-4">
                                <span :class="getStatusColor(order.order_status)" class="px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wide">
                                    {{ order.order_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <!-- Tombol Lacak -->
                                <button 
                                    v-if="['shipped', 'completed'].includes(order.order_status)"
                                    @click="trackPackage(order)"
                                    class="inline-flex items-center gap-1 bg-white border border-gray-300 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-50 hover:text-rose-600 transition shadow-sm"
                                >
                                    <Truck class="w-3 h-3" /> Lacak
                                </button>
                                
                                <!-- Tombol Bayar -->
                                <button 
                                    v-else-if="order.order_status === 'pending' && order.payment_method === 'online_payment' && order.snap_token"
                                    @click="payNow(order.snap_token)"
                                    class="inline-flex items-center gap-1 bg-rose-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-rose-700 transition shadow-sm shadow-rose-200"
                                >
                                    <CreditCard class="w-3 h-3" /> Bayar
                                </button>

                                <span v-else class="text-gray-400 text-xs">-</span>
                            </td>
                        </tr>
                        <tr v-if="recentOrders.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                <ShoppingBag class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                                Belum ada pesanan terbaru. <Link href="/catalog" class="text-rose-600 hover:underline">Belanja yuk?</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL TRACKING -->
        <div v-if="showTrackingModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4 transition-all" @click.self="showTrackingModal = false">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                <div class="bg-gray-900 px-6 py-4 flex justify-between items-center text-white">
                    <h3 class="font-bold text-lg flex items-center gap-2">
                        <Truck class="w-5 h-5 text-rose-400" /> Lacak Paket
                    </h3>
                    <button @click="showTrackingModal = false" class="hover:bg-white/10 rounded-full p-1 transition"><X class="w-5 h-5"/></button>
                </div>
                <div class="p-6 max-h-[70vh] overflow-y-auto bg-gray-50">
                    <div v-if="isLoadingTrack" class="py-12 text-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-rose-600 mx-auto mb-3"></div>
                        <p class="text-gray-500 text-sm">Menghubungi kurir...</p>
                    </div>
                    <div v-else-if="trackingData?.error" class="p-4 bg-red-50 text-red-600 rounded-xl text-center text-sm font-medium">
                        {{ trackingData.error }}
                    </div>
                    <div v-else-if="trackingData" class="space-y-6">
                        <div class="bg-white p-4 rounded-xl border border-gray-200 text-center shadow-sm">
                            <p class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-1">Nomor Resi</p>
                            <p class="font-mono text-xl font-bold text-gray-900 select-all">{{ trackingData.airway_bill }}</p>
                        </div>
                        
                        <div class="relative pl-6 border-l-2 border-gray-200 space-y-8 ml-2">
                            <div v-for="(log, index) in trackingData.history" :key="index" class="relative">
                                <div class="absolute -left-[31px] top-1 w-4 h-4 rounded-full border-2 border-white shadow-sm" 
                                     :class="index === 0 ? 'bg-green-500 ring-4 ring-green-100' : 'bg-gray-400'">
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 font-mono mb-0.5">{{ log.date }}</p>
                                    <p class="font-medium text-gray-800 text-sm">{{ log.desc }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>