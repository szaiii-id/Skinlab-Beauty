<script setup>
defineProps({
    vouchers: Array
});

const emit = defineEmits(['switch-tab']);

const formatDate = (date) => new Date(date).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });
</script>

<template>
    <div v-if="vouchers.length > 0" class="space-y-4 animate-fade-in">
        <div v-for="voucher in vouchers" :key="voucher.id" class="bg-white border border-gray-200 rounded-xl p-5 flex justify-between items-center relative overflow-hidden group hover:shadow-md transition-shadow">
            <div class="absolute left-0 top-0 bottom-0 w-2 bg-green-500"></div>
            
            <div>
                <h3 class="font-bold text-gray-900 text-lg">{{ voucher.reward.name }}</h3>
                <p class="text-sm text-gray-500 mt-1">Use this code at checkout:</p>
                <div class="mt-2 bg-gray-50 px-4 py-1.5 rounded-lg inline-block font-mono font-bold text-gray-700 border border-dashed border-gray-300 select-all cursor-pointer hover:bg-gray-100 transition-colors">
                    {{ voucher.code }}
                </div>
            </div>
            
            <div class="text-right">
                <span class="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full font-bold uppercase tracking-wide border border-green-100">Active</span>
                <p class="text-[10px] text-gray-400 mt-2 font-medium">Exp: {{ formatDate(voucher.expires_at) }}</p>
            </div>
        </div>
    </div>

    <div v-else class="text-center py-16 border-2 border-dashed border-gray-200 rounded-2xl bg-gray-50/50">
        <div class="text-4xl mb-2 grayscale opacity-50">🎟️</div>
        <p class="text-gray-500 font-medium">You don't have any vouchers yet.</p>
        <button @click="$emit('switch-tab', 'catalog')" class="text-amber-600 font-bold hover:underline text-sm mt-2 hover:text-amber-700 transition-colors">
            Redeem Points First
        </button>
    </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>