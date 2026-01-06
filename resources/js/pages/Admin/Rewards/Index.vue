<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { ref } from 'vue';
import { Plus, Ticket, Trash2, Edit3, Clock, DollarSign, Package } from 'lucide-vue-next';
import Swal from 'sweetalert2';

// Components
import RewardFormModal from '@/components/RewardFormModal.vue'; 
import { useFormatting } from '@/composables/useFormatting';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    rewards: Object // Paginated data dari Controller Admin
});

const { formatCurrency } = useFormatting();

// Helper Lokal
const formatNumber = (num) => {
    if (typeof num === 'number') {
        return new Intl.NumberFormat('en-US').format(num);
    }
    return num;
};

// State
const showModal = ref(false);
const isEditMode = ref(false);
const editingReward = ref(null);

// Actions
const openForm = (reward = null) => {
    if (reward) {
        isEditMode.value = true;
        editingReward.value = reward;
    } else {
        isEditMode.value = false;
        editingReward.value = null;
    }
    showModal.value = true;
};

const deleteReward = (reward) => {
    Swal.fire({
        title: 'Delete Reward?',
        text: `Delete "${reward.name}"? Action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        confirmButtonColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.rewards.destroy', reward.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Reward has been removed.', 'success');
                }
            });
        }
    });
};

const formatValue = (reward) => {
    if (reward.type === 'percent') return `${reward.value}% OFF`;
    if (reward.type === 'fixed') return `IDR ${formatNumber(reward.value)} OFF`;
    return 'Free Shipping';
};

const getStockStatus = (stock) => {
    if (stock <= 0) return { text: 'SOLD OUT', color: 'text-red-500', bg: 'bg-red-50' };
    if (stock < 20) return { text: `LOW (${stock})`, color: 'text-orange-500', bg: 'bg-orange-50' };
    return { text: 'AVAIL', color: 'text-emerald-500', bg: 'bg-emerald-50' };
};
</script>

<template>
    <Head title="Admin Rewards" />
    
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Voucher Rewards</h1>
                <p class="text-sm text-gray-600 mt-1">Manage points cost and stock.</p>
            </div>
            <button @click="openForm()" class="w-full sm:w-auto px-5 py-2.5 bg-rose-600 text-white font-bold rounded-xl shadow-md hover:bg-rose-700 transition-all text-sm flex justify-center items-center gap-2">
                <Plus class="w-5 h-5" /> New Reward
            </button>
        </div>

        <div class="hidden md:block bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reward</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost (Pts)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    <tr v-for="reward in rewards.data" :key="reward.id" class="hover:bg-rose-50/50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <div class="flex items-center gap-3">
                                <div class="bg-amber-100 p-2 rounded-lg text-amber-600">
                                    <Ticket class="w-5 h-5" />
                                </div>
                                <div>
                                    {{ reward.name }}
                                    <p class="text-xs text-gray-500 font-normal mt-0.5">Min Spend: {{ formatCurrency(reward.min_spend) }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-bold">
                            {{ formatNumber(reward.points_required) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                             <span 
                                :class="reward.type === 'percent' ? 'bg-blue-100 text-blue-600' : 'bg-rose-100 text-rose-600'" 
                                class="px-2 py-0.5 text-xs font-bold rounded-full uppercase"
                             >
                                {{ formatValue(reward) }}
                             </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span 
                                :class="[getStockStatus(reward.stock).bg, getStockStatus(reward.stock).color]" 
                                class="px-3 py-1 text-xs font-bold rounded-full uppercase"
                            >
                                {{ getStockStatus(reward.stock).text }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span 
                                :class="reward.is_active ? 'bg-emerald-100 text-emerald-600' : 'bg-gray-100 text-gray-500'" 
                                class="px-3 py-1 text-xs font-bold rounded-full uppercase"
                            >
                                {{ reward.is_active ? 'Active' : 'Disabled' }}
                            </span>
                            <p class="text-[10px] text-gray-400 mt-1 flex items-center gap-1">
                                <Clock class="w-3 h-3" /> {{ reward.validity_days }} days
                            </p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <button @click="openForm(reward)" class="text-blue-600 hover:text-blue-900 p-2 rounded-full hover:bg-blue-50 transition-colors" title="Edit">
                                    <Edit3 class="w-4 h-4" />
                                </button>
                                <button @click="deleteReward(reward)" class="text-red-600 hover:text-red-900 p-2 rounded-full hover:bg-red-50 transition-colors" title="Delete">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="md:hidden space-y-4">
            <div v-for="reward in rewards.data" :key="reward.id" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex flex-col gap-4">
                
                <div class="flex justify-between items-start">
                    <div class="flex gap-3">
                        <div class="bg-amber-50 p-2.5 rounded-xl h-fit text-amber-500 border border-amber-100">
                            <Ticket class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 text-base leading-snug">{{ reward.name }}</h3>
                            <div class="flex flex-wrap gap-2 mt-2">
                                <span 
                                    :class="reward.is_active ? 'bg-emerald-50 text-emerald-600 border-emerald-100' : 'bg-gray-100 text-gray-500 border-gray-200'" 
                                    class="px-2 py-0.5 text-[10px] font-bold rounded-md border uppercase tracking-wider"
                                >
                                    {{ reward.is_active ? 'Active' : 'Disabled' }}
                                </span>
                                <span 
                                    :class="reward.type === 'percent' ? 'bg-blue-50 text-blue-600 border-blue-100' : 'bg-rose-50 text-rose-600 border-rose-100'" 
                                    class="px-2 py-0.5 text-[10px] font-bold rounded-md border uppercase tracking-wider"
                                >
                                    {{ formatValue(reward) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <div class="space-y-1">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Points Cost</p>
                        <p class="text-sm font-bold text-gray-900">{{ formatNumber(reward.points_required) }} pts</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Min Spend</p>
                        <p class="text-sm font-bold text-gray-900">{{ formatCurrency(reward.min_spend) }}</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Validity</p>
                        <div class="flex items-center gap-1 text-sm font-medium text-gray-700">
                            <Clock class="w-3.5 h-3.5 text-gray-400" /> {{ reward.validity_days }} Days
                        </div>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] text-gray-400 font-bold uppercase">Stock</p>
                        <span 
                            :class="[getStockStatus(reward.stock).color]" 
                            class="text-xs font-bold uppercase flex items-center gap-1"
                        >
                            <Package class="w-3.5 h-3.5" /> {{ getStockStatus(reward.stock).text }}
                        </span>
                    </div>
                </div>

                <div class="flex gap-3 pt-2 border-t border-gray-100">
                    <button @click="deleteReward(reward)" class="flex-1 py-2.5 rounded-xl border border-gray-200 text-red-600 font-bold text-sm hover:bg-red-50 flex items-center justify-center gap-2 transition-colors">
                        <Trash2 class="w-4 h-4" /> Delete
                    </button>
                    <button @click="openForm(reward)" class="flex-1 py-2.5 rounded-xl bg-gray-900 text-white font-bold text-sm hover:bg-gray-800 flex items-center justify-center gap-2 transition-colors shadow-sm">
                        <Edit3 class="w-4 h-4" /> Edit
                    </button>
                </div>

            </div>
        </div>

    </div>
    
    <RewardFormModal 
        :show="showModal"
        :is-edit="isEditMode"
        :initial-data="editingReward"
        @close="showModal = false"
    />
</template>