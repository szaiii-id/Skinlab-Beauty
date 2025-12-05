<script setup>
import { Ticket, Lock, ArrowRight } from 'lucide-vue-next';

defineProps({
    rewards: Array,
    userPoints: Number
});

defineEmits(['redeem']);

const formatNumber = (num) => new Intl.NumberFormat('en-US').format(num);
</script>

<template>
    <div v-if="rewards.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 animate-fade-in">
        
        <div v-for="reward in rewards" :key="reward.id" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 flex flex-col h-full group relative overflow-hidden">
            
            <div class="w-full h-32 bg-amber-50 rounded-xl mb-4 flex items-center justify-center overflow-hidden relative">
                <img v-if="reward.image" :src="'/storage/' + reward.image" class="w-full h-full object-cover" />
                <Ticket v-else class="w-10 h-10 text-amber-400 opacity-80" />
            </div>
            
            <h3 class="font-bold text-gray-900 text-lg mb-1 leading-tight">{{ reward.name }}</h3>
            <p class="text-sm text-gray-500 mb-6 flex-1 leading-relaxed line-clamp-2">
                {{ reward.description || 'Redeem points to get this exclusive voucher.' }}
            </p>
            
            <div class="mt-auto border-t border-dashed border-gray-100 pt-4">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Cost</span>
                    <span class="font-black text-amber-600 text-lg">{{ formatNumber(reward.points_required) }} pts</span>
                </div>
                
                <button 
                    @click="$emit('redeem', reward)"
                    :disabled="userPoints < reward.points_required"
                    class="w-full py-3 rounded-xl font-bold text-xs uppercase tracking-wide transition-all shadow-sm flex items-center justify-center gap-2 group/btn"
                    :class="userPoints >= reward.points_required 
                        ? 'bg-gray-900 text-white hover:bg-amber-500 hover:text-white hover:shadow-amber-200 hover:shadow-lg' 
                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                >
                    <Lock v-if="userPoints < reward.points_required" class="w-3 h-3" />
                    {{ userPoints >= reward.points_required ? 'Redeem Now' : 'Not Enough Points' }}
                    <ArrowRight v-if="userPoints >= reward.points_required" class="w-3 h-3 group-hover/btn:translate-x-1 transition-transform" />
                </button>
            </div>
        </div>
    </div>

    <div v-else class="text-center py-20 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200">
        <div class="bg-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
            <Ticket class="w-8 h-8 text-gray-300" />
        </div>
        <h3 class="text-gray-900 font-bold">Catalog Empty</h3>
        <p class="text-gray-500 text-sm mt-1">No rewards available right now.</p>
    </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.5s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>