<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { X, Search, Bell, PenTool, Plus, Minus, Trash2 } from 'lucide-vue-next';

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
    reminder_time: '',
    is_reminder_active: false,
    repeat_frequency: 1,
    timezone_input: Intl.DateTimeFormat().resolvedOptions().timeZone
});

// State
const searchQuery = ref('');
const isDropdownOpen = ref(false);
const selectedStoreProduct = ref(null);

const timezones = [
    { label: 'WIB (Jakarta)', value: 'Asia/Jakarta' },
    { label: 'WITA (Makassar)', value: 'Asia/Makassar' },
    { label: 'WIT (Jayapura)', value: 'Asia/Jayapura' },
];

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
            // Populate Edit
            const item = props.initialData;
            form.step_order = item.step_order;
            form.note = item.note || '';
            form.is_reminder_active = !!item.is_reminder_active;
            form.repeat_frequency = item.repeat_frequency || 1;
            
            // Time Handling
            if (item.reminder_time) {
                // Convert UTC to Local for Input
                const date = new Date();
                const [h, m] = item.reminder_time.split(':');
                date.setUTCHours(parseInt(h), parseInt(m));
                form.reminder_time = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
            } else {
                form.reminder_time = '';
            }
            form.reminder_times = []; // Single edit mode

            // Product Handling
            if (item.product_id) {
                const product = props.storeProducts.find(p => p.id === item.product_id);
                selectedStoreProduct.value = product || { name: item.name, image_url: item.image_url, brand_name: item.brand_name };
                form.product_id = item.product_id;
            } else {
                selectedStoreProduct.value = null;
                form.product_id = null;
                form.custom_product_name = item.name;
                searchQuery.value = item.name;
            }
        } else {
            // Reset Create
            form.reset();
            form.reminder_times = [''];
            searchQuery.value = '';
            selectedStoreProduct.value = null;
        }
    }
});

// Auto toggle reminder
watch(() => form.reminder_times, (val) => {
    if (val.some(t => t) && !form.is_reminder_active) form.is_reminder_active = true;
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

const submit = () => {
    if (!selectedStoreProduct.value) {
        form.product_id = null;
        form.custom_product_name = searchQuery.value;
    }

    if (props.isEdit && props.editingId) {
        form.put(`/routine/${props.editingId}`, {
            onSuccess: () => emit('close'),
            preserveScroll: true
        });
    } else {
        form.post('/routine', {
            onSuccess: () => emit('close'),
            preserveScroll: true
        });
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            
            <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-white shrink-0">
                <h3 class="font-bold text-lg text-gray-900">
                    {{ isEdit ? 'Edit Routine' : 'Add Routine' }}
                </h3>
                <button @click="$emit('close')" class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors text-gray-600 hover:text-red-500">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="p-6 space-y-6 overflow-y-auto">
                
                <div :class="{'opacity-75 pointer-events-none': isEdit}">
                    <label class="block text-sm font-bold text-gray-900 mb-2">Product Name</label>
                    
                    <div v-if="selectedStoreProduct" class="flex items-center gap-3 p-3 border border-rose-200 bg-rose-50/50 rounded-xl relative group">
                         <div class="w-12 h-12 bg-white rounded-lg overflow-hidden border border-rose-100 shrink-0">
                             <img v-if="selectedStoreProduct.image_url" :src="selectedStoreProduct.image_url" class="w-full h-full object-cover">
                         </div>
                         <div class="flex-1 min-w-0">
                            <p class="text-[10px] text-rose-500 font-bold uppercase">{{ selectedStoreProduct.brand_name || 'Brand' }}</p>
                            <p class="text-sm font-bold text-gray-900 truncate">{{ selectedStoreProduct.name }}</p>
                         </div>
                         <button v-if="!isEdit" @click="clearSelectedProduct" class="p-1.5 text-gray-400 hover:text-red-500 bg-white rounded-full shadow-sm hover:shadow transition-all"><X class="w-4 h-4" /></button>
                    </div>

                    <div v-else class="relative">
                        <Search class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" />
                        <input 
                            v-model="searchQuery" 
                            @focus="isDropdownOpen = true" 
                            type="text" 
                            placeholder="Search catalog or type manual..." 
                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:border-rose-500 focus:ring-rose-500 shadow-sm transition-shadow"
                        >
                        
                        <div v-if="searchQuery && isDropdownOpen && filteredProducts.length > 0" class="absolute z-20 w-full mt-2 bg-white border border-gray-100 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                            <button v-for="p in filteredProducts" :key="p.id" @click="selectStoreProduct(p)" class="w-full flex items-center gap-3 p-3 hover:bg-rose-50 text-left border-b border-gray-50 transition-colors">
                                <div class="w-8 h-8 bg-gray-100 rounded overflow-hidden flex-shrink-0">
                                    <img v-if="p.image_url" :src="p.image_url" class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-gray-500 font-bold">{{ p.brand_name }}</p>
                                    <p class="text-sm font-bold text-gray-900 truncate">{{ p.name }}</p>
                                </div>
                            </button>
                        </div>

                        <div v-if="searchQuery && !selectedStoreProduct" class="mt-2 flex items-center gap-2 text-xs text-rose-500 bg-rose-50 p-2 rounded-lg border border-rose-100">
                            <PenTool class="w-3 h-3" /><span>Save as manual: <b class="text-gray-900">"{{ searchQuery }}"</b></span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-bold text-gray-800 flex items-center gap-2">
                            <Bell class="w-4 h-4 text-rose-500" /> Reminder
                        </label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" v-model="form.is_reminder_active" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:bg-rose-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                        </label>
                    </div>

                    <div v-if="form.is_reminder_active" class="space-y-4 animate-in fade-in slide-in-from-top-2">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Timezone</label>
                                <select v-model="form.timezone_input" class="w-full rounded-lg border-gray-300 text-gray-900 bg-white text-sm py-2 px-3 focus:border-rose-500 shadow-sm">
                                    <option v-for="tz in timezones" :key="tz.value" :value="tz.value">{{ tz.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-1">Repeat</label>
                                <select v-model="form.repeat_frequency" class="w-full rounded-lg border-gray-300 text-gray-900 bg-white text-sm py-2 px-3 focus:border-rose-500 shadow-sm">
                                    <option :value="1">Daily</option>
                                    <option :value="2">Every 2 Days</option>
                                    <option :value="7">Weekly</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-600 mb-2">What time?</label>
                            
                            <div v-if="!isEdit" class="space-y-2">
                                <div v-for="(time, index) in form.reminder_times" :key="index" class="flex gap-2">
                                    <input v-model="form.reminder_times[index]" type="time" class="flex-1 rounded-lg border-gray-300 text-gray-900 bg-white focus:border-rose-500 py-2 px-3 shadow-sm">
                                    <button v-if="index > 0" @click="removeTimeSlot(index)" type="button" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                        <Minus class="w-4 h-4"/>
                                    </button>
                                </div>
                                <button @click="addTimeSlot" type="button" class="text-xs text-rose-600 font-bold hover:underline flex items-center gap-1 mt-2">
                                    <Plus class="w-3 h-3" /> Add Another Time
                                </button>
                            </div>

                            <div v-else>
                                <input v-model="form.reminder_time" type="time" class="w-full rounded-lg border-gray-300 text-gray-900 bg-white focus:border-rose-500 py-2 px-3 shadow-sm">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-1">
                        <label class="block text-sm font-bold text-gray-900 mb-2">Step Order</label>
                        <input v-model="form.step_order" type="number" min="1" class="w-full rounded-xl border-gray-300 text-gray-900 bg-white py-2.5 px-3 focus:ring-rose-500 shadow-sm">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-bold text-gray-900 mb-2">Note (Optional)</label>
                        <input v-model="form.note" type="text" placeholder="e.g. 2 pumps" class="w-full rounded-xl border-gray-300 text-gray-900 bg-white py-2.5 px-3 placeholder-gray-400 focus:ring-rose-500 shadow-sm">
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button v-if="isEdit" @click="$emit('delete')" type="button" class="px-5 py-3 bg-red-50 text-red-600 rounded-xl font-bold hover:bg-red-100 transition-colors border border-red-100">
                        <Trash2 class="w-5 h-5" />
                    </button>
                    <button @click="submit" :disabled="form.processing || (!selectedStoreProduct && !searchQuery)" class="flex-1 bg-rose-600 text-white py-3 rounded-xl font-bold hover:bg-rose-700 disabled:opacity-50 shadow-lg shadow-rose-200 transition-all">
                        {{ isEdit ? 'Save Changes' : 'Save Routine' }}
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>