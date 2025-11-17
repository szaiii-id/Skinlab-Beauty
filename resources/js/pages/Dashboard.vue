<!-- resources/js/pages/Dashboard.vue -->
<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { ShoppingBag, Package, CheckCircle, Heart, Calendar } from 'lucide-vue-next';

defineOptions({
    layout: DashboardLayout
});

const props = defineProps({
    cart: Object,
    recentOrders: Array,
    userStats: Object,
    beautyTips: Array,
    skinAnalysis: Object
});

// Formatting function
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
    Object.values(props.cart || {}).forEach(item => {
        total += item.price * item.quantity;
    });
    return total;
});

const cartItemsCount = computed(() => {
    return Object.keys(props.cart || {}).length;
});

const recentOrdersCount = computed(() => {
    return props.recentOrders?.length || 0;
});

const routineCompletion = computed(() => {
    return props.userStats?.routineCompletion || 0;
});

// Quick actions
const quickActions = [
    { 
        label: 'Pesanan Saya', 
        icon: Package, 
        route: '/orders',
        color: 'from-blue-500 to-cyan-500',
        description: `${recentOrdersCount.value} pesanan`
    },
    { 
        label: 'Keranjang', 
        icon: ShoppingBag, 
        route: '/cart',
        color: 'from-rose-500 to-pink-500',
        description: `${cartItemsCount.value} items`
    },
    { 
        label: 'Rutinitas Skincare', 
        icon: CheckCircle, 
        route: '/my-routine',
        color: 'from-emerald-500 to-green-500',
        description: `${routineCompletion.value}% lengkap`
    },
    { 
        label: 'Analisis Kulit', 
        icon: Heart, 
        route: '/skin-analysis',
        color: 'from-purple-500 to-indigo-500',
        description: 'Update terbaru'
    }
];

// Stats cards data
const statsCards = [
    {
        title: 'Beauty Points',
        value: (props.userStats?.beautyPoints || 0).toString(),
        change: '+50 hari ini',
        trend: 'up',
        icon: '⭐',
        color: 'from-amber-500 to-orange-500',
        description: 'Tukar hadiah'
    },
    {
        title: 'Kesehatan Kulit',
        value: (props.skinAnalysis?.score || 0) + '%',
        change: '+5% minggu ini',
        trend: 'up',
        icon: '💆‍♀️',
        color: 'from-rose-500 to-pink-500',
        description: 'Berdasarkan analisis'
    },
    {
        title: 'Streak Rutinitas',
        value: (props.userStats?.streakDays || 0) + ' hari',
        change: 'Lanjutkan!',
        trend: 'up',
        icon: '📅',
        color: 'from-green-500 to-emerald-500',
        description: 'Konsistensi Anda'
    }
];

const navigateTo = (route) => {
    router.get(route);
};

// Recent activities
const recentActivities = [
    { action: 'Menyelesaikan rutinitas pagi', time: '2 jam lalu', points: 10 },
    { action: 'Membeli serum baru', time: '1 hari lalu', points: 50 },
    { action: 'Berbagi tips skincare', time: '2 hari lalu', points: 20 },
    { action: 'Menyelesaikan analisis kulit', time: '3 hari lalu', points: 30 }
];
</script>

<template>
    <Head title="Dashboard" />

    <div class="min-h-screen bg-gradient-to-br from-rose-50 to-pink-50 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <!-- Welcome Section -->
            <div class="mb-8">
                <h1 class="text-3xl font-light text-gray-900 mb-2">
                    Selamat Datang Kembali! 👋
                </h1>
                <p class="text-gray-600">
                    Overview perjalanan kecantikan Anda
                </p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div
                    v-for="stat in statsCards"
                    :key="stat.title"
                    class="bg-white rounded-2xl p-6 shadow border border-gray-100 hover:shadow-md transition-shadow"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm font-medium mb-1">{{ stat.title }}</p>
                            <p class="text-3xl font-light text-gray-900">{{ stat.value }}</p>
                            <p class="text-sm mt-1" :class="{
                                'text-green-500': stat.trend === 'up',
                                'text-yellow-500': stat.trend === 'neutral',
                                'text-red-500': stat.trend === 'down'
                            }">
                                {{ stat.change }}
                            </p>
                        </div>
                        <div class="text-3xl">
                            {{ stat.icon }}
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-gray-500 text-xs">{{ stat.description }}</p>
                        <div class="mt-2 w-full bg-gray-100 rounded-full h-2">
                            <div class="h-2 rounded-full bg-gradient-to-r" :class="stat.color" 
                                 :style="`width: ${parseInt(stat.value)}%`">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Left Column: Quick Actions & Recent Activities -->
                <div class="lg:col-span-2 space-y-8">
                    
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-2xl shadow border border-gray-100 p-6">
                        <h2 class="text-xl font-semibold text-gray-900 mb-6">Aksi Cepat</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <button
                                v-for="action in quickActions"
                                :key="action.label"
                                @click="navigateTo(action.route)"
                                class="flex flex-col items-center justify-center p-6 rounded-xl border border-gray-200 hover:shadow transition-all"
                                :class="`hover:bg-gradient-to-r ${action.color} hover:text-white`"
                            >
                                <div class="p-3 rounded-lg bg-gray-100 mb-3">
                                    <component :is="action.icon" class="w-6 h-6 text-gray-600" />
                                </div>
                                <span class="font-semibold text-center">
                                    {{ action.label }}
                                </span>
                                <span class="text-sm text-gray-500 mt-1">
                                    {{ action.description }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="bg-white rounded-2xl shadow border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-semibold text-gray-900">Aktivitas Terbaru</h2>
                            <span class="text-rose-500">📝</span>
                        </div>
                        
                        <div class="space-y-4">
                            <div
                                v-for="activity in recentActivities"
                                :key="activity.action"
                                class="flex items-center justify-between p-4 rounded-lg bg-rose-50 border border-rose-100 hover:bg-rose-100 transition-colors"
                            >
                                <div>
                                    <p class="text-rose-700 font-medium">{{ activity.action }}</p>
                                    <p class="text-rose-600/70 text-sm mt-1">{{ activity.time }}</p>
                                </div>
                                <span class="px-3 py-1 bg-rose-500 text-white rounded-full text-sm font-medium">
                                    +{{ activity.points }} pts
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Cart Summary & Beauty Tips -->
                <div class="space-y-8">
                    
                    <!-- Cart Summary - Sederhana -->
                    <div class="bg-white rounded-2xl shadow border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-semibold text-gray-900">Keranjang</h2>
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">🛒</span>
                                <span class="px-2 py-1 bg-rose-100 text-rose-600 rounded-full text-sm font-medium">
                                    {{ cartItemsCount }}
                                </span>
                            </div>
                        </div>
                        
                        <div v-if="cartItemsCount > 0" class="text-center">
                            <p class="text-gray-600 mb-2">Total belanja:</p>
                            <p class="text-2xl font-bold text-rose-600 mb-4">{{ formatCurrency(cartTotal) }}</p>
                            
                            <button 
                                @click="navigateTo('/cart')"
                                class="w-full py-3 bg-rose-600 text-white font-semibold rounded-lg hover:bg-rose-700 transition-colors"
                            >
                                Lihat Keranjang
                            </button>
                        </div>
                        
                        <div v-else class="text-center py-4">
                            <p class="text-gray-600 mb-4">Keranjang kosong</p>
                            <button 
                                @click="navigateTo('/catalog')"
                                class="w-full py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors"
                            >
                                Belanja Sekarang
                            </button>
                        </div>
                    </div>

                    <!-- Beauty Tips -->
                    <div class="bg-white rounded-2xl shadow border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-semibold text-gray-900">Tips Kecantikan</h2>
                            <span class="text-rose-500">💡</span>
                        </div>
                        
                        <div class="space-y-3">
                            <div
                                v-for="(tip, index) in beautyTips"
                                :key="index"
                                class="p-3 rounded-lg bg-rose-50 border border-rose-100"
                            >
                                <p class="text-gray-700 text-sm">{{ tip }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Weekly Progress -->
                    <div class="bg-white rounded-2xl shadow border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-semibold text-gray-900">Progress Mingguan</h2>
                            <span class="text-rose-500">📈</span>
                        </div>
                        <div class="grid grid-cols-7 gap-1">
                            <div 
                                v-for="day in ['S', 'S', 'R', 'K', 'J', 'S', 'M']"
                                :key="day"
                                class="text-center"
                            >
                                <p class="text-gray-600/80 text-xs mb-1">{{ day }}</p>
                                <div 
                                    class="w-6 h-6 mx-auto rounded flex items-center justify-center text-white text-xs"
                                    :class="{
                                        'bg-green-500': Math.random() > 0.3,
                                        'bg-gray-200 text-gray-400': Math.random() <= 0.3
                                    }"
                                >
                                    {{ Math.random() > 0.3 ? '✓' : '' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>