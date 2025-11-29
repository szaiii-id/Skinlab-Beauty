<script setup>
import { Check, Edit3, X, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    group: Object
});

const emit = defineEmits(['toggle', 'edit', 'delete-single', 'delete-all']);

const formatTimeDisplay = (utcTime) => {
    if (!utcTime) return 'Flex';
    const date = new Date();
    const [h, m] = utcTime.split(':');
    date.setUTCHours(parseInt(h), parseInt(m));
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm transition-shadow hover:shadow-md relative group/card">
        
        <button 
            v-if="group.slots.length > 0"
            @click="$emit('delete-all', group.slots[0], group.name)" 
            class="absolute top-4 right-4 text-gray-300 hover:text-red-500 p-2 rounded-full hover:bg-red-50 transition-all z-10"
            title="Delete all schedules for this product"
        >
            <Trash2 class="w-5 h-5" />
        </button>

        <div class="flex items-start gap-4">
            <div class="w-14 h-14 rounded-xl bg-gray-100 overflow-hidden shrink-0 border border-gray-100 self-center">
                <img v-if="group.image_url" :src="group.image_url" class="w-full h-full object-cover">
                <div v-else class="w-full h-full flex items-center justify-center text-gray-300 text-xs font-bold">IMG</div>
            </div>

            <div class="flex-1 min-w-0 self-center pr-8">
                <h3 class="text-sm font-bold text-gray-900 leading-tight mb-1 line-clamp-2">
                    {{ group.name }}
                </h3>
                <div class="flex flex-wrap items-center gap-2">
                    <span v-if="group.is_manual" class="text-[10px] text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">Manual</span>
                    <p v-if="group.note" class="text-xs text-gray-500 truncate">{{ group.note }}</p>
                </div>
            </div>

            <div class="flex flex-col gap-2 shrink-0 items-end pl-2 border-l border-gray-100 mt-2 sm:mt-0">
                <div 
                    v-for="slot in group.slots" 
                    :key="slot.id"
                    class="flex items-center bg-gray-50 rounded-lg border border-gray-200 overflow-hidden shadow-sm transition-all hover:shadow-md group/timebtn"
                    :class="{'border-green-200 bg-green-50': slot.is_completed_today}"
                >
                    <button 
                        @click="$emit('toggle', slot.id)"
                        class="flex items-center gap-2 px-3 py-1.5 hover:bg-black/5 transition-colors group/check"
                        title="Click to complete"
                    >
                        <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-colors"
                             :class="slot.is_completed_today ? 'border-green-500 bg-green-500' : 'border-gray-300 bg-white group-hover/check:border-rose-400'">
                            <Check v-if="slot.is_completed_today" class="w-2.5 h-2.5 text-white stroke-[3]" />
                        </div>
                        <span class="text-xs font-bold" :class="slot.is_completed_today ? 'text-green-700' : 'text-gray-700'">
                            {{ formatTimeDisplay(slot.reminder_time) }}
                        </span>
                    </button>

                    <div class="w-px h-4 bg-gray-300 mx-0.5"></div>

                    <button 
                        @click.stop="$emit('edit', slot, group.name)"
                        class="px-2 py-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                        title="Edit Schedule"
                    >
                        <Edit3 class="w-3.5 h-3.5" />
                    </button>

                    <div class="w-px h-4 bg-gray-300 mx-0.5"></div>

                    <button 
                        @click.stop="$emit('delete-single', slot, group.name)" 
                        class="px-2 py-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors" 
                        title="Delete Schedule"
                    >
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>