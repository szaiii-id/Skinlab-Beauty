<script setup>
import { Clock, ArrowUpRight, ArrowDownLeft, Wallet } from 'lucide-vue-next';

defineProps({ history: Array });
</script>

<template>
    <div v-if="history.length > 0" class="bg-white rounded-2xl border border-gray-100 overflow-hidden animate-fade-in shadow-sm">
        <div class="bg-gray-50 px-5 py-3 border-b border-gray-100 text-xs font-bold text-gray-400 uppercase tracking-wider">
            Last Transactions
        </div>

        <div v-for="(log, index) in history" :key="index" class="p-5 border-b border-gray-50 flex items-center justify-between hover:bg-gray-50/50 transition-colors last:border-0">
            <div class="flex items-center gap-4">
                <div :class="log.is_positive ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'" class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 shadow-sm">
                    <component :is="log.is_positive ? ArrowUpRight : ArrowDownLeft" class="w-5 h-5" />
                </div>
                <div>
                    <p class="font-bold text-gray-900 text-sm mb-0.5">{{ log.description }}</p>
                    <p class="text-[11px] text-gray-400 flex items-center gap-1 font-medium bg-gray-50 px-2 py-0.5 rounded w-fit">
                        <Clock class="w-3 h-3"/> {{ log.date }}
                    </p>
                </div>
            </div>
            
            <span :class="log.is_positive ? 'text-emerald-600' : 'text-rose-600'" class="font-black text-lg tracking-tight">
                {{ log.is_positive ? '+' : '' }}{{ log.amount }}
            </span>
        </div>
    </div>

    <div v-else class="text-center py-24 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200">
        <div class="bg-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
            <Wallet class="w-8 h-8 text-gray-300" />
        </div>
        <h3 class="text-gray-900 font-bold">No History</h3>
        <p class="text-gray-500 text-sm mt-1">Your transaction history will appear here.</p>
    </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.5s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>