<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { ref, computed, onMounted, watch } from 'vue';
import { 
    Plus, Trash2, Check, Circle, 
    Search, X, Bell, Globe, Repeat, Edit3, Clock, ChevronDown, PenTool, Minus
} from 'lucide-vue-next';
import { requestPermission } from '@/firebase';

defineOptions({ layout: DashboardLayout });

const props = defineProps<{
    routines: any[],
    storeProducts: any[]
}>();

onMounted(() => requestPermission());

// --- HELPERS ---
const browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
const timezones = [
    { label: 'WIB (Jakarta)', value: 'Asia/Jakarta' },
    { label: 'WITA (Makassar/Bali)', value: 'Asia/Makassar' },
    { label: 'WIT (Jayapura)', value: 'Asia/Jayapura' },
];

const utcToLocalInput = (utcTime: string) => {
    if (!utcTime) return '';
    const date = new Date();
    const [h, m] = utcTime.split(':');
    date.setUTCHours(parseInt(h), parseInt(m));
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
};

const formatTimeDisplay = (utcTime: string) => {
    if (!utcTime) return 'Flex';
    const date = new Date();
    const [h, m] = utcTime.split(':');
    date.setUTCHours(parseInt(h), parseInt(m));
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

// --- LOGIC GROUPING ---
const groupedRoutines = computed(() => {
    const groups: Record<string, any> = {};

    props.routines.forEach(routine => {
        const key = routine.product_id 
            ? `prod-${routine.product_id}` 
            : `manual-${routine.custom_product_name.toLowerCase().trim()}`;

        if (!groups[key]) {
            groups[key] = {
                name: routine.name,
                image_url: routine.image_url,
                note: routine.note,
                is_manual: !routine.product_id,
                slots: [] 
            };
        }

        groups[key].slots.push({
            id: routine.id,
            reminder_time: routine.reminder_time,
            is_completed_today: routine.is_completed_today,
            is_reminder_active: routine.is_reminder_active,
            repeat_frequency: routine.repeat_frequency,
            step_order: routine.step_order,
            original_data: routine 
        });
    });

    Object.values(groups).forEach((group: any) => {
        group.slots.sort((a: any, b: any) => {
            if (!a.reminder_time) return 1;
            if (!b.reminder_time) return -1;
            return a.reminder_time.localeCompare(b.reminder_time);
        });
    });

    return Object.values(groups);
});

// --- STATE ---
const showModal = ref(false);
const isEditMode = ref(false);
const editingId = ref<number | null>(null);

// Search State
const searchQuery = ref('');
const isDropdownOpen = ref(false);
const selectedStoreProduct = ref<any>(null);

const filteredStoreProducts = computed(() => {
    if (!searchQuery.value) return [];
    const lowerQuery = searchQuery.value.toLowerCase();
    return props.storeProducts.filter(p => 
        p.name.toLowerCase().includes(lowerQuery) || 
        (p.brand_name && p.brand_name.toLowerCase().includes(lowerQuery))
    ).slice(0, 5);
});

const progress = computed(() => {
    const total = props.routines.length;
    if (total === 0) return 0;
    const completed = props.routines.filter(r => r.is_completed_today).length;
    return Math.round((completed / total) * 100);
});

// --- FORM ---
const form = useForm({
    product_id: '' as string | number | null,
    custom_product_name: '' as string | null,
    step_order: 1,
    note: '',
    reminder_times: [''] as string[], 
    reminder_time: '', 
    is_reminder_active: false,
    repeat_frequency: 1,
    timezone_input: browserTimezone
});

watch(() => form.reminder_times, (newVal) => {
    const hasTime = newVal.some(t => t !== '');
    if (hasTime && !form.is_reminder_active) form.is_reminder_active = true;
}, { deep: true });

watch(() => form.reminder_time, (newVal) => {
    if (newVal && !form.is_reminder_active) form.is_reminder_active = true;
});

// --- ACTIONS ---

const addTimeSlot = () => { form.reminder_times.push(''); };
const removeTimeSlot = (index: number) => { form.reminder_times.splice(index, 1); };

const openAddModal = () => {
    isEditMode.value = false;
    editingId.value = null;
    form.reset();
    form.timezone_input = browserTimezone;
    form.reminder_times = [''];
    
    const maxStep = props.routines.length > 0 ? Math.max(...props.routines.map(r => r.step_order)) : 0;
    form.step_order = maxStep + 1;
    
    searchQuery.value = '';
    selectedStoreProduct.value = null;
    showModal.value = true;
};

const openEditModal = (slot: any, groupName: string) => {
    const item = slot.original_data;
    isEditMode.value = true;
    editingId.value = item.id;
    
    form.step_order = item.step_order;
    form.note = item.note || '';
    form.is_reminder_active = item.is_reminder_active;
    form.repeat_frequency = item.repeat_frequency || 1;
    form.timezone_input = browserTimezone;
    
    form.reminder_time = item.reminder_time ? utcToLocalInput(item.reminder_time) : '';
    form.reminder_times = [];

    if (item.product_id) {
        const product = props.storeProducts.find(p => p.id === item.product_id);
        selectedStoreProduct.value = product || { name: item.name, image_url: item.image_url, brand_name: item.brand_name };
        form.product_id = item.product_id;
        form.custom_product_name = null;
    } else {
        selectedStoreProduct.value = null;
        form.product_id = null;
        form.custom_product_name = item.name;
        searchQuery.value = item.name;
    }

    showModal.value = true;
};

const selectStoreProduct = (product: any) => {
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

const submitRoutine = () => {
    if (!selectedStoreProduct.value) {
        form.product_id = null;
        form.custom_product_name = searchQuery.value;
    }

    if (isEditMode.value && editingId.value) {
        form.put(route('routine.update', editingId.value), {
            onSuccess: () => showModal.value = false,
            preserveScroll: true
        });
    } else {
        form.post(route('routine.store'), {
            onSuccess: () => showModal.value = false,
            preserveScroll: true
        });
    }
};

const deleteRoutine = () => {
    if (!editingId.value) return;
    if(confirm('Hapus jadwal waktu ini?')) {
        router.delete(route('routine.destroy', editingId.value), {
            onSuccess: () => showModal.value = false,
            preserveScroll: true
        });
    }
};

const toggleCheck = (id: number) => {
    router.post(route('routine.toggle', id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Skincare Routine" />
    <div class="max-w-3xl mx-auto py-8 px-4 pb-24">
        
        <!-- HEADER -->
        <div class="bg-gradient-to-r from-rose-500 to-pink-600 rounded-3xl p-6 text-white shadow-lg mb-6 relative overflow-hidden">
            <div class="relative z-10 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">My Routine</h1>
                    <p class="text-rose-100 text-sm mt-1">Konsistensi = Kulit Sehat ✨</p>
                </div>
                <div class="text-center bg-white/20 backdrop-blur-sm rounded-xl p-3 min-w-[80px]">
                    <span class="block text-2xl font-bold">{{ progress }}%</span>
                    <span class="text-[10px] uppercase tracking-wider opacity-80">Hari Ini</span>
                </div>
            </div>
            <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl -mr-10 -mt-10"></div>
        </div>

        <!-- TOMBOL TAMBAH -->
        <button 
            @click="openAddModal"
            class="w-full bg-white border-2 border-dashed border-rose-300 text-rose-600 font-bold py-4 rounded-2xl hover:bg-rose-50 hover:border-rose-400 hover:shadow-md transition-all flex items-center justify-center gap-2 mb-8 group"
        >
            <div class="bg-rose-100 p-1.5 rounded-full group-hover:bg-rose-200 transition-colors">
                <Plus class="w-5 h-5" />
            </div>
            <span class="text-lg">Tambah Rutinitas Baru</span>
        </button>

        <!-- LIST ROUTINES (GROUPED) -->
        <div class="space-y-4">
            <div v-if="props.routines.length === 0" class="text-center py-8">
                <p class="text-gray-400 text-sm">Belum ada rutinitas.</p>
            </div>

            <!-- Kartu Per Produk -->
            <div v-for="(group, index) in groupedRoutines" :key="index" 
                class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="flex items-start gap-4">
                    <!-- KIRI: Gambar Produk -->
                    <div class="w-14 h-14 rounded-xl bg-gray-100 overflow-hidden shrink-0 border border-gray-100 self-center">
                        <img v-if="group.image_url" :src="group.image_url" class="w-full h-full object-cover">
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-300 text-xs font-bold">IMG</div>
                    </div>

                    <!-- TENGAH: Info Produk -->
                    <div class="flex-1 min-w-0 self-center">
                        <h3 class="text-sm font-bold text-gray-900 leading-tight mb-1 line-clamp-2">
                            {{ group.name }}
                        </h3>
                        <div class="flex flex-wrap items-center gap-2">
                            <span v-if="group.is_manual" class="text-[10px] text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded border border-gray-200">Manual</span>
                            <p v-if="group.note" class="text-xs text-gray-500 truncate">{{ group.note }}</p>
                        </div>
                    </div>

                    <!-- KANAN: Slot Waktu (Vertikal) -->
                    <div class="flex flex-col gap-2 shrink-0 items-end pl-2 border-l border-gray-100">
                        <div 
                            v-for="slot in group.slots" 
                            :key="slot.id"
                            class="flex items-center bg-gray-50 rounded-lg border border-gray-200 overflow-hidden shadow-sm transition-all hover:shadow-md group/timebtn"
                            :class="{'border-green-200 bg-green-50': slot.is_completed_today}"
                        >
                            <!-- Tombol Checklist -->
                            <button 
                                @click="toggleCheck(slot.id)"
                                class="flex items-center gap-2 px-3 py-1.5 hover:bg-black/5 transition-colors group/check"
                                title="Klik untuk menyelesaikan"
                            >
                                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-colors"
                                     :class="slot.is_completed_today ? 'border-green-500 bg-green-500' : 'border-gray-300 bg-white group-hover/check:border-rose-400'">
                                    <Check v-if="slot.is_completed_today" class="w-2.5 h-2.5 text-white stroke-[3]" />
                                </div>
                                <span class="text-xs font-bold" :class="slot.is_completed_today ? 'text-green-700' : 'text-gray-700'">
                                    {{ formatTimeDisplay(slot.reminder_time) }}
                                </span>
                            </button>

                            <!-- Divider Kecil -->
                            <div class="w-px h-4 bg-gray-300 mx-0.5"></div>

                            <!-- Tombol Edit (Pensil) -->
                            <button 
                                @click.stop="openEditModal(slot, group.name)"
                                class="px-2 py-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                title="Edit Jadwal Ini"
                            >
                                <Edit3 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL FORM -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 transition-all">
            <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden animate-fade-in-up border border-gray-100 max-h-[90vh] flex flex-col">
                
                <div class="p-5 border-b border-gray-100 flex justify-between items-center bg-white shrink-0">
                    <h3 class="font-bold text-lg text-gray-900">
                        {{ isEditMode ? 'Edit Jadwal' : 'Tambah Rutinitas' }}
                    </h3>
                    <button @click="showModal = false" class="bg-gray-100 hover:bg-gray-200 p-2 rounded-full transition-colors text-gray-600 hover:text-red-500">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 space-y-6 overflow-y-auto">
                    
                    <!-- 1. PRODUK -->
                    <div :class="{'opacity-75 pointer-events-none': isEditMode}">
                        <label class="block text-sm font-bold text-gray-900 mb-2">
                            Nama Produk {{ isEditMode ? '(Tidak dapat diubah saat edit waktu)' : '' }}
                        </label>
                        
                        <div v-if="selectedStoreProduct" class="flex items-center gap-3 p-3 border border-rose-200 bg-rose-50/50 rounded-xl relative group">
                             <div class="w-12 h-12 bg-white rounded-lg overflow-hidden border border-rose-100 shrink-0">
                                <img v-if="selectedStoreProduct.image_url" :src="selectedStoreProduct.image_url" class="w-full h-full object-cover">
                             </div>
                             <div class="flex-1 min-w-0">
                                <p class="text-[10px] text-rose-500 font-bold uppercase">{{ selectedStoreProduct.brand_name || 'Brand' }}</p>
                                <p class="text-sm font-bold text-gray-900 truncate">{{ selectedStoreProduct.name }}</p>
                             </div>
                             <button v-if="!isEditMode" @click="clearSelectedProduct" class="p-1.5 text-gray-400 hover:text-red-500 bg-white rounded-full shadow-sm hover:shadow transition-all"><X class="w-4 h-4" /></button>
                        </div>

                        <div v-else class="relative">
                            <Search class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" />
                            <input 
                                v-model="searchQuery" 
                                @focus="isDropdownOpen = true"
                                type="text" 
                                placeholder="Cari di katalog atau ketik manual..." 
                                class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 bg-white text-gray-900 placeholder-gray-400 focus:border-rose-500 focus:ring-rose-500 shadow-sm transition-shadow"
                            >
                            <div v-if="searchQuery && isDropdownOpen && filteredStoreProducts.length > 0" class="absolute z-20 w-full mt-2 bg-white border border-gray-100 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                                <div class="px-3 py-2 text-[10px] font-bold text-gray-400 uppercase bg-gray-50 tracking-wider">Katalog Toko</div>
                                <button v-for="p in filteredStoreProducts" :key="p.id" @click="selectStoreProduct(p)" class="w-full flex items-center gap-3 p-3 hover:bg-rose-50 text-left border-b border-gray-50 last:border-0 transition-colors">
                                    <div class="w-8 h-8 bg-gray-100 rounded overflow-hidden flex-shrink-0"><img v-if="p.image_url" :src="p.image_url" class="w-full h-full object-cover"></div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs text-gray-500 font-bold">{{ p.brand_name }}</p>
                                        <p class="text-sm font-bold text-gray-900 truncate">{{ p.name }}</p>
                                    </div>
                                </button>
                            </div>
                            <div v-if="searchQuery && !selectedStoreProduct" class="mt-2 flex items-center gap-2 text-xs text-rose-500 bg-rose-50 p-2 rounded-lg border border-rose-100">
                                <PenTool class="w-3 h-3" />
                                <span>Simpan sebagai manual: <b class="text-gray-900">"{{ searchQuery }}"</b></span>
                            </div>
                        </div>
                    </div>

                    <!-- 2. SETTINGS -->
                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-bold text-gray-800 flex items-center gap-2">
                                <Bell class="w-4 h-4 text-rose-500" /> Aktifkan Pengingat?
                            </label>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="form.is_reminder_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:bg-rose-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                            </label>
                        </div>

                        <div v-if="form.is_reminder_active" class="space-y-4 animate-fade-in-up">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Zona Waktu</label>
                                    <select v-model="form.timezone_input" class="w-full rounded-lg border-gray-300 text-gray-900 bg-white text-sm py-2 px-3 focus:border-rose-500 shadow-sm">
                                        <option v-for="tz in timezones" :key="tz.value" :value="tz.value">{{ tz.label }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-1">Ulangi</label>
                                    <select v-model="form.repeat_frequency" class="w-full rounded-lg border-gray-300 text-gray-900 bg-white text-sm py-2 px-3 focus:border-rose-500 shadow-sm">
                                        <option :value="1">Tiap Hari</option>
                                        <option :value="2">Tiap 2 Hari</option>
                                        <option :value="7">Mingguan</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-2">Pukul Berapa?</label>
                                <div v-if="!isEditMode" class="space-y-2">
                                    <div v-for="(time, index) in form.reminder_times" :key="index" class="flex gap-2">
                                        <input v-model="form.reminder_times[index]" type="time" class="flex-1 rounded-lg border-gray-300 text-gray-900 bg-white focus:border-rose-500 py-2 px-3 shadow-sm">
                                        <button v-if="index > 0" @click="removeTimeSlot(index)" type="button" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors"><Minus class="w-4 h-4"/></button>
                                    </div>
                                    <button @click="addTimeSlot" type="button" class="text-xs text-rose-600 font-bold hover:underline flex items-center gap-1 mt-2"><Plus class="w-3 h-3" /> Tambah Jam Lain</button>
                                </div>
                                <div v-else>
                                    <input v-model="form.reminder_time" type="time" class="w-full rounded-lg border-gray-300 text-gray-900 bg-white focus:border-rose-500 py-2 px-3 shadow-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="col-span-1">
                            <label class="block text-sm font-bold text-gray-900 mb-2">Urutan</label>
                            <input v-model="form.step_order" type="number" min="1" class="w-full rounded-xl border-gray-300 text-gray-900 bg-white py-2.5 px-3 focus:ring-rose-500 shadow-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-gray-900 mb-2">Catatan</label>
                            <input v-model="form.note" type="text" placeholder="Misal: 2 pump" class="w-full rounded-xl border-gray-300 text-gray-900 bg-white py-2.5 px-3 placeholder-gray-400 focus:ring-rose-500 shadow-sm">
                        </div>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button v-if="isEditMode" @click="deleteRoutine" type="button" class="px-5 py-3 bg-red-50 text-red-600 rounded-xl font-bold hover:bg-red-100 transition-colors border border-red-100"><Trash2 class="w-5 h-5" /></button>
                        <button @click="submitRoutine" :disabled="form.processing || (!selectedStoreProduct && !searchQuery)" class="flex-1 bg-rose-600 text-white py-3 rounded-xl font-bold hover:bg-rose-700 disabled:opacity-50 shadow-lg shadow-rose-200 transition-all">
                            {{ isEditMode ? 'Simpan Perubahan' : 'Simpan Jadwal' }}
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
.animate-fade-in-up { animation: fadeInUp 0.3s ease-out; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>