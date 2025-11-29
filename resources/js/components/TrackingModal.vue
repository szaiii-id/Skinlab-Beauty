<script setup>
import { X, Truck } from 'lucide-vue-next';

defineProps({
    show: Boolean,
    loading: Boolean,
    data: Object
});

const emit = defineEmits(['close']);
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
        
        <div class="absolute inset-0 bg-white/80 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

        <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl border border-gray-100 flex flex-col max-h-[85vh] animate-in zoom-in-95 duration-200 overflow-hidden">
            
            <div class="bg-gray-900 px-6 py-4 flex justify-between items-center text-white shrink-0">
                <h3 class="font-bold text-lg flex items-center gap-2">
                    <Truck class="w-5 h-5 text-rose-400" /> Track Package
                </h3>
                <button @click="$emit('close')" class="hover:bg-white/20 rounded-full p-1.5 transition-colors">
                    <X class="w-5 h-5"/>
                </button>
            </div>

            <div class="p-6 overflow-y-auto flex-1 bg-gray-50/50 custom-scrollbar">
                
                <div v-if="loading" class="py-12 text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-rose-600 mx-auto mb-3"></div>
                    <p class="text-gray-500 text-sm font-medium">Connecting to courier...</p>
                </div>

                <div v-else-if="data?.error" class="p-4 bg-red-50 text-red-600 rounded-xl text-center text-sm font-medium border border-red-100">
                    {{ data.error }}
                </div>

                <div v-else-if="data" class="space-y-6">
                    <div class="bg-white p-5 rounded-xl border border-gray-200 text-center shadow-sm">
                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-1">Tracking Number</p>
                        <p class="font-mono text-2xl font-bold text-gray-900 select-all tracking-wide">{{ data.airway_bill }}</p>
                    </div>
                    
                    <div class="relative pl-8 border-l-2 border-gray-200 space-y-8 ml-3 py-2">
                        <div v-for="(log, index) in data.history" :key="index" class="relative">
                            <div class="absolute -left-[39px] top-1.5 w-5 h-5 rounded-full border-4 border-white shadow-sm flex items-center justify-center z-10" 
                                 :class="index === 0 ? 'bg-green-500 ring-2 ring-green-100' : 'bg-gray-300'">
                            </div>
                            
                            <div>
                                <p class="text-xs text-gray-400 font-mono mb-1 font-medium">{{ log.date }}</p>
                                <p class="font-bold text-gray-800 text-sm leading-relaxed" :class="{'text-green-700': index === 0}">
                                    {{ log.desc }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
</style>