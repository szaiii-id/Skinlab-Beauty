<script setup>
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue'; // Pastikan path layout sesuai project Anda
import { ref } from 'vue';
import { Gift, History, Ticket } from 'lucide-vue-next';
import Swal from 'sweetalert2';

// Import Modular Components
import RewardHeader from '@/components/RewardHeader.vue';
import RewardCatalog from '@/components/RewardCatalog.vue';
import UserVouchers from '@/components/UserVouchers.vue';
import PointHistory from '@/components/PointHistory.vue';

defineOptions({ layout: DashboardLayout });

// Props dari Controller
const props = defineProps({
    points: Number,
    tier: String, // Menerima data level member (Bronze/Silver/Gold)
    rewards: Array,
    my_vouchers: Array,
    history: Array
});

// State untuk Tab Aktif
const activeTab = ref('catalog'); // Default tab

// Action: Handle Redeem dengan Konfirmasi SweetAlert
const handleRedeem = (reward) => {
    Swal.fire({
        title: '<span class="text-gray-900 font-bold">Redeem Reward?</span>',
        text: `Exchange ${reward.points_required} points for "${reward.name}"?`,
        icon: 'question',
        iconColor: '#f59e0b', // Warna Amber
        showCancelButton: true,
        confirmButtonText: 'Yes, Redeem',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#e5e7eb',
        customClass: {
            cancelButton: 'text-gray-600 font-medium'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('rewards.redeem', reward.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Voucher added to your wallet.',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                    activeTab.value = 'my_vouchers'; // Otomatis pindah ke tab voucher
                },
                onError: () => {
                    Swal.fire('Failed', 'Something went wrong. Please try again.', 'error');
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Loyalty Rewards" />
    
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6">
        
        <RewardHeader :points="points" :tier-name="tier" />

        <div class="flex gap-8 border-b border-gray-100 mb-8 overflow-x-auto no-scrollbar">
            <button 
                @click="activeTab = 'catalog'" 
                class="pb-3 px-1 border-b-[3px] font-bold transition-all flex items-center gap-2 whitespace-nowrap text-sm"
                :class="activeTab === 'catalog' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
            >
                <Gift class="w-4 h-4" /> Reward Catalog
            </button>
            
            <button 
                @click="activeTab = 'my_vouchers'" 
                class="pb-3 px-1 border-b-[3px] font-bold transition-all flex items-center gap-2 whitespace-nowrap text-sm"
                :class="activeTab === 'my_vouchers' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
            >
                <Ticket class="w-4 h-4" /> My Vouchers 
                <span v-if="my_vouchers.length > 0" class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-xs ml-1 font-bold">{{ my_vouchers.length }}</span>
            </button>
            
            <button 
                @click="activeTab = 'history'" 
                class="pb-3 px-1 border-b-[3px] font-bold transition-all flex items-center gap-2 whitespace-nowrap text-sm"
                :class="activeTab === 'history' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
            >
                <History class="w-4 h-4" /> History
            </button>
        </div>

        <div class="min-h-[400px]">
            <Transition name="fade" mode="out-in">
                
                <div v-if="activeTab === 'catalog'" key="catalog">
                    <RewardCatalog 
                        :rewards="rewards" 
                        :user-points="points" 
                        @redeem="handleRedeem" 
                    />
                </div>

                <div v-else-if="activeTab === 'my_vouchers'" key="vouchers">
                    <UserVouchers 
                        :vouchers="my_vouchers" 
                        @switch-tab="activeTab = $event"
                    />
                </div>

                <div v-else-if="activeTab === 'history'" key="history">
                    <PointHistory :history="history" />
                </div>

            </Transition>
        </div>

    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>