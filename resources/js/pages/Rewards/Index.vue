<script setup>
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { ref } from 'vue';
import { Gift, History, Ticket } from 'lucide-vue-next';
import Swal from 'sweetalert2';

// Components
import RewardHeader from '@/components/RewardHeader.vue';
import RewardCatalog from '@/components/RewardCatalog.vue';
import UserVouchers from '@/components/UserVouchers.vue';
import PointHistory from '@/components/PointHistory.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    points: Number,
    rewards: Array,
    my_vouchers: Array,
    history: Array
});

// State
const activeTab = ref('catalog'); // 'catalog', 'my_vouchers', 'history'

// Actions
const handleRedeem = (reward) => {
    Swal.fire({
        title: '<span class="text-gray-900 font-bold">Redeem Reward?</span>',
        text: `Are you sure you want to exchange ${reward.points_required} points for "${reward.name}"?`,
        icon: 'question',
        iconColor: '#f59e0b', // Amber-500
        showCancelButton: true,
        confirmButtonText: 'Yes, Redeem!',
        cancelButtonText: 'Cancel',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-2xl border border-amber-100 shadow-xl p-6',
            confirmButton: 'bg-amber-500 text-white font-bold py-3 px-6 rounded-xl hover:bg-amber-600 transition-colors mx-2 shadow-lg shadow-amber-200',
            cancelButton: 'bg-white text-gray-500 font-medium py-3 px-6 rounded-xl border border-gray-200 hover:bg-gray-50 transition-colors mx-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('rewards.redeem', reward.id), {}, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Voucher has been added to your wallet.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                    activeTab.value = 'my_vouchers'; // Auto switch to vouchers tab
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Points & Rewards" />
    
    <div class="max-w-4xl mx-auto py-8 px-4">
        
        <RewardHeader :points="points" />

        <div class="flex gap-6 border-b border-gray-200 mb-8 overflow-x-auto no-scrollbar">
            <button 
                @click="activeTab = 'catalog'" 
                class="pb-3 px-1 border-b-2 font-bold transition-all flex items-center gap-2 whitespace-nowrap text-sm"
                :class="activeTab === 'catalog' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
            >
                <Gift class="w-4 h-4" /> Reward Catalog
            </button>
            <button 
                @click="activeTab = 'my_vouchers'" 
                class="pb-3 px-1 border-b-2 font-bold transition-all flex items-center gap-2 whitespace-nowrap text-sm"
                :class="activeTab === 'my_vouchers' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
            >
                <Ticket class="w-4 h-4" /> My Vouchers <span class="bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full text-xs ml-1">{{ my_vouchers.length }}</span>
            </button>
            <button 
                @click="activeTab = 'history'" 
                class="pb-3 px-1 border-b-2 font-bold transition-all flex items-center gap-2 whitespace-nowrap text-sm"
                :class="activeTab === 'history' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
            >
                <History class="w-4 h-4" /> History
            </button>
        </div>

        <div class="min-h-[300px]">
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
/* Tab Transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>