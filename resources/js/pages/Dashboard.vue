<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import axios from 'axios';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { 
    ShoppingBag, 
    Truck, 
    CreditCard, 
    Activity, 
    Star, 
    Clock 
} from 'lucide-vue-next';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    auth: Object,
    recentOrders: Array,
    cartCount: Number,
    stats: Object
});

// --- FORMATTER ---
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

// --- STATUS HELPER ---
const getStatusColor = (status) => {
    const colors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'paid': 'bg-blue-100 text-blue-800',
        'shipped': 'bg-purple-100 text-purple-800',
        'completed': 'bg-green-100 text-green-800',
        'canceled': 'bg-red-100 text-red-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
};

// --- FITUR TRACKING (LANGSUNG DI DASHBOARD) ---
const showTrackingModal = ref(false);
const trackingData = ref(null);
const isLoadingTrack = ref(false);
const trackingError = ref('');

const trackPackage = async (order) => {
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

// --- PEMBAYARAN ---
const payNow = (snapToken) => {
    if (window.snap && snapToken) {
        window.snap.pay(snapToken, {
            onSuccess: () => router.reload(),
            onPending: () => router.reload(),
            onError: () => alert("Gagal")
        });
    }
};

onMounted(() => {
    // Load Midtrans script
    const script = document.createElement('script');
    script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
    // Masukkan Client Key Anda disini atau pass via props jika mau dinamis
    script.setAttribute('data-client-key', 'SB-Mid-client-xxxxxx'); 
    document.head.appendChild(script);
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="min-h-screen bg-rose-50/30 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- WELCOME HEADER -->
            <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-light text-gray-900">
                        Halo, <span class="font-semibold text-rose-600">{{ auth.user.name }}</span>! 👋
                    </h1>
                    <p class="text-gray-500 text-sm mt-1">Selamat datang kembali di Skin Lab Beauty.</p>
                </div>
                
                <!-- Quick Actions -->
                <div class="flex gap-3">
                    <Link href="/catalog" class="flex items-center gap-2 bg-white border border-rose-200 text-rose-600 px-4 py-2 rounded-lg shadow-sm hover:bg-rose-50 transition">
                        <ShoppingBag class="w-4 h-4" />
                        <span>Belanja</span>
                    </Link>
                    <Link href="/cart" class="flex items-center gap-2 bg-rose-600 text-white px-4 py-2 rounded-lg shadow-md hover:bg-rose-700 transition">
                        <ShoppingBag class="w-4 h-4" />
                        <span>Keranjang ({{ cartCount }})</span>
                    </Link>
                </div>
            </div>

            <!-- STATS CARDS (Fitur GitHub: Points & Skin Analysis) -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Card 1: Points -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="p-3 bg-amber-100 text-amber-600 rounded-xl">
                        <Star class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Beauty Points</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.points }}</p>
                    </div>
                </div>

                <!-- Card 2: Skin Score -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="p-3 bg-pink-100 text-pink-600 rounded-xl">
                        <Activity class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Skin Score</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.skin_score }}/100</p>
                    </div>
                </div>

                <!-- Card 3: Routine Streak -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="p-3 bg-teal-100 text-teal-600 rounded-xl">
                        <Clock class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Routine Streak</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.routine_streak }} Hari</p>
                    </div>
                </div>

                <!-- Card 4: Voucher -->
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-4">
                    <div class="p-3 bg-purple-100 text-purple-600 rounded-xl">
                        <CreditCard class="w-6 h-6" />
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Voucher Saya</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.voucher_count }}</p>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT GRID -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- LEFT: PESANAN TERBARU (Real Data) -->
                <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                        <h2 class="font-bold text-gray-800 text-lg">Pesanan Terbaru</h2>
                        <Link href="/orders" class="text-sm text-rose-600 hover:underline">Lihat Semua</Link>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 text-xs text-gray-500 uppercase font-bold tracking-wider">
                                <tr>
                                    <th class="px-6 py-3 text-left">Order ID</th>
                                    <th class="px-6 py-3 text-left">Tanggal</th>
                                    <th class="px-6 py-3 text-left">Total</th>
                                    <th class="px-6 py-3 text-left">Status</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-gray-50/50 transition">
                                    <td class="px-6 py-4 text-sm font-mono text-gray-600">
                                        {{ order.order_number }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ formatDate(order.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-bold text-gray-900">
                                        {{ formatCurrency(order.total_amount) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-bold uppercase" :class="getStatusColor(order.order_status)">
                                            {{ order.order_status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <!-- Tombol Lacak -->
                                        <button 
                                            v-if="['shipped', 'completed'].includes(order.order_status) && order.shipping_tracking_number"
                                            @click="trackPackage(order)"
                                            class="text-xs bg-rose-100 text-rose-700 px-3 py-1.5 rounded-md hover:bg-rose-200 font-medium transition"
                                        >
                                            Lacak
                                        </button>
                                        
                                        <!-- Tombol Bayar -->
                                        <button 
                                            v-else-if="order.order_status === 'pending' && order.snap_token"
                                            @click="payNow(order.snap_token)"
                                            class="text-xs bg-blue-600 text-white px-3 py-1.5 rounded-md hover:bg-blue-700 font-medium transition"
                                        >
                                            Bayar
                                        </button>
                                        
                                        <span v-else class="text-gray-400 text-xs">-</span>
                                    </td>
                                </tr>
                                
                                <tr v-if="recentOrders.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                        Belum ada pesanan terbaru.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- RIGHT: PROMO / SKIN ANALYSIS (Fitur GitHub) -->
                <div class="space-y-6">
                    <!-- Skin Analysis Banner -->
                    <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
                        <div class="relative z-10">
                            <h3 class="font-bold text-xl mb-2">Analisis Kulit AI</h3>
                            <p class="text-rose-100 text-sm mb-4">Cek kondisi kulitmu hari ini dan dapatkan rekomendasi produk yang tepat.</p>
                            <button class="bg-white text-rose-600 px-4 py-2 rounded-lg text-sm font-bold hover:bg-rose-50 transition">
                                Mulai Analisis
                            </button>
                        </div>
                        <!-- Decoration Circle -->
                        <div class="absolute -right-10 -bottom-10 w-32 h-32 bg-white/20 rounded-full blur-2xl"></div>
                    </div>

                    <!-- Daily Routine Checklist (Mockup) -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <h3 class="font-bold text-gray-800 mb-4">Rutinitas Hari Ini</h3>
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <input type="checkbox" checked class="text-rose-600 rounded focus:ring-rose-500 border-gray-300">
                                <span class="text-sm text-gray-600 line-through">Morning Cleanser</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <input type="checkbox" class="text-rose-600 rounded focus:ring-rose-500 border-gray-300">
                                <span class="text-sm text-gray-800">Sunscreen SPF 50</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <input type="checkbox" class="text-rose-600 rounded focus:ring-rose-500 border-gray-300">
                                <span class="text-sm text-gray-800">Night Serum</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- MODAL TRACKING (Sama seperti di Order Index) -->
        <div v-if="showTrackingModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="showTrackingModal = false">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden animate-fade-in-up">
                <div class="bg-rose-600 px-6 py-4 flex justify-between items-center text-white">
                    <h3 class="font-bold text-lg flex items-center gap-2">
                        <Truck class="w-5 h-5" /> Lacak Paket
                    </h3>
                    <button @click="showTrackingModal = false" class="hover:bg-white/20 rounded-full p-1">✕</button>
                </div>
                <div class="p-6 max-h-[70vh] overflow-y-auto bg-gray-50">
                    <div v-if="isLoadingTrack" class="py-12 text-center">
                        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-rose-600 mx-auto mb-3"></div>
                        <p class="text-gray-500">Menghubungi Ekspedisi...</p>
                    </div>
                    <div v-else-if="trackingError" class="p-4 bg-red-50 text-red-700 rounded-xl border border-red-100 text-center">
                        {{ trackingError }}
                    </div>
                    <div v-else-if="trackingData" class="space-y-6">
                        <div class="bg-white p-4 rounded-xl border border-gray-200 text-center shadow-sm">
                            <p class="text-xs text-gray-400 uppercase font-bold">Nomor Resi</p>
                            <p class="font-mono text-2xl font-bold text-gray-800">{{ trackingData.airway_bill }}</p>
                        </div>
                        <div class="text-center">
                            <div class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-bold uppercase mb-2">Status Terkini</div>
                            <p class="text-lg font-bold text-gray-800">{{ trackingData.last_status }}</p>
                        </div>
                        <div class="relative pl-4 border-l-2 border-gray-200 space-y-6">
                            <div v-for="(log, index) in trackingData.history" :key="index" class="relative">
                                <div class="absolute -left-[21px] top-1 w-4 h-4 rounded-full border-2 border-white" :class="index === 0 ? 'bg-green-500 ring-4 ring-green-100' : 'bg-gray-300'"></div>
                                <div>
                                    <p class="text-xs text-gray-400 font-mono">{{ log.date }}</p>
                                    <p class="font-medium text-gray-800 text-sm">{{ log.desc }}</p>
                                    <span class="text-[10px] font-bold bg-gray-200 px-2 py-0.5 rounded text-gray-600 uppercase">{{ log.status }}</span>
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