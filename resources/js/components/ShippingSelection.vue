<script setup>
import { computed, ref, watch } from 'vue';
import { ChevronDown, Truck, Check, Package } from 'lucide-vue-next';

const props = defineProps({
    rates: { type: Array, default: () => [] },
    isLoading: { type: Boolean, default: false },
    error: { type: String, default: '' },
    selectedService: { type: Object, default: null },
    hasAddress: { type: Boolean, default: false }
});

const emit = defineEmits(['select-service']);

// State
const isOpen = ref(false);
const activeTab = ref('ALL'); // Default tab

// Helper Format
const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

// 1. Get Unique Courier Names for Tabs
const availableCouriers = computed(() => {
    const couriers = new Set(props.rates.map(r => r.courier_name));
    return ['ALL', ...Array.from(couriers)];
});

// 2. Filter Services based on Active Tab
const filteredRates = computed(() => {
    let list = props.rates;
    
    // Filter by Tab
    if (activeTab.value !== 'ALL') {
        list = list.filter(r => r.courier_name === activeTab.value);
    }

    // Sort by price (Cheapest first)
    return list.sort((a, b) => a.price - b.price);
});

// Watcher: Reset tab when rates change
watch(() => props.rates, () => {
    activeTab.value = 'ALL';
});

// Actions
const toggleDropdown = () => {
    if (!props.hasAddress || props.isLoading) return;
    isOpen.value = !isOpen.value;
};

const selectOption = (service) => {
    emit('select-service', service);
    isOpen.value = false;
};

const setActiveTab = (courierName) => {
    activeTab.value = courierName;
};
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 relative">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Shipping Method</h2>
        
        <div v-if="isLoading" class="py-6 text-center bg-gray-50 rounded-lg border border-dashed border-gray-200">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-rose-600 mx-auto mb-2"></div>
            <p class="text-gray-500 text-xs font-medium">Checking courier availability...</p>
        </div>

        <div v-else-if="error" class="p-3 bg-red-50 text-red-700 text-sm rounded-lg border border-red-100 flex items-center gap-2">
            ⚠️ {{ error }}
        </div>

        <div v-else-if="!hasAddress" class="text-gray-400 text-center py-6 border border-dashed rounded-lg bg-gray-50 text-sm">
            Please select a shipping address first.
        </div>

        <div v-else class="relative">
            
            <button 
                @click="toggleDropdown"
                class="w-full flex items-center justify-between p-4 bg-white border rounded-xl transition-all duration-200 group focus:outline-none"
                :class="isOpen ? 'border-rose-500 ring-2 ring-rose-100' : 'border-gray-300 hover:border-rose-400 hover:shadow-sm'"
            >
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center transition-colors"
                        :class="selectedService ? 'bg-rose-100 text-rose-600' : 'bg-gray-100 text-gray-500'">
                        <Truck class="w-5 h-5" />
                    </div>
                    
                    <div v-if="selectedService" class="text-left">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-gray-900 text-sm">{{ selectedService.courier_name }}</span>
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded border border-gray-200 font-medium">
                                {{ selectedService.service_type }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Estimasi: {{ selectedService.duration }}
                        </p>
                    </div>
                    <div v-else class="text-left">
                        <p class="font-medium text-gray-700 text-sm">Select Shipping Courier</p>
                        <p class="text-xs text-gray-400">Choose the best rate for you</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span v-if="selectedService" class="font-bold text-rose-600 text-sm">
                        {{ formatCurrency(selectedService.price) }}
                    </span>
                    <ChevronDown 
                        class="w-5 h-5 text-gray-400 transition-transform duration-200" 
                        :class="{ 'rotate-180': isOpen }"
                    />
                </div>
            </button>

            <div 
                v-if="isOpen"
                class="absolute z-50 mt-2 w-full bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden animate-in fade-in slide-in-from-top-2"
            >
                <div class="flex items-center gap-2 p-2 bg-gray-50 border-b border-gray-100 overflow-x-auto no-scrollbar">
                    <button 
                        v-for="courier in availableCouriers" 
                        :key="courier"
                        @click.stop="setActiveTab(courier)"
                        class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all whitespace-nowrap border"
                        :class="activeTab === courier 
                            ? 'bg-rose-600 text-white border-rose-600 shadow-sm' 
                            : 'bg-white text-gray-600 border-gray-200 hover:border-rose-300 hover:text-rose-600'"
                    >
                        {{ courier }}
                    </button>
                </div>

                <div class="max-h-64 overflow-y-auto custom-scrollbar">
                    <div v-if="filteredRates.length === 0" class="p-6 text-center text-gray-400 text-xs">
                        No services available for this courier.
                    </div>

                    <div 
                        v-for="service in filteredRates" 
                        :key="service.id"
                        @click="selectOption(service)"
                        class="px-4 py-3 cursor-pointer transition-colors border-b border-gray-50 last:border-0 flex justify-between items-center group hover:bg-rose-50/50"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-[10px] font-bold uppercase tracking-tighter"
                                :class="selectedService?.id === service.id ? 'bg-rose-100 text-rose-700' : 'bg-gray-100 text-gray-500'">
                                {{ service.courier_name.substring(0, 3) }}
                            </div>

                            <div>
                                <p class="text-sm font-bold text-gray-800" :class="{'text-rose-700': selectedService?.id === service.id}">
                                    {{ service.service_type }}
                                </p>
                                <p class="text-[10px] text-gray-500">Est: {{ service.duration }}</p>
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <p class="text-sm font-bold text-gray-700" :class="{'text-rose-600': selectedService?.id === service.id}">
                                {{ formatCurrency(service.price) }}
                            </p>
                            <span v-if="selectedService?.id === service.id" class="text-[10px] text-rose-600 flex items-center justify-end gap-1">
                                Selected <Check class="w-3 h-3" />
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="isOpen" @click="isOpen = false" class="fixed inset-0 z-40 cursor-default bg-transparent"></div>
        </div>
    </div>
</template>

<style scoped>
/* Hide Scrollbar for Tabs but keep functionality */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* Custom Scrollbar for Dropdown List */
.custom-scrollbar::-webkit-scrollbar {
    width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f9fafb;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #fb7185; /* Rose-400 */
    border-radius: 10px;
}
</style>