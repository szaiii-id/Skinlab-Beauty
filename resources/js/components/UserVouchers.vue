<script setup>
import { Copy, Clock, Ticket, CheckCircle2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps({ vouchers: Array });
const emit = defineEmits(['switch-tab']);
const copiedId = ref(null);

const formatDate = (date) => {
    if (!date) return 'Lifetime Validity';
    return new Date(date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

const copyCode = (code, id) => {
    navigator.clipboard.writeText(code);
    copiedId.value = id;
    setTimeout(() => copiedId.value = null, 2000);
};
</script>

<template>
    <div v-if="vouchers.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-fade-in">
        
        <div v-for="voucher in vouchers" :key="voucher.id" class="bg-white border border-gray-200 rounded-2xl p-5 flex flex-col sm:flex-row items-center justify-between relative overflow-hidden group hover:border-green-400 hover:shadow-md transition-all">
            
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-green-500"></div>
            
            <div class="pl-4 flex-1 w-full text-center sm:text-left">
                <h3 class="font-bold text-gray-900 text-lg">{{ voucher.reward?.name || 'Discount Voucher' }}</h3>
                <p class="text-xs text-gray-400 mt-1 mb-3">Copy code below & use at checkout:</p>
                
                <button @click="copyCode(voucher.code, voucher.id)" class="w-full sm:w-auto bg-gray-50 hover:bg-green-50 px-4 py-2 rounded-lg border border-dashed border-gray-300 hover:border-green-400 font-mono font-bold text-gray-700 hover:text-green-700 text-base transition-all flex items-center justify-center sm:justify-start gap-3 group/code">
                    <span v-if="copiedId === voucher.id" class="flex items-center gap-2 text-green-600">
                        <CheckCircle2 class="w-4 h-4" /> Copied!
                    </span>
                    <span v-else class="flex items-center gap-2">
                        {{ voucher.code }} <Copy class="w-3 h-3 text-gray-400 group-hover/code:text-green-600" />
                    </span>
                </button>
            </div>
            
            <div class="mt-4 sm:mt-0 sm:text-right pl-0 sm:pl-4 sm:border-l border-gray-100 sm:ml-4 w-full sm:w-auto flex flex-row sm:flex-col justify-between sm:justify-center items-center">
                <span class="inline-block bg-green-100 text-green-700 text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wide border border-green-200 mb-0 sm:mb-2">Active</span>
                <p class="text-[10px] text-gray-400 flex items-center gap-1 font-medium bg-gray-50 px-2 py-1 rounded">
                    <Clock class="w-3 h-3" /> {{ formatDate(voucher.expires_at) }}
                </p>
            </div>

            <div class="absolute -top-2 left-[70%] w-4 h-4 bg-[#F9FAFB] rounded-full border border-gray-200 z-10 hidden sm:block"></div>
            <div class="absolute -bottom-2 left-[70%] w-4 h-4 bg-[#F9FAFB] rounded-full border border-gray-200 z-10 hidden sm:block"></div>
        </div>
    </div>

    <div v-else class="text-center py-24 bg-gray-50/50 rounded-3xl border-2 border-dashed border-gray-200">
        <div class="bg-white w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
            <Ticket class="w-8 h-8 text-gray-300" />
        </div>
        <h3 class="text-gray-900 font-bold">No Vouchers Yet</h3>
        <p class="text-gray-500 text-sm mt-1 mb-5">Redeem your points to get exclusive discounts.</p>
        <button @click="$emit('switch-tab', 'catalog')" class="text-white bg-gray-900 hover:bg-amber-500 px-6 py-2.5 rounded-full font-bold text-xs uppercase tracking-wide transition-all shadow-lg hover:shadow-amber-200">
            Go to Catalog
        </button>
    </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.5s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>