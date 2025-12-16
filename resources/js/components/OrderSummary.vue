<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Ticket, X, Loader2, AlertCircle, ChevronDown, Check } from 'lucide-vue-next';

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

// State untuk Custom Dropdown
const isDropdownOpen = ref(false);
const dropdownRef = ref(null);

// Helper Currency
const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

// Cek Kelayakan Voucher
const isVoucherEligible = (voucher) => {
    const minSpend = Number(voucher.reward.min_spend) || 0;
    return props.subtotal >= minSpend;
};

// Voucher Logic
const voucherDiscount = computed(() => {
    if (!props.selectedVoucher) return 0;
    if (!isVoucherEligible(props.selectedVoucher)) return 0; // Safety check

    const reward = props.selectedVoucher.reward;
    const value = Number(reward.value);
    const type = (reward.type || '').toLowerCase();
    
    let discount = 0;
    if (type.includes('fixed')) {
        discount = value;
    } else {
        discount = (props.subtotal * value) / 100;
    }

    return Math.min(discount, props.subtotal);
});

const finalTotal = computed(() => {
    const total = (props.subtotal + props.shippingCost) - voucherDiscount.value;
    return total > 0 ? total : 0;
});

// --- ACTIONS ---
const toggleDropdown = () => {
    if (props.availableVouchers.length > 0) {
        isDropdownOpen.value = !isDropdownOpen.value;
    }
};

const selectVoucher = (voucher, index) => {
    if (isVoucherEligible(voucher)) {
        emit('apply-voucher', voucher);
        isDropdownOpen.value = false;
    }
};

// Klik di luar dropdown untuk menutup
const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isDropdownOpen.value = false;
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-6 z-20">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
        
        <div class="space-y-4 max-h-80 overflow-y-auto pr-2 custom-scrollbar mb-6">
            <div v-for="item in items" :key="item.variant_id" class="flex gap-3 py-2 border-b border-gray-50 last:border-0">
                <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 border border-gray-200">
                    <img :src="item.image_url || '/images/default-product.png'" class="w-full h-full object-cover" />
                </div>
                
                <div class="flex-1 min-w-0 flex flex-col justify-center">
                    <span v-if="item.brand" class="text-[10px] uppercase font-bold text-gray-400 tracking-wider mb-0.5">
                        {{ item.brand }}
                    </span>
                    <p class="text-sm font-bold text-gray-900 truncate leading-tight">{{ item.name }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        Variant: <span class="font-medium text-gray-700">{{ item.volume }}</span> • Qty: {{ item.quantity }}
                    </p>
                </div>

                <div class="flex flex-col items-end justify-center">
                    <p class="text-sm font-bold text-gray-900">
                        {{ formatCurrency(item.price * item.quantity) }}
                    </p>
                    <p v-if="item.has_discount" class="text-xs text-gray-400 line-through mt-0.5">
                        {{ formatCurrency(item.original_price * item.quantity) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-4" ref="dropdownRef">
            <h3 class="text-sm font-semibold text-gray-800 mb-2 flex items-center gap-1">
                <Ticket class="w-4 h-4 text-rose-500" /> Vouchers
            </h3>
            
            <div v-if="selectedVoucher" class="bg-emerald-50 border border-emerald-200 rounded-lg p-3 flex justify-between items-center mb-2 animate-fade-in transition-all">
                <div v-if="isVoucherEligible(selectedVoucher)">
                    <p class="text-sm font-bold text-emerald-800">{{ selectedVoucher.reward.name }}</p>
                    <p class="text-xs text-emerald-600">Applied: -{{ formatCurrency(voucherDiscount) }}</p>
                </div>
                <div v-else class="flex items-center gap-2">
                    <AlertCircle class="w-4 h-4 text-amber-500" />
                    <div>
                        <p class="text-xs font-bold text-amber-700">Min. spend not met</p>
                        <p class="text-[10px] text-amber-600">Add more items</p>
                    </div>
                </div>
                <button @click="$emit('remove-voucher')" class="text-emerald-600 hover:text-red-500 hover:bg-white rounded-full p-1 transition-all" title="Remove Voucher">
                    <X class="w-4 h-4" />
                </button>
            </div>

            <div v-else class="relative">
                <div 
                    @click="toggleDropdown"
                    class="w-full flex justify-between items-center bg-gray-50 border border-gray-300 text-gray-700 text-sm rounded-lg px-4 py-2.5 cursor-pointer hover:border-rose-400 hover:bg-white transition-all duration-200"
                    :class="{'ring-2 ring-rose-100 border-rose-400': isDropdownOpen}"
                >
                    <span v-if="availableVouchers.length > 0">Select Available Voucher</span>
                    <span v-else class="text-gray-400 italic">No vouchers available</span>
                    <ChevronDown class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="{'rotate-180': isDropdownOpen}" />
                </div>

                <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                    <div v-if="isDropdownOpen && availableVouchers.length > 0" class="absolute z-50 mt-1 w-full bg-white shadow-xl max-h-60 rounded-lg py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto custom-scrollbar focus:outline-none sm:text-sm">
                        
                        <div 
                            v-for="(v, index) in availableVouchers" 
                            :key="v.id"
                            @click="selectVoucher(v, index)"
                            class="relative cursor-pointer select-none py-3 pl-4 pr-4 border-b border-gray-50 last:border-0 transition-colors"
                            :class="[
                                isVoucherEligible(v) ? 'hover:bg-rose-50' : 'bg-gray-50 cursor-not-allowed opacity-70'
                            ]"
                        >
                            <div class="flex justify-between items-start">
                                <div>
                                    <span class="block truncate font-medium" :class="isVoucherEligible(v) ? 'text-gray-900' : 'text-gray-500'">
                                        {{ v.reward.name }}
                                    </span>
                                    
                                    <span v-if="!isVoucherEligible(v)" class="flex items-center gap-1 mt-0.5 text-xs text-rose-500 font-medium">
                                        <AlertCircle class="w-3 h-3" />
                                        Min. spend {{ formatCurrency(v.reward.min_spend) }}
                                    </span>
                                    <span v-else class="text-xs text-gray-500">
                                        Click to apply
                                    </span>
                                </div>
                                
                                <Check v-if="isVoucherEligible(v)" class="w-4 h-4 text-rose-500 hidden group-hover:block" />
                            </div>
                        </div>

                    </div>
                </transition>
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

            <div v-if="selectedVoucher && isVoucherEligible(selectedVoucher)" class="flex justify-between text-sm text-emerald-600 animate-pulse">
                <span class="font-bold">Discount</span>
                <span class="font-bold">-{{ formatCurrency(voucherDiscount) }}</span>
            </div>

            <div class="flex justify-between items-center border-t border-gray-200 pt-4 mt-2">
                <span class="font-bold text-gray-900 text-lg">Total Payment</span>
                <span class="text-2xl font-black text-rose-600 tracking-tight">{{ formatCurrency(finalTotal) }}</span>
            </div>
        </div>

        <div class="mt-6">
            <button 
                @click="$emit('submit-order')" 
                :disabled="isProcessing || !canSubmit"
                class="w-full py-4 bg-gradient-to-r from-rose-500 to-pink-600 text-white font-bold rounded-xl hover:shadow-lg hover:scale-[1.02] active:scale-95 disabled:from-gray-300 disabled:to-gray-400 disabled:cursor-not-allowed disabled:scale-100 disabled:shadow-none transition-all shadow-md flex justify-center items-center gap-2"
            >
                <Loader2 v-if="isProcessing" class="w-5 h-5 animate-spin" />
                <span v-else>Pay Now</span>
            </button>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1; 
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #d1d5db; 
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af; 
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>