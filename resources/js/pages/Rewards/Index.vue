<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { ref } from 'vue';
import { Gift, History, Ticket, Star, Clock } from 'lucide-vue-next';

defineOptions({ layout: DashboardLayout });

const props = defineProps<{
    points: number,
    rewards: any[],
    my_vouchers: any[],
    history: any[]
}>();

const activeTab = ref('catalog'); // 'catalog', 'my_vouchers', 'history'

const redeem = (rewardId: number) => {
    if(confirm('Tukar poin dengan hadiah ini?')) {
        router.post(route('rewards.redeem', rewardId));
    }
};

const formatNumber = (num: number) => new Intl.NumberFormat('id-ID').format(num);
</script>

<template>
    <Head title="Points & Rewards" />
    <div class="max-w-4xl mx-auto py-8 px-4">
        
        <!-- HEADER POIN -->
        <div class="bg-gradient-to-r from-amber-400 to-orange-500 rounded-3xl p-8 text-white shadow-lg mb-8 relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-amber-100 font-medium mb-1">Saldo Poin Anda</p>
                <h1 class="text-5xl font-bold flex items-center gap-2">
                    {{ formatNumber(points) }} <span class="text-2xl opacity-80">pts</span>
                </h1>
                <p class="text-sm mt-4 text-white/90 bg-white/20 inline-block px-3 py-1 rounded-full">
                    Setiap belanja Rp 10.000 dapat 1 poin
                </p>
            </div>
            <!-- Decor -->
            <Star class="absolute -right-6 -bottom-6 w-40 h-40 text-white opacity-20" />
        </div>

        <!-- TABS -->
        <div class="flex gap-4 border-b border-gray-200 mb-6 overflow-x-auto">
            <button @click="activeTab = 'catalog'" :class="activeTab === 'catalog' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500'" class="pb-3 px-2 border-b-2 font-medium transition-colors flex items-center gap-2 whitespace-nowrap">
                <Gift class="w-4 h-4" /> Katalog Hadiah
            </button>
            <button @click="activeTab = 'my_vouchers'" :class="activeTab === 'my_vouchers' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500'" class="pb-3 px-2 border-b-2 font-medium transition-colors flex items-center gap-2 whitespace-nowrap">
                <Ticket class="w-4 h-4" /> Voucher Saya ({{ my_vouchers.length }})
            </button>
            <button @click="activeTab = 'history'" :class="activeTab === 'history' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500'" class="pb-3 px-2 border-b-2 font-medium transition-colors flex items-center gap-2 whitespace-nowrap">
                <History class="w-4 h-4" /> Riwayat
            </button>
        </div>

        <!-- 1. KATALOG HADIAH -->
        <div v-if="activeTab === 'catalog'">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                <div v-for="reward in rewards" :key="reward.id" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-md transition-shadow flex flex-col h-full">
                    <div class="bg-amber-50 w-12 h-12 rounded-full flex items-center justify-center mb-4 text-amber-600">
                        <Ticket class="w-6 h-6" />
                    </div>
                    <h3 class="font-bold text-gray-900 text-lg mb-1">{{ reward.name }}</h3>
                    <p class="text-sm text-gray-500 mb-4 flex-1">{{ reward.description || 'Potongan belanja langsung.' }}</p>
                    <div class="mt-auto">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs text-gray-400">Harga:</span>
                            <span class="font-bold text-amber-600">{{ formatNumber(reward.points_required) }} pts</span>
                        </div>
                        <button 
                            @click="redeem(reward.id)"
                            :disabled="points < reward.points_required"
                            class="w-full py-2.5 rounded-xl font-bold text-sm transition-colors"
                            :class="points >= reward.points_required ? 'bg-gray-900 text-white hover:bg-gray-800' : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                        >
                            {{ points >= reward.points_required ? 'Tukar Sekarang' : 'Poin Kurang' }}
                        </button>
                    </div>
                </div>
            </div>
            <!-- Empty State -->
            <div v-if="rewards.length === 0" class="text-center py-12 text-gray-400">Belum ada hadiah tersedia.</div>
        </div>

        <!-- 2. VOUCHER SAYA -->
        <div v-if="activeTab === 'my_vouchers'" class="space-y-4">
            <div v-for="voucher in my_vouchers" :key="voucher.id" class="bg-white border border-gray-200 rounded-xl p-5 flex justify-between items-center relative overflow-hidden group">
                <div class="absolute left-0 top-0 bottom-0 w-2 bg-green-500"></div>
                <div>
                    <h3 class="font-bold text-gray-900">{{ voucher.reward.name }}</h3>
                    <p class="text-sm text-gray-500 mt-1">Gunakan kode ini saat checkout:</p>
                    <div class="mt-2 bg-gray-100 px-3 py-1.5 rounded-lg inline-block font-mono font-bold text-gray-700 border border-dashed border-gray-300">
                        {{ voucher.code }}
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full font-medium">Aktif</span>
                    <p class="text-[10px] text-gray-400 mt-2">Exp: {{ new Date(voucher.expires_at).toLocaleDateString() }}</p>
                </div>
            </div>
            <div v-if="my_vouchers.length === 0" class="text-center py-12 border-2 border-dashed border-gray-200 rounded-xl">
                <p class="text-gray-400">Kamu belum punya voucher.</p>
                <button @click="activeTab = 'catalog'" class="text-amber-600 font-bold hover:underline text-sm mt-2">Tukar Poin Dulu</button>
            </div>
        </div>

        <!-- 3. RIWAYAT -->
        <div v-if="activeTab === 'history'" class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div v-for="(log, index) in history" :key="index" class="p-4 border-b border-gray-50 flex items-center justify-between hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    <div :class="log.is_positive ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'" class="w-10 h-10 rounded-full flex items-center justify-center shrink-0">
                        <component :is="log.is_positive ? Plus : Minus" class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ log.description }}</p>
                        <p class="text-xs text-gray-400 flex items-center gap-1"><Clock class="w-3 h-3"/> {{ log.date }}</p>
                    </div>
                </div>
                <span :class="log.is_positive ? 'text-green-600' : 'text-red-600'" class="font-bold">
                    {{ log.is_positive ? '+' : '' }}{{ log.amount }}
                </span>
            </div>
            <div v-if="history.length === 0" class="p-8 text-center text-gray-400">Belum ada riwayat transaksi.</div>
        </div>

    </div>
</template>