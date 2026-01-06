<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { X, Search, PenTool, Plus, Minus, Trash2, Clock, Calendar, ChevronDown } from 'lucide-vue-next';

const props = defineProps({
    show: Boolean,
    isEdit: Boolean,
    editingId: Number,
    storeProducts: Array,
    initialData: Object
});

const emit = defineEmits(['close', 'delete']);

const form = useForm({
    product_id: null,
    custom_product_name: null,
    step_order: 1,
    note: '',
    reminder_times: [''], 
    is_reminder_active: false,
    repeat_frequency: 1,
    timezone_input: Intl.DateTimeFormat().resolvedOptions().timeZone
});

// State
const searchQuery = ref('');
const isDropdownOpen = ref(false);
const selectedStoreProduct = ref(null);
const isCustomRepeat = ref(false); 

const filteredProducts = computed(() => {
    if (!searchQuery.value) return [];
    const query = searchQuery.value.toLowerCase();
    return props.storeProducts.filter(p => 
        p.name.toLowerCase().includes(query) || 
        (p.brand_name && p.brand_name.toLowerCase().includes(query))
    ).slice(0, 5);
});

// --- WATCHERS & INITIALIZATION ---
watch(() => props.show, (newVal) => {
    if (newVal) {
        if (props.isEdit && props.initialData) {
            // --- EDIT MODE ---
            const item = props.initialData;
            
            form.clearErrors();
            form.step_order = item.step_order;
            form.note = item.note || '';
            form.is_reminder_active = !!item.is_reminder_active;
            form.repeat_frequency = item.repeat_frequency || 1;
            form.timezone_input = item.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone;
            
            isCustomRepeat.value = form.repeat_frequency > 1;

            // --- TIME FORMATTER ---
            if (item.reminder_time) {
                const date = new Date();
                const [h, m] = item.reminder_time.split(':');
                date.setUTCHours(parseInt(h), parseInt(m));
                const localHours = date.getHours().toString().padStart(2, '0');
                const localMinutes = date.getMinutes().toString().padStart(2, '0');
                form.reminder_times = [`${localHours}:${localMinutes}`];
            } else {
                form.reminder_times = [''];
            }

            // Product Handling
            if (item.product_id) {
                const product = props.storeProducts.find(p => p.id === item.product_id);
                selectedStoreProduct.value = product || { 
                    name: item.name, 
                    image_url: item.image_url, 
                    brand_name: item.brand_name 
                };
                form.product_id = item.product_id;
            } else {
                selectedStoreProduct.value = null;
                form.product_id = null;
                form.custom_product_name = item.name;
                searchQuery.value = item.name;
            }
        } else {
            // --- CREATE MODE ---
            form.reset();
            form.clearErrors();
            form.reminder_times = [''];
            searchQuery.value = '';
            selectedStoreProduct.value = null;
            isCustomRepeat.value = false;
        }
    }
});

// Auto toggle reminder checkbox
watch(() => form.reminder_times, (val) => {
    const hasTime = val.some(t => t && t.trim() !== '');
    if (hasTime && !form.is_reminder_active) {
        form.is_reminder_active = true;
    }
}, { deep: true });

// --- METHODS ---
const addTimeSlot = () => form.reminder_times.push('');
const removeTimeSlot = (idx) => form.reminder_times.splice(idx, 1);

const selectStoreProduct = (product) => {
    form.product_id = product.id;
    form.custom_product_name = null;
    selectedStoreProduct.value = product;
    searchQuery.value = '';
    isDropdownOpen.value = false;
};

const clearSelectedProduct = () => {
    form.product_id = null;
    selectedStoreProduct.value = null;
    searchQuery.value = '';
};

const toggleRepeatMode = (mode) => {
    if (mode === 'daily') {
        form.repeat_frequency = 1;
        isCustomRepeat.value = false;
    } else {
        isCustomRepeat.value = true;
        if (form.repeat_frequency === 1) form.repeat_frequency = 2;
    }
};

const submit = () => {
    if (!selectedStoreProduct.value) {
        form.product_id = null;
        form.custom_product_name = searchQuery.value;
    }
    
    form.reminder_times = form.reminder_times.filter(t => t !== '');

    const options = {
        onSuccess: () => emit('close'),
        preserveScroll: true
    };

    if (props.isEdit && props.editingId) {
        form.put(`/routine/${props.editingId}`, options);
    } else {
        form.post('/routine', options);
    }
};
</script>

<template>
    <div v-if="show">
        
        <div class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

        <div class="hidden md:flex fixed inset-0 z-[101] items-center justify-center p-4 pointer-events-none">
            <div class="bg-white rounded-[2rem] w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-300 border border-white/20 pointer-events-auto">
                
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-white shrink-0">
                    <div>
                        <h3 class="font-black text-xl text-gray-900 flex items-center gap-2 tracking-tight">
                            {{ isEdit ? 'Edit Routine' : 'Add Routine' }}
                        </h3>
                        <p class="text-xs text-gray-400 font-medium mt-0.5">Build your glow habits</p>
                    </div>
                    <button @click="$emit('close')" class="bg-gray-50 hover:bg-gray-100 p-2.5 rounded-full transition-colors text-gray-400 hover:text-rose-500">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 space-y-7 overflow-y-auto custom-scrollbar bg-white">
                    
                    <div :class="{'opacity-75 pointer-events-none': isEdit}">
                        <label class="block text-xs font-extrabold text-gray-700 uppercase tracking-widest mb-2 ml-1">Product</label>
                        
                        <div v-if="selectedStoreProduct" class="flex items-center gap-4 p-3 bg-rose-50/50 border border-rose-200 rounded-2xl relative group shadow-sm">
                             <div class="w-14 h-14 bg-white rounded-xl overflow-hidden border border-rose-100 shrink-0">
                                <img 
                                    v-if="selectedStoreProduct.image_url" 
                                    :src="selectedStoreProduct.image_url.startsWith('http') ? selectedStoreProduct.image_url : `/storage/${selectedStoreProduct.image_url}`" 
                                    class="w-full h-full object-cover"
                                >
                             </div>
                             <div class="flex-1 min-w-0">
                                <p class="text-[10px] text-rose-500 font-black uppercase tracking-wide">{{ selectedStoreProduct.brand_name || 'Brand' }}</p>
                                <p class="text-sm font-bold text-gray-900 truncate">{{ selectedStoreProduct.name }}</p>
                             </div>
                             <button v-if="!isEdit" @click="clearSelectedProduct" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-all"><X class="w-4 h-4" /></button>
                        </div>

                        <div v-else class="relative group">
                            <Search class="absolute left-4 top-3.5 w-5 h-5 text-gray-400 group-focus-within:text-rose-500 transition-colors z-10" />
                            
                            <input 
                                v-model="searchQuery" 
                                @focus="isDropdownOpen = true" 
                                type="text" 
                                placeholder="Type product name..." 
                                class="w-full pl-12 pr-4 py-3.5 rounded-2xl border border-gray-200 bg-gray-50 text-gray-900 placeholder-gray-400 focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 shadow-sm transition-all font-medium"
                            >
                            
                            <div v-if="searchQuery && isDropdownOpen && filteredProducts.length > 0" class="absolute z-20 w-full mt-2 bg-white border border-gray-100 rounded-2xl shadow-xl max-h-56 overflow-y-auto custom-scrollbar">
                                <button v-for="p in filteredProducts" :key="p.id" @click="selectStoreProduct(p)" class="w-full flex items-center gap-3 p-3 hover:bg-rose-50 text-left border-b border-gray-50 transition-colors last:border-0">
                                    <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 border border-gray-100">
                                        <img 
                                            v-if="p.image_url" 
                                            :src="p.image_url.startsWith('http') ? p.image_url : `/storage/${p.image_url}`" 
                                            class="w-full h-full object-cover"
                                        >
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] text-rose-500 font-bold uppercase">{{ p.brand_name }}</p>
                                        <p class="text-xs font-bold text-gray-900 truncate">{{ p.name }}</p>
                                    </div>
                                </button>
                            </div>

                            <div v-if="searchQuery && !selectedStoreProduct" class="mt-2 flex items-center gap-2 text-xs text-rose-600 bg-rose-50 p-3 rounded-xl border border-rose-100">
                                <PenTool class="w-3.5 h-3.5" /> Use manual name: <b class="text-gray-900">"{{ searchQuery }}"</b>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-gray-700 uppercase tracking-widest mb-3 ml-1">Frequency</label>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <button 
                                type="button"
                                @click="toggleRepeatMode('daily')"
                                class="py-3 px-4 rounded-xl border-2 font-bold text-sm transition-all flex items-center justify-center gap-2"
                                :class="!isCustomRepeat ? 'border-rose-500 bg-rose-50 text-rose-600' : 'border-gray-200 bg-gray-50 text-gray-500 hover:bg-gray-100 hover:border-gray-300'"
                            >
                                <Calendar class="w-4 h-4" /> Daily
                            </button>
                            <button 
                                type="button"
                                @click="toggleRepeatMode('custom')"
                                class="py-3 px-4 rounded-xl border-2 font-bold text-sm transition-all flex items-center justify-center gap-2"
                                :class="isCustomRepeat ? 'border-rose-500 bg-rose-50 text-rose-600' : 'border-gray-200 bg-gray-50 text-gray-500 hover:bg-gray-100 hover:border-gray-300'"
                            >
                                <Clock class="w-4 h-4" /> Interval
                            </button>
                        </div>

                        <div v-if="isCustomRepeat" class="bg-rose-50/50 p-4 rounded-xl border border-rose-100 shadow-sm animate-in slide-in-from-top-2">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-bold text-gray-700">Every</span>
                                <input 
                                    v-model="form.repeat_frequency" 
                                    type="number" 
                                    min="2" 
                                    class="w-20 text-center font-bold text-lg border border-gray-300 bg-white text-gray-900 rounded-lg focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20"
                                >
                                <span class="text-sm font-bold text-gray-700">Days</span>
                            </div>
                            <p class="text-[10px] text-gray-500 mt-2 font-medium">Example: 2 = Every other day (Skin Cycling)</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold text-gray-700 uppercase tracking-widest mb-3 ml-1">Reminder Time</label>
                        
                        <div class="space-y-3">
                            <div v-for="(time, index) in form.reminder_times" :key="index" class="flex gap-2 items-center group">
                                <div class="relative flex-1">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 bg-gray-200 p-1 rounded-md">
                                        <Clock class="w-3.5 h-3.5" />
                                    </div>
                                    <input 
                                        v-model="form.reminder_times[index]" 
                                        type="time" 
                                        class="w-full rounded-xl border border-gray-200 bg-gray-50 text-gray-900 pl-12 pr-4 py-3 shadow-sm font-bold focus:bg-white focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20 cursor-pointer transition-all"
                                    >
                                </div>
                                
                                <button 
                                    v-if="form.reminder_times.length > 1" 
                                    @click="removeTimeSlot(index)" 
                                    type="button" 
                                    class="w-11 h-11 flex items-center justify-center bg-gray-50 border border-gray-200 rounded-xl text-gray-400 hover:text-red-500 hover:bg-red-50 hover:border-red-200 transition-all shadow-sm"
                                >
                                    <Minus class="w-5 h-5"/>
                                </button>
                            </div>

                            <button 
                                @click="addTimeSlot" 
                                type="button" 
                                class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-gray-500 font-bold text-xs hover:border-rose-400 hover:text-rose-500 hover:bg-rose-50 transition-all flex items-center justify-center gap-2 mt-2 group"
                            >
                                <span class="bg-gray-200 group-hover:bg-rose-100 p-1 rounded-md transition-colors text-gray-600 group-hover:text-rose-600"><Plus class="w-3 h-3" /></span>
                                Add Another Time
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-1">
                            <label class="block text-[10px] font-extrabold text-gray-500 uppercase mb-2 ml-1">Step</label>
                            <input v-model="form.step_order" type="number" min="1" class="w-full rounded-xl border border-gray-200 bg-gray-50 text-gray-900 py-3 px-4 font-bold text-center shadow-sm focus:bg-white focus:ring-2 focus:ring-rose-500/20 text-lg">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[10px] font-extrabold text-gray-500 uppercase mb-2 ml-1">Note</label>
                            <input v-model="form.note" type="text" placeholder="e.g. 2 pumps" class="w-full rounded-xl border border-gray-200 bg-gray-50 text-gray-900 py-3 px-4 font-medium placeholder-gray-400 shadow-sm focus:bg-white focus:ring-2 focus:ring-rose-500/20">
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4 border-t border-gray-100 mt-4">
                        <button v-if="isEdit" type="button" @click="$emit('delete')" class="w-14 flex items-center justify-center bg-white text-red-500 rounded-2xl border-2 border-red-100 hover:bg-red-50 hover:border-red-200 transition-colors shadow-sm">
                            <Trash2 class="w-5 h-5" />
                        </button>
                        
                        <button 
                            @click="submit" 
                            :disabled="form.processing || (!selectedStoreProduct && !searchQuery)" 
                            class="flex-1 bg-gradient-to-r from-rose-500 to-pink-600 text-white py-4 rounded-2xl font-bold text-sm hover:shadow-lg hover:shadow-rose-500/30 hover:scale-[1.02] active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none disabled:shadow-none transition-all"
                        >
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>{{ isEdit ? 'Save Changes' : 'Add to Routine' }}</span>
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <div class="md:hidden fixed inset-x-0 bottom-0 z-[101] flex flex-col max-h-[95vh] animate-in slide-in-from-bottom duration-300">
            <div class="bg-white rounded-t-[2rem] shadow-[0_-10px_40px_rgba(0,0,0,0.2)] flex flex-col flex-1 overflow-hidden ring-1 ring-black/5">
                
                <div class="w-full flex justify-center pt-3 pb-2 bg-white" @click="$emit('close')">
                    <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
                </div>

                <div class="px-6 pb-4 border-b border-gray-100 bg-white sticky top-0 z-10 flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900 text-xl flex items-center gap-2">
                            {{ isEdit ? 'Edit Routine' : 'Add Routine' }}
                        </h3>
                        <p class="text-xs text-gray-500">Manage your skincare</p>
                    </div>
                    <button @click="$emit('close')" class="p-2 bg-gray-100 rounded-full text-gray-500 hover:bg-gray-200">
                        <ChevronDown class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 space-y-6 overflow-y-auto flex-1 bg-white pb-32">
                    
                    <div class="bg-white" :class="{'opacity-75 pointer-events-none': isEdit}">
                        <label class="block text-sm font-bold text-gray-900 mb-2">Product Name</label>
                        
                        <div v-if="selectedStoreProduct" class="flex items-center gap-3 p-3 bg-rose-50 border border-rose-100 rounded-2xl relative">
                             <div class="w-14 h-14 bg-white rounded-xl overflow-hidden border border-rose-100 shrink-0">
                                <img 
                                    v-if="selectedStoreProduct.image_url" 
                                    :src="selectedStoreProduct.image_url.startsWith('http') ? selectedStoreProduct.image_url : `/storage/${selectedStoreProduct.image_url}`" 
                                    class="w-full h-full object-cover"
                                >
                             </div>
                             <div class="flex-1 min-w-0">
                                <p class="text-[10px] text-rose-500 font-bold uppercase">{{ selectedStoreProduct.brand_name || 'Brand' }}</p>
                                <p class="text-sm font-bold text-gray-900 truncate">{{ selectedStoreProduct.name }}</p>
                             </div>
                             <button v-if="!isEdit" @click="clearSelectedProduct" class="p-2 bg-white text-gray-400 rounded-full shadow-sm"><X class="w-4 h-4" /></button>
                        </div>

                        <div v-else class="relative">
                            <input 
                                v-model="searchQuery" 
                                @focus="isDropdownOpen = true" 
                                type="text" 
                                placeholder="Search or type name..." 
                                class="w-full pl-4 pr-4 py-3.5 rounded-xl border border-gray-300 text-gray-900 placeholder:text-gray-400 focus:border-rose-500 focus:ring-rose-500 bg-white"
                            >
                            
                            <div v-if="searchQuery && isDropdownOpen && filteredProducts.length > 0" class="mt-2 bg-white border border-gray-100 rounded-xl shadow-xl max-h-48 overflow-y-auto z-50 absolute w-full">
                                <button v-for="p in filteredProducts" :key="p.id" @click="selectStoreProduct(p)" class="w-full flex items-center gap-3 p-3 border-b border-gray-50 last:border-0 text-left bg-white">
                                    <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                        <img v-if="p.image_url" :src="p.image_url.startsWith('http') ? p.image_url : `/storage/${p.image_url}`" class="w-full h-full object-cover rounded">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-[10px] font-bold uppercase text-gray-500">{{ p.brand_name }}</p>
                                        <p class="text-sm font-bold text-gray-900 truncate">{{ p.name }}</p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-3">Frequency</label>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <button type="button" @click="toggleRepeatMode('daily')" class="py-3 rounded-xl border-2 font-bold text-sm flex items-center justify-center gap-2" :class="!isCustomRepeat ? 'border-rose-500 bg-rose-50 text-rose-600' : 'border-gray-200 bg-white text-gray-500'">
                                <Calendar class="w-4 h-4" /> Daily
                            </button>
                            <button type="button" @click="toggleRepeatMode('custom')" class="py-3 rounded-xl border-2 font-bold text-sm flex items-center justify-center gap-2" :class="isCustomRepeat ? 'border-rose-500 bg-rose-50 text-rose-600' : 'border-gray-200 bg-white text-gray-500'">
                                <Clock class="w-4 h-4" /> Interval
                            </button>
                        </div>
                        <div v-if="isCustomRepeat" class="flex items-center gap-3 bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <span class="text-sm font-bold text-gray-700">Every</span>
                            <input v-model="form.repeat_frequency" type="number" min="2" class="w-20 text-center font-bold text-lg border-gray-300 rounded-lg py-2 text-gray-900 bg-white">
                            <span class="text-sm font-bold text-gray-700">Days</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-900 mb-2">Reminder Time</label>
                        <div v-for="(time, index) in form.reminder_times" :key="index" class="flex gap-2 mb-3">
                            <input v-model="form.reminder_times[index]" type="time" class="flex-1 rounded-xl border-gray-300 text-gray-900 font-bold py-3 px-4 bg-white focus:border-rose-500">
                            <button v-if="form.reminder_times.length > 1" @click="removeTimeSlot(index)" class="p-3 bg-red-50 text-red-500 rounded-xl border border-red-100"><Minus class="w-5 h-5"/></button>
                        </div>
                        <button @click="addTimeSlot" class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-gray-500 font-bold text-sm flex items-center justify-center gap-2 hover:border-rose-400 hover:text-rose-500"><Plus class="w-4 h-4" /> Add Time</button>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-1">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Step</label>
                            <input v-model="form.step_order" type="number" class="w-full rounded-xl border-gray-300 text-center font-bold text-lg py-3 text-gray-900 bg-white">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Note</label>
                            <input v-model="form.note" type="text" placeholder="e.g. 2 pumps" class="w-full rounded-xl border-gray-300 text-sm py-3 px-4 text-gray-900 bg-white placeholder:text-gray-400">
                        </div>
                    </div>

                </div>

                <div class="p-5 bg-white border-t border-gray-100 flex gap-3 shadow-[0_-4px_20px_rgba(0,0,0,0.05)]">
                    <button v-if="isEdit" type="button" @click="$emit('delete')" class="p-4 bg-red-50 text-red-600 rounded-2xl border border-red-100">
                        <Trash2 class="w-6 h-6" />
                    </button>
                    <button @click="submit" :disabled="form.processing || (!selectedStoreProduct && !searchQuery)" class="flex-1 py-4 bg-gradient-to-r from-rose-500 to-pink-600 text-white rounded-2xl font-bold text-base shadow-lg shadow-rose-200 active:scale-95 transition-all disabled:opacity-50">
                        <span v-if="form.processing">Saving...</span>
                        <span v-else>{{ isEdit ? 'Save Changes' : 'Add to Routine' }}</span>
                    </button>
                </div>

            </div>
        </div>

    </div>
</template>

<style scoped>
/* Scrollbar styling for desktop */
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background-color: #d1d5db; }
</style>