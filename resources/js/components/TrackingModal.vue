<script setup>
import { computed } from 'vue';
import { 
    X, Truck, MapPin, CheckCircle, Clock, Package, 
    AlertCircle, Copy, Check, Plane, Building, ChevronDown 
} from 'lucide-vue-next';

const props = defineProps({
    show: Boolean,
    loading: Boolean,
    data: Object // Structure: { waybill, courier, history: [{ date, description, location }] }
});

const emit = defineEmits(['close']);

// --- Helper for Status Icons ---
const getStatusIcon = (description, index) => {
    const desc = description.toLowerCase();
    if (index === 0 && desc.includes('delivered')) return CheckCircle;
    if (desc.includes('delivered')) return CheckCircle;
    if (desc.includes('picked up')) return Package;
    if (desc.includes('departed')) return Plane;
    if (desc.includes('arrived')) return Building;
    if (desc.includes('delivery')) return Truck;
    return Clock; // Default
};

// --- Copy to Clipboard ---
const copyResi = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        alert('Waybill copied to clipboard!'); 
    } catch (err) {
        console.error('Failed to copy', err);
    }
};

// --- Date & Time Parser ---
const parseDateTime = (dateString) => {
    const [datePart, timePart] = dateString.split(' ');
    const dateObj = new Date(datePart);
    const formattedDate = dateObj.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
    
    return {
        date: formattedDate,
        time: timePart
    };
};
</script>

<template>
    <div v-if="show">
        
        <div class="fixed inset-0 z-[90] bg-black/60 backdrop-blur-sm transition-opacity" @click="emit('close')"></div>

        <div class="hidden md:flex fixed inset-0 z-[100] items-center justify-center p-4 animate-in fade-in duration-200 pointer-events-none">
            <div class="bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] border border-gray-100 ring-1 ring-gray-200 pointer-events-auto">
                
                <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-white sticky top-0 z-10">
                    <div>
                        <h3 class="font-bold text-gray-900 text-xl flex items-center gap-2">
                            Track Package
                            <span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 text-[10px] font-bold uppercase tracking-wider border border-rose-100">Live</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-1">Monitor your delivery status</p>
                    </div>
                    <button @click="emit('close')" class="p-2 text-gray-400 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-all duration-200">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-0 overflow-y-auto flex-1 bg-gray-50/50 scroll-smooth">
                    
                    <div v-if="loading" class="flex flex-col items-center justify-center py-20 space-y-4">
                        <div class="relative">
                            <div class="w-14 h-14 border-4 border-rose-100 border-t-rose-600 rounded-full animate-spin"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <Truck class="w-5 h-5 text-rose-600 animate-pulse" />
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 font-medium animate-pulse">Fetching shipment data...</p>
                    </div>

                    <div v-else-if="data?.error" class="p-12 text-center">
                        <div class="w-20 h-20 bg-red-50 rounded-3xl flex items-center justify-center mx-auto mb-5 border border-red-100 shadow-sm">
                            <AlertCircle class="w-10 h-10 text-red-500" />
                        </div>
                        <h4 class="text-gray-900 font-bold text-lg">Tracking Unavailable</h4>
                        <p class="text-sm text-gray-500 mt-2 max-w-xs mx-auto leading-relaxed">{{ data.error }}</p>
                    </div>

                    <div v-else-if="data?.history" class="p-6">
                        
                        <div class="mb-8 bg-white border border-gray-200 rounded-2xl p-5 shadow-sm relative overflow-hidden group">
                            <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                                <Truck class="w-24 h-24 text-rose-600 -rotate-12 transform translate-x-4 -translate-y-4" />
                            </div>
                            
                            <div class="grid grid-cols-2 gap-6 relative z-10">
                                <div>
                                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Courier</p>
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                                            <Package class="w-4 h-4 text-gray-600" />
                                        </div>
                                        <p class="font-bold text-gray-900 text-base">{{ data.courier || 'Standard' }}</p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider mb-1">Waybill Number</p>
                                    <div class="flex items-center gap-2 group/copy cursor-pointer" @click="copyResi(data.waybill)">
                                        <p class="font-mono font-bold text-rose-600 text-lg tracking-tight">{{ data.waybill }}</p>
                                        <Copy class="w-3.5 h-3.5 text-gray-400 group-hover/copy:text-rose-600 transition-colors" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="relative">
                            <div class="absolute left-[27px] top-4 bottom-4 w-0.5 bg-gray-200"></div>

                            <div class="space-y-6">
                                <div v-for="(log, index) in data.history" :key="index" class="relative flex gap-4 group">
                                    
                                    <div class="relative z-10 flex-shrink-0">
                                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center border-[3px] transition-all duration-300 shadow-sm"
                                            :class="index === 0 
                                                ? 'bg-rose-600 border-white text-white shadow-rose-200 scale-110' 
                                                : 'bg-white border-gray-100 text-gray-400 group-hover:border-rose-100 group-hover:text-rose-400'">
                                            <component :is="getStatusIcon(log.description, index)" :class="index === 0 ? 'w-6 h-6' : 'w-5 h-5'" />
                                        </div>
                                    </div>

                                    <div class="flex-1 py-1" :class="index === 0 ? 'opacity-100' : 'opacity-70 grayscale hover:grayscale-0 hover:opacity-100 transition-all'">
                                        <div :class="index === 0 ? 'bg-white p-4 rounded-xl border border-rose-100 shadow-sm' : ''">
                                            
                                            <div class="flex items-center justify-between mb-1.5">
                                                <p class="text-sm font-bold text-gray-900 leading-snug" 
                                                :class="{'text-rose-700 text-base': index === 0}">
                                                    {{ log.description }}
                                                </p>
                                            </div>

                                            <div class="flex flex-col sm:flex-row sm:items-center gap-y-1 gap-x-4 text-xs mt-1">
                                                <div class="flex items-center gap-1.5 text-gray-500 font-medium bg-gray-100 px-2 py-1 rounded-md w-fit">
                                                    <Clock class="w-3 h-3" />
                                                    {{ parseDateTime(log.date).time }}
                                                    <span class="text-gray-300">|</span>
                                                    {{ parseDateTime(log.date).date }}
                                                </div>

                                                <div class="flex items-center gap-1.5 text-gray-500">
                                                    <MapPin class="w-3.5 h-3.5 text-gray-400" /> 
                                                    <span class="font-medium">{{ log.location }}</span>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>

                <div class="px-6 py-4 bg-white border-t border-gray-100 flex justify-between items-center text-[10px] text-gray-400">
                    <p>Data updated in real-time (Simulation).</p>
                    <div class="flex items-center gap-1">
                        <CheckCircle class="w-3 h-3 text-emerald-500" />
                        <span>Verified</span>
                    </div>
                </div>
            </div>
        </div>


        <div class="md:hidden fixed inset-x-0 bottom-0 z-[100] flex flex-col max-h-[90vh] animate-in slide-in-from-bottom duration-300">
            <div class="bg-white rounded-t-[2rem] shadow-[0_-10px_40px_rgba(0,0,0,0.2)] flex flex-col flex-1 overflow-hidden ring-1 ring-black/5">
                
                <div class="w-full flex justify-center pt-3 pb-2 bg-white" @click="emit('close')">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                </div>

                <div class="px-5 pb-4 border-b border-gray-100 bg-white sticky top-0 z-10 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900 text-lg flex items-center gap-2">
                            Lacak Paket
                        </h3>
                        <p class="text-xs text-gray-500">Update status pengiriman</p>
                    </div>
                    <button @click="emit('close')" class="p-2 bg-gray-100 rounded-full text-gray-500">
                        <ChevronDown class="w-5 h-5" />
                    </button>
                </div>

                <div class="overflow-y-auto flex-1 bg-gray-50/50 p-5">
                    
                    <div v-if="loading" class="flex flex-col items-center justify-center py-10 space-y-3">
                        <div class="relative">
                            <div class="w-12 h-12 border-4 border-rose-100 border-t-rose-600 rounded-full animate-spin"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <Truck class="w-4 h-4 text-rose-600" />
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 font-medium">Memuat data...</p>
                    </div>

                    <div v-else-if="data?.error" class="py-10 text-center">
                        <AlertCircle class="w-12 h-12 text-red-500 mx-auto mb-3" />
                        <h4 class="font-bold text-gray-900">Gagal Memuat</h4>
                        <p class="text-xs text-gray-500 mt-1">{{ data.error }}</p>
                    </div>

                    <div v-else-if="data?.history">
                        
                        <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm mb-6 flex justify-between items-center">
                            <div>
                                <p class="text-[10px] text-gray-400 font-bold uppercase">Kurir</p>
                                <p class="font-bold text-gray-900 text-sm">{{ data.courier || 'JNE' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-gray-400 font-bold uppercase">No. Resi</p>
                                <div class="flex items-center justify-end gap-1.5" @click="copyResi(data.waybill)">
                                    <p class="font-mono font-bold text-rose-600 text-sm">{{ data.waybill }}</p>
                                    <Copy class="w-3 h-3 text-gray-400" />
                                </div>
                            </div>
                        </div>

                        <div class="relative pl-2">
                            <div class="absolute left-[19px] top-2 bottom-4 w-0.5 bg-gray-200"></div>

                            <div class="space-y-6">
                                <div v-for="(log, index) in data.history" :key="index" class="relative flex gap-4">
                                    
                                    <div class="relative z-10 flex-shrink-0">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center border-2 transition-all shadow-sm bg-white"
                                            :class="index === 0 ? 'border-rose-500 text-rose-600 ring-4 ring-rose-50' : 'border-gray-200 text-gray-400'">
                                            <component :is="getStatusIcon(log.description, index)" class="w-5 h-5" />
                                        </div>
                                    </div>

                                    <div class="flex-1 pt-0.5" :class="index === 0 ? 'opacity-100' : 'opacity-80'">
                                        <p class="text-sm font-bold text-gray-900 leading-snug mb-1" :class="{'text-rose-600': index === 0}">
                                            {{ log.description }}
                                        </p>
                                        <div class="flex flex-col gap-0.5">
                                            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                                <Clock class="w-3 h-3" />
                                                {{ parseDateTime(log.date).time }} - {{ parseDateTime(log.date).date }}
                                            </div>
                                            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                                <MapPin class="w-3 h-3" />
                                                {{ log.location }}
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background-color: #e2e8f0; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background-color: #cbd5e1; }
</style>