<script setup>
import { Check, Edit3, X, Trash2, Repeat } from 'lucide-vue-next';

const props = defineProps({
    group: Object
});

const emit = defineEmits(['toggle', 'edit', 'delete-single', 'delete-all']);

const formatTimeDisplay = (utcTime) => {
    if (!utcTime) return 'Flex';
    const date = new Date();
    const [h, m] = utcTime.split(':');
    date.setUTCHours(parseInt(h), parseInt(m));
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
};

// Helper: Fix Image URL
const getImageUrl = (path) => {
    if (!path) return null;
    if (path.startsWith('http')) return path; 
    return `/storage/${path}`; 
};

// --- HELPER BARU: Format Frekuensi ---
const getFrequencyLabel = (freq) => {
    if (freq === 1) return 'Daily';
    if (freq === 7) return 'Weekly';
    return `Every ${freq} Days`;
};
</script>

<template>
    <div class="bg-white rounded-2xl border border-gray-200 p-4 sm:p-5 shadow-sm transition-all hover:shadow-md relative group/card mb-4">
        
        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6">
            
            <div class="flex items-start gap-4 flex-1 w-full min-w-0">
                
                <div class="w-16 h-16 rounded-xl bg-gray-50 overflow-hidden shrink-0 border border-gray-100 relative mt-1">
                    <img 
                        v-if="group.image_url" 
                        :src="getImageUrl(group.image_url)" 
                        class="w-full h-full object-cover"
                        @error="$event.target.style.display='none'"
                    >
                    <div v-else class="w-full h-full flex items-center justify-center text-gray-300 text-[10px] font-bold">
                        IMG
                    </div>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mb-1">
                        <span v-if="group.brand_name" class="text-[10px] font-extrabold text-rose-500 uppercase tracking-widest truncate max-w-[120px]">
                            {{ group.brand_name }}
                        </span>
                        <span v-if="group.brand_name && group.category_name" class="text-gray-300 text-[10px] hidden sm:inline">•</span>
                        <span v-if="group.category_name" class="text-[10px] font-bold text-gray-400 uppercase tracking-wide truncate max-w-[100px]">
                            {{ group.category_name }}
                        </span>
                    </div>

                    <h3 class="text-sm font-bold text-gray-900 leading-tight mb-2 line-clamp-2">
                        {{ group.name || 'Unnamed Product' }}
                    </h3>

                    <div class="flex flex-wrap items-center gap-2">
                        
                        <div class="flex items-center gap-1 bg-blue-50 text-blue-600 px-2 py-0.5 rounded border border-blue-100">
                            <Repeat class="w-3 h-3" />
                            <span class="text-[9px] font-bold uppercase tracking-wide">
                                {{ getFrequencyLabel(group.repeat_frequency) }}
                            </span>
                        </div>

                        <span v-for="tag in (group.tags || []).slice(0, 2)" :key="tag" 
                              class="text-[9px] font-medium text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200 whitespace-nowrap">
                            {{ tag }}
                        </span>
                        
                        <span v-if="group.is_manual" class="text-[9px] font-bold text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200 uppercase whitespace-nowrap">Manual</span>
                        
                        <p v-if="group.note" class="text-xs text-gray-500 truncate flex items-center gap-1 max-w-[150px]">
                            <span class="w-1 h-1 bg-rose-400 rounded-full shrink-0"></span> 
                            <span class="italic truncate">{{ group.note }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="w-full sm:w-auto flex flex-col gap-2 shrink-0 border-t sm:border-t-0 sm:border-l border-gray-100 pt-3 sm:pt-0 sm:pl-5">
                
                <div 
                    v-for="slot in group.slots" 
                    :key="slot.id"
                    class="flex items-center justify-between bg-gray-50 rounded-lg border border-gray-200 overflow-hidden shadow-sm hover:shadow-md transition-all w-full sm:w-auto"
                    :class="{'border-emerald-200 bg-emerald-50': slot.is_completed_today}"
                >
                    <button 
                        @click="$emit('toggle', slot.id)"
                        class="flex-1 sm:flex-none flex items-center gap-3 px-3 py-2 transition-colors group/check sm:min-w-[110px]"
                        :class="slot.is_completed_today ? 'bg-emerald-50' : 'hover:bg-gray-100'"
                        title="Click to complete"
                    >
                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-all shrink-0"
                             :class="slot.is_completed_today ? 'border-emerald-500 bg-emerald-500' : 'border-gray-300 bg-white group-hover/check:border-rose-400'">
                            <Check v-if="slot.is_completed_today" class="w-2.5 h-2.5 text-white stroke-[3]" />
                        </div>
                        <span class="text-xs font-bold" :class="slot.is_completed_today ? 'text-emerald-700 line-through' : 'text-gray-700'">
                            {{ formatTimeDisplay(slot.reminder_time) }}
                        </span>
                    </button>

                    <div class="flex items-center border-l border-gray-200 h-full bg-white">
                        <button 
                            @click.stop="$emit('edit', slot, group.name)" 
                            class="px-2.5 py-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors h-full flex items-center justify-center border-r border-gray-100"
                            title="Edit Time"
                        >
                            <Edit3 class="w-3.5 h-3.5" />
                        </button>
                        <button 
                            @click.stop="$emit('delete-single', slot, group.name)" 
                            class="px-2.5 py-2 text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors h-full flex items-center justify-center"
                            title="Delete this time only"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="group.slots.length > 0" class="w-full sm:w-auto flex justify-center items-center border-t sm:border-t-0 sm:border-l border-gray-100 pt-4 sm:pt-0 sm:pl-6">
                
                <button 
                    @click="$emit('delete-all', group.slots[0], group.name)" 
                    class="group/del flex flex-col items-center justify-center gap-1 text-gray-300 hover:text-red-600 transition-all"
                    title="Delete Entire Product Routine"
                >
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-xl group-hover/del:bg-red-50 group-hover/del:border-red-200 transition-all shadow-sm">
                        <Trash2 class="w-6 h-6 stroke-[2]" />
                    </div>
                    
                    <span class="text-[9px] font-bold uppercase tracking-wider opacity-0 group-hover/del:opacity-100 transition-opacity hidden sm:block">
                        Delete All
                    </span>
                    <span class="text-[10px] font-bold sm:hidden mt-1">Delete All</span>
                </button>

            </div>

        </div>
    </div>
</template>