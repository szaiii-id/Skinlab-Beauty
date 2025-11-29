<script setup>
import { Clock, Plus, Minus } from 'lucide-vue-next';

defineProps({
    history: Array
});
</script>

<template>
    <div v-if="history.length > 0" class="bg-white rounded-2xl border border-gray-100 overflow-hidden animate-fade-in shadow-sm">
        <div v-for="(log, index) in history" :key="index" class="p-4 border-b border-gray-50 flex items-center justify-between hover:bg-gray-50 transition-colors last:border-0">
            <div class="flex items-center gap-4">
                <div :class="log.is_positive ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'" class="w-10 h-10 rounded-full flex items-center justify-center shrink-0">
                    <component :is="log.is_positive ? Plus : Minus" class="w-5 h-5" />
                </div>
                <div>
                    <p class="font-bold text-gray-900 text-sm">{{ log.description }}</p>
                    <p class="text-xs text-gray-400 flex items-center gap-1 mt-0.5"><Clock class="w-3 h-3"/> {{ log.date }}</p>
                </div>
            </div>
            <span :class="log.is_positive ? 'text-green-600' : 'text-red-600'" class="font-bold text-lg">
                {{ log.is_positive ? '+' : '' }}{{ log.amount }}
            </span>
        </div>
    </div>

    <div v-else class="p-12 text-center text-gray-400 border-2 border-dashed border-gray-200 rounded-2xl bg-gray-50/50">
        No transaction history yet.
    </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>