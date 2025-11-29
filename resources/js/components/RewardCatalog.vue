<script setup>
import { Ticket } from 'lucide-vue-next';

defineProps({
    rewards: Array,
    userPoints: Number
});

const emit = defineEmits(['redeem']);

const formatNumber = (num) => new Intl.NumberFormat('en-US').format(num);
</script>

<template>
    <div v-if="rewards.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 animate-fade-in">
        <div v-for="reward in rewards" :key="reward.id" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col h-full group">
            
            <div class="bg-amber-50 w-12 h-12 rounded-full flex items-center justify-center mb-4 text-amber-600 group-hover:bg-amber-100 transition-colors">
                <Ticket class="w-6 h-6" />
            </div>
            
            <h3 class="font-bold text-gray-900 text-lg mb-1">{{ reward.name }}</h3>
            <p class="text-sm text-gray-500 mb-4 flex-1">{{ reward.description || 'Instant discount voucher.' }}</p>
            
            <div class="mt-auto border-t border-gray-50 pt-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs text-gray-400 font-bold uppercase">Price</span>
                    <span class="font-bold text-amber-600">{{ formatNumber(reward.points_required) }} pts</span>
                </div>
                
                <button 
                    @click="$emit('redeem', reward)"
                    :disabled="userPoints < reward.points_required"
                    class="w-full py-2.5 rounded-xl font-bold text-sm transition-all shadow-sm"
                    :class="userPoints >= reward.points_required 
                        ? 'bg-gray-900 text-white hover:bg-gray-800 hover:shadow-md' 
                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                >
                    {{ userPoints >= reward.points_required ? 'Redeem Now' : 'Not Enough Points' }}
                </button>
            </div>
        </div>
    </div>

    <div v-else class="text-center py-12 text-gray-400 border-2 border-dashed border-gray-200 rounded-2xl">
        No rewards available at the moment.
    </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>