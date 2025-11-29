<script setup>
import { computed } from 'vue';
import { Ticket, X } from 'lucide-vue-next';

const props = defineProps({
    items: { type: Array, default: () => [] },
    subtotal: { type: Number, default: 0 },
    shippingCost: { type: Number, default: 0 },
    availableVouchers: { type: Array, default: () => [] },
    selectedVoucher: { type: Object, default: null },
    isProcessing: { type: Boolean, default: false },
    canSubmit: { type: Boolean, default: false }
});

const emit = defineEmits(['apply-voucher', 'remove-voucher', 'submit-order']);

// Helper
const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

// Voucher Logic
const voucherDiscount = computed(() => {
    if (!props.selectedVoucher) return 0;
    const reward = props.selectedVoucher.reward;
    return reward.type === 'discount_fixed' 
        ? reward.value 
        : (props.subtotal * reward.value) / 100;
});

const finalTotal = computed(() => {
    const total = (props.subtotal + props.shippingCost) - voucherDiscount.value;
    return total > 0 ? total : 0;
});

const handleVoucherChange = (event) => {
    const index = event.target.value;
    if (index !== "") {
        emit('apply-voucher', props.availableVouchers[index]);
    }
};
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
        
        <div class="space-y-4 max-h-80 overflow-y-auto pr-2 custom-scrollbar">
            <div v-for="item in items" :key="item.variant_id" class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 border border-gray-200">
                    <img :src="item.image_url || '/images/default-product.png'" class="w-full h-full object-cover" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-gray-900 truncate">{{ item.name }}</p>
                    <p class="text-xs text-gray-500">Qty: {{ item.quantity }}</p>
                </div>
                <p class="text-sm font-bold text-gray-900">{{ formatCurrency(item.price * item.quantity) }}</p>
            </div>
        </div>

        <div class="border-t border-gray-200 mt-4 pt-4">
            <h3 class="text-sm font-semibold text-gray-800 mb-2 flex items-center gap-1">
                <Ticket class="w-4 h-4 text-rose-500" /> My Vouchers
            </h3>
            
            <div v-if="selectedVoucher" class="bg-green-50 border border-green-200 rounded-lg p-3 flex justify-between items-center mb-2 animate-fade-in">
                <div>
                    <p class="text-sm font-bold text-green-800">{{ selectedVoucher.reward.name }}</p>
                    <p class="text-xs text-green-600">Save {{ formatCurrency(voucherDiscount) }}</p>
                </div>
                <button @click="$emit('remove-voucher')" class="text-gray-400 hover:text-red-500 p-1 hover:bg-red-50 rounded-full transition-colors">
                    <X class="w-4 h-4" />
                </button>
            </div>

            <div v-else>
                <div v-if="availableVouchers.length > 0" class="space-y-2">
                    <select 
                        @change="handleVoucherChange" 
                        class="w-full text-sm border-gray-300 rounded-lg focus:ring-rose-500 focus:border-rose-500 bg-white shadow-sm cursor-pointer hover:border-rose-300 transition-colors"
                    >
                        <option value="" selected disabled>Select Available Voucher</option>
                        <option v-for="(v, idx) in availableVouchers" :key="v.id" :value="idx">
                            {{ v.reward.name }}
                        </option>
                    </select>
                </div>
                <p v-else class="text-xs text-gray-400 italic">No vouchers available for this order.</p>
            </div>
        </div>

        <div class="border-t border-gray-200 mt-4 pt-4 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Subtotal</span>
                <span class="font-bold text-gray-900">{{ formatCurrency(subtotal) }}</span>
            </div>
            
            <div class="flex justify-between text-sm">
                <span class="text-gray-600">Shipping Fee</span>
                <span class="font-bold text-gray-900">{{ shippingCost ? formatCurrency(shippingCost) : '-' }}</span>
            </div>

            <div v-if="selectedVoucher" class="flex justify-between text-sm text-green-600">
                <span class="font-bold">Discount</span>
                <span class="font-bold">-{{ formatCurrency(voucherDiscount) }}</span>
            </div>

            <div class="flex justify-between items-center border-t border-gray-200 pt-4 mt-2">
                <span class="font-bold text-gray-900 text-lg">Total</span>
                <span class="text-xl font-bold text-rose-600">{{ formatCurrency(finalTotal) }}</span>
            </div>
        </div>

        <div class="mt-6">
            <button 
                @click="$emit('submit-order')" 
                :disabled="isProcessing || !canSubmit"
                class="w-full py-4 bg-gradient-to-r from-rose-500 to-pink-600 text-white font-bold rounded-xl hover:shadow-lg hover:scale-[1.02] disabled:from-gray-300 disabled:to-gray-400 disabled:cursor-not-allowed disabled:scale-100 disabled:shadow-none transition-all shadow-md"
            >
                <span v-if="isProcessing">Processing...</span>
                <span v-else>Pay Now</span>
            </button>
        </div>
    </div>
</template>