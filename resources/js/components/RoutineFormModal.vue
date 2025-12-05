<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { X, Search, Bell, PenTool, Plus, Minus, Trash2, Clock, Calendar, ChevronDown } from 'lucide-vue-next';

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
            // --- MODE EDIT ---
            const item = props.initialData;
            
            form.clearErrors();
            form.step_order = item.step_order;
            form.note = item.note || '';
            form.is_reminder_active = !!item.is_reminder_active;
            form.repeat_frequency = item.repeat_frequency || 1;
            form.timezone_input = item.timezone || Intl.DateTimeFormat().resolvedOptions().timeZone;
            
            isCustomRepeat.value = form.repeat_frequency > 1;

            // --- [KEMBALI KE LOGIC BENAR] FORMATTER WAKTU SESUAI DOMISILI ---
            if (item.reminder_time) {
                const date = new Date();
                const [h, m] = item.reminder_time.split(':');
                
                // 1. Set waktu berdasarkan data DB (UTC)
                date.setUTCHours(parseInt(h), parseInt(m));
                
                // 2. Ambil Jam & Menit Lokal User (Agar sesuai domisili)
                const localHours = date.getHours().toString().padStart(2, '0');
                const localMinutes = date.getMinutes().toString().padStart(2, '0');
                
                // 3. Masukkan ke Form
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
            // --- MODE CREATE ---
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
        // Saat Edit, backend Laravel 'update' biasanya mengharapkan data tunggal.
        // Jika Anda ingin fitur "Tambah Waktu saat Edit", idealnya endpoint backend harus support update massal (group).
        // TAPI, agar tidak error sekarang, kita kirim data seperti create (array reminder_times) 
        // DAN backend harus siap menerimanya.
        // Jika backend 'update' hanya menerima 1 waktu, kode ini akan mengupdate waktu item yang sedang diedit saja (index 0).
        
        // Jika user menambah waktu baru di mode edit, logic backend harus handle create baru.
        // Untuk amannya, kita post ke endpoint update, tapi backend logic Anda perlu dipastikan.
        
        // Sesuai permintaan "bisa tambahkan waktu lain", saya kirim array penuh.
        form.put(`/routine/${props.editingId}`, options);
    } else {
        form.post('/routine', options);
    }
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-[2rem] w-full max-w-md shadow-2xl overflow-hidden flex flex-col max-h-[90vh] animate-in fade-in zoom-in duration-300 border border-white/20">
            
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
</template>