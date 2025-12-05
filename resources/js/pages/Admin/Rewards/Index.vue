<script setup>
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { ref } from 'vue';
import { Plus, Ticket, Star, Trash2, Edit3, Eye } from 'lucide-vue-next';
import Swal from 'sweetalert2';

// Components
import RewardFormModal from '@/components/RewardFormModal.vue'; 
import { useFormatting } from '@/composables/useFormatting';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    rewards: Object // Paginated data dari Controller Admin
});

const { formatCurrency } = useFormatting();

// Helper Lokal (untuk formatting Poin, yang tidak berupa mata uang)
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
        text: `Are you sure you want to delete the reward "${reward.name}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Delete',
        confirmButtonColor: '#dc2626' // Red
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
    if (stock < 20) return { text: `LOW STOCK (${stock})`, color: 'text-orange-500', bg: 'bg-orange-50' };
    return { text: 'AVAILABLE', color: 'text-emerald-500', bg: 'bg-emerald-50' };
};
</script>

<template>
    <Head title="Admin Rewards" />
    
    <div class="max-w-6xl mx-auto">
        
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Voucher Rewards</h1>
                <p class="text-sm text-gray-600 mt-1">Manage points cost, stock, and validity for loyalty program.</p>
            </div>
            <button @click="openForm()" class="px-5 py-2.5 bg-rose-600 text-white font-bold rounded-xl shadow-md hover:bg-rose-700 transition-all text-sm flex items-center gap-2">
                <Plus class="w-5 h-5" /> New Reward
            </button>
        </div>

        <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
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
                    <tr v-for="reward in rewards.data" :key="reward.id" class="hover:bg-rose-50/50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <div class="flex items-center gap-3">
                                <Ticket class="w-5 h-5 text-amber-500" />
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
                            <p class="text-[10px] text-gray-400 mt-1">Valid for {{ reward.validity_days }} days</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium flex justify-end gap-2">
                            <button @click="openForm(reward)" class="text-blue-600 hover:text-blue-900 p-1.5 rounded-full hover:bg-blue-50 transition-colors" title="Edit">
                                <Edit3 class="w-4 h-4" />
                            </button>
                            <button @click="deleteReward(reward)" class="text-red-600 hover:text-red-900 p-1.5 rounded-full hover:bg-red-50 transition-colors" title="Delete">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <RewardFormModal 
        :show="showModal"
        :is-edit="isEditMode"
        :initial-data="editingReward"
        @close="showModal = false"
    />
</template>