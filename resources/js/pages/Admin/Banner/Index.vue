<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Swal from 'sweetalert2';

// Props
const props = defineProps({ 
    banners: Object,
    availableVariants: Array // [{id, full_name, price, thumbnail}]
});

// State
const showModal = ref(false);
const isEditing = ref(false);
const previewUrl = ref<string | null>(null);
const search = ref(''); 

// State Validasi Manual
const clientErrors = ref({
    title: '',
    subtitle: '',
    image: '',
    start_date: '',
    end_date: '',
    products: '' // Validasi baru untuk produk
});

// Helper: Get Local Datetime String for Input (YYYY-MM-DDTHH:mm)
const getCurrentDateTime = () => {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 16);
};

// Form
const form = useForm({
    id: null as number | null, 
    title: '', 
    subtitle: '', 
    start_date: '', 
    end_date: '', 
    image: null as File | null, 
    products: [] as number[], 
    // Varian lengkap untuk menyimpan nilai diskon
    variants: [] as any[],
    _method: 'post'
});

// Computed: Filter Varian
const filteredVariants = computed(() => {
    if (!search.value) return props.availableVariants;
    return props.availableVariants.filter((v: any) => 
        v.full_name.toLowerCase().includes(search.value.toLowerCase())
    );
});

// --- HELPER FUNCTIONS ---

const isSelected = (id: number) => {
    return form.variants.some((v) => v.id === id);
};

const getVariantForm = (id: number) => {
    return form.variants.find((v) => v.id === id);
};

const toggleVariant = (id: number) => {
    const idx = form.variants.findIndex((v) => v.id === id);
    if (idx === -1) {
        // Add (Default diskon 0%)
        form.variants.push({ id: id, discount_type: 'percent', discount_value: 0 });
    } else {
        // Remove
        form.variants.splice(idx, 1);
    }
};

// --- VALIDATION ---
const validateForm = () => {
    let isValid = true;
    // Reset errors
    clientErrors.value = { title: '', subtitle: '', image: '', start_date: '', end_date: '', products: '' };

    if (!form.title) {
        clientErrors.value.title = 'Campaign title is required.';
        isValid = false;
    }
    
    if (!form.subtitle) {
        clientErrors.value.subtitle = 'Subtitle is required.';
        isValid = false;
    }

    // Validasi Gambar: Wajib ada preview (Entah dari upload baru atau data lama)
    if (!previewUrl.value) {
        clientErrors.value.image = 'Cover image is required.';
        isValid = false;
    }

    if (!form.start_date) {
        clientErrors.value.start_date = 'Start date is required.';
        isValid = false;
    }
    
    if (!form.end_date) {
        clientErrors.value.end_date = 'End date is required.';
        isValid = false;
    }

    // Validasi End Date harus setelah Start Date (jika diisi)
    if (form.end_date && form.start_date && new Date(form.end_date) <= new Date(form.start_date)) {
        clientErrors.value.end_date = 'End date must be after start date.';
        isValid = false;
    }

    // Validasi Minimal 1 Produk Terpilih
    if (form.variants.length === 0) {
        clientErrors.value.products = 'Please select at least one product variant for this campaign.';
        isValid = false;
    }

    return isValid;
};

// --- CRUD METHODS ---

// --- CRUD METHODS ---

const openModal = (banner: any = null) => {
    form.clearErrors();
    clientErrors.value = { title: '', subtitle: '', image: '', start_date: '', end_date: '', products: '' };
    
    // Reset bawaan Inertia
    form.reset();
    search.value = '';
    
    if (banner) {
        // --- MODE EDIT ---
        isEditing.value = true;
        form.id = banner.id;
        form.title = banner.title;
        form.subtitle = banner.subtitle;
        form.start_date = banner.start_date ? new Date(banner.start_date).toISOString().slice(0, 16) : '';
        form.end_date = banner.end_date ? new Date(banner.end_date).toISOString().slice(0, 16) : '';
        previewUrl.value = banner.image;

        form.variants = [];
        if (banner.selected_variants) {
            Object.keys(banner.selected_variants).forEach(key => {
                const id = parseInt(key);
                const data = banner.selected_variants[key];
                form.variants.push({
                    id: id,
                    discount_type: data.discount_type,
                    discount_value: data.discount_value
                });
            });
        }
        
        form._method = 'put';
    } else {
        // --- MODE CREATE ---
        isEditing.value = false;
        form.id = null;
        
        // FIX: Paksa kosongkan manual agar form benar-benar bersih
        form.title = '';
        form.subtitle = '';
        
        const now = getCurrentDateTime();
        form.start_date = now;
        form.end_date = ''; 

        previewUrl.value = null; 
        form._method = 'post'; 
        form.variants = [];
    }
    showModal.value = true;
};

const handleImage = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) { form.image = file; previewUrl.value = URL.createObjectURL(file); }
};

const submit = () => {
    // 1. Jalankan Validasi Client
    if (!validateForm()) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please fill in all required fields.',
            confirmButtonColor: '#db2777'
        });
        return;
    }

    const url = isEditing.value ? route('admin.banners.update', form.id) : route('admin.banners.store');
    form.post(url, {
        forceFormData: true,
        onSuccess: () => { showModal.value = false; Swal.fire({ title: 'Success', text: 'Campaign saved!', icon: 'success', confirmButtonColor: '#db2777' }); },
        onError: (err) => { console.error(err); Swal.fire('Error', 'Check form inputs', 'error'); }
    });
};

const deleteBanner = (id: number) => {
    Swal.fire({ title: 'Delete?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#db2777', confirmButtonText: 'Yes' })
    .then((r) => { if(r.isConfirmed) router.delete(route('admin.banners.destroy', id)); });
};

const toggleActive = (id: number) => { router.post(route('admin.banners.toggle', id), {}, { preserveScroll: true }); };
</script>

<template>
    <Head title="Promo Campaigns" />
    
    <AdminLayout>
        
        <!-- HEADER -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Promo Campaigns</h1>
                <p class="text-sm text-gray-600 mt-1 font-medium">Manage banners and variant discounts.</p>
            </div>
            <button @click="openModal()" class="px-5 py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl shadow-sm hover:shadow-md transition-all text-sm flex items-center gap-2">
                + New Campaign
            </button>
        </div>

        <!-- GRID CARD -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="banner in banners.data" :key="banner.id" class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden group relative hover:shadow-lg transition-all duration-300">
                
                <div class="aspect-[21/9] bg-gray-100 relative">
                    <img :src="banner.image" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                    
                    <!-- STATUS BADGES -->
                    <div class="absolute top-3 left-3 flex gap-2 z-10">
                        <span v-if="!banner.is_active" class="px-2.5 py-1 bg-gray-900/90 backdrop-blur-md text-white text-[10px] font-extrabold rounded-lg uppercase shadow-sm">Disabled</span>
                        <span v-else-if="banner.status_label === 'LIVE NOW'" class="px-2.5 py-1 bg-gradient-to-r from-green-500 to-emerald-600 text-white text-[10px] font-extrabold rounded-lg uppercase shadow-sm animate-pulse">Live</span>
                        <span v-else-if="banner.status_label === 'EXPIRED'" class="px-2.5 py-1 bg-rose-600/90 backdrop-blur-md text-white text-[10px] font-extrabold rounded-lg uppercase shadow-sm">Expired</span>
                        <span v-else class="px-2.5 py-1 bg-amber-500/90 backdrop-blur-md text-white text-[10px] font-extrabold rounded-lg uppercase shadow-sm">Upcoming</span>
                    </div>

                    <!-- HOVER ACTIONS -->
                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-[2px] z-20">
                        <button @click="openModal(banner)" class="p-2.5 bg-white rounded-full text-gray-800 hover:text-pink-600 hover:scale-110 transition-all shadow-lg" title="Edit">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        </button>
                        <button @click="deleteBanner(banner.id)" class="p-2.5 bg-white rounded-full text-gray-800 hover:text-rose-600 hover:scale-110 transition-all shadow-lg" title="Delete">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>
                </div>
                
                <div class="p-5">
                    <div class="flex justify-between items-start mb-3">
                        <div class="overflow-hidden pr-4">
                            <h3 class="font-bold text-gray-900 truncate text-base">{{ banner.title }}</h3>
                            <p class="text-xs text-gray-600 font-medium truncate mt-1">{{ banner.subtitle || 'No subtitle provided' }}</p>
                        </div>
                        <button @click="toggleActive(banner.id)" :class="banner.is_active ? 'bg-pink-600' : 'bg-gray-300'" class="w-11 h-6 rounded-full relative transition-colors duration-300 focus:outline-none shrink-0 cursor-pointer">
                            <span :class="banner.is_active ? 'translate-x-5' : 'translate-x-1'" class="absolute top-1 left-0 w-4 h-4 bg-white rounded-full transition-transform duration-300 shadow-sm"></span>
                        </button>
                    </div>

                    <div class="pt-3 border-t border-gray-100 flex justify-between items-center text-[11px]">
                        <div class="flex items-center gap-1.5 text-gray-500 font-bold">
                            <svg class="w-3.5 h-3.5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span :class="{'text-green-600': banner.status_label === 'LIVE NOW', 'text-red-500': banner.status_label === 'EXPIRED'}">{{ banner.status_label }}</span>
                        </div>

                        <div class="flex items-center gap-1.5 text-pink-700 font-extrabold bg-pink-50 px-2.5 py-1 rounded-md border border-pink-100">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                            {{ banner.variants ? banner.variants.length : 0 }} Items
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div @click="showModal = false" class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
            
            <form @submit.prevent="submit" class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl relative z-10 overflow-hidden flex flex-col max-h-[90vh] border border-gray-100">
                
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/80 backdrop-blur flex justify-between items-center shrink-0">
                    <h3 class="font-extrabold text-lg text-gray-900">{{ isEditing ? 'Edit Campaign' : 'New Campaign' }}</h3>
                    <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-700 transition-colors p-1 rounded-full hover:bg-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="p-6 space-y-6 overflow-y-auto custom-scrollbar bg-white">
                    
                    <!-- Image Upload -->
                    <div class="aspect-[21/9] bg-gray-50 rounded-xl border-2 border-dashed border-gray-300 flex flex-col items-center justify-center relative cursor-pointer overflow-hidden hover:border-pink-500 hover:bg-pink-50/10 transition-all group">
                        <img v-if="previewUrl" :src="previewUrl" class="absolute inset-0 w-full h-full object-cover">
                        <div v-else class="text-center p-4">
                             <svg class="w-10 h-10 text-gray-300 mx-auto mb-2 group-hover:text-pink-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <span class="text-xs text-gray-500 font-bold uppercase group-hover:text-pink-600">Click to Upload Cover <span class="text-red-500">*</span></span>
                        </div>
                        <input type="file" class="absolute inset-0 opacity-0 cursor-pointer" @change="handleImage" accept="image/*">
                    </div>
                    <p v-if="form.errors.image || clientErrors.image" class="text-xs text-red-600 font-bold mt-1">
                        {{ form.errors.image || clientErrors.image }}
                    </p>

                    <!-- Basic Info -->
                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <label class="text-xs font-extrabold text-gray-800 mb-1.5 block uppercase tracking-wide">Title <span class="text-red-500">*</span></label>
                            <input v-model="form.title" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm text-gray-900 font-medium focus:ring-2 focus:ring-pink-500 focus:border-pink-500 placeholder:text-gray-400 transition-all" placeholder="e.g. 12.12 Sale">
                            <p v-if="form.errors.title || clientErrors.title" class="text-xs text-red-600 font-bold mt-1">
                                {{ form.errors.title || clientErrors.title }}
                            </p>
                        </div>
                        <div>
                            <label class="text-xs font-extrabold text-gray-800 mb-1.5 block uppercase tracking-wide">Subtitle <span class="text-red-500">*</span></label>
                            <input v-model="form.subtitle" class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm text-gray-900 font-medium focus:ring-2 focus:ring-pink-500 focus:border-pink-500 placeholder:text-gray-400 transition-all" placeholder="e.g. Up to 50% Off">
                            <p v-if="clientErrors.subtitle" class="text-xs text-red-600 font-bold mt-1">
                                {{ clientErrors.subtitle }}
                            </p>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="grid grid-cols-2 gap-4 p-4 bg-pink-50 rounded-xl border border-pink-100">
                        <div>
                            <label class="text-xs font-extrabold text-pink-800 mb-1.5 block">Start Date (Live) <span class="text-red-500">*</span></label>
                            <input type="datetime-local" v-model="form.start_date" class="w-full border border-pink-200 rounded-lg px-3 py-2 text-xs font-bold text-gray-900 bg-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-all">
                            <p v-if="form.errors.start_date || clientErrors.start_date" class="text-xs text-red-600 font-bold mt-1">
                                {{ form.errors.start_date || clientErrors.start_date }}
                            </p>
                        </div>
                        <div>
                            <label class="text-xs font-extrabold text-pink-800 mb-1.5 block">End Date (Expire) <span class="text-red-500">*</span></label>
                            <input type="datetime-local" v-model="form.end_date" class="w-full border border-pink-200 rounded-lg px-3 py-2 text-xs font-bold text-gray-900 bg-white focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-all">
                            <p v-if="form.errors.end_date || clientErrors.end_date" class="text-xs text-red-600 font-bold mt-1">
                                {{ form.errors.end_date || clientErrors.end_date }}
                            </p>
                        </div>
                    </div>

                    <!-- VARIANT SELECTOR -->
                    <div class="border border-gray-200 rounded-xl overflow-hidden flex flex-col h-80 shadow-sm">
                        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex justify-between items-center shrink-0">
                            <label class="text-xs font-extrabold text-gray-700 uppercase">Select Variants & Discount</label>
                            <input v-model="search" placeholder="Search variant..." class="text-xs border border-gray-300 rounded-lg pl-3 py-1.5 w-40 focus:ring-pink-500 text-gray-900 font-medium">
                        </div>
                        
                        <div class="overflow-y-auto flex-1 p-2 bg-white custom-scrollbar">
                            <div class="grid grid-cols-1 gap-1">
                                <div v-for="v in filteredVariants" :key="v.id" 
                                     class="flex items-center gap-3 p-2 rounded-lg border transition-all"
                                     :class="isSelected(v.id) ? 'bg-pink-50 border-pink-200' : 'bg-white border-transparent hover:bg-gray-50'"
                                >
                                    <!-- Checkbox -->
                                    <input type="checkbox" :checked="isSelected(v.id)" @change="toggleVariant(v.id)" class="w-4 h-4 text-pink-600 rounded focus:ring-pink-500 border-gray-300 cursor-pointer">
                                    
                                    <!-- Thumb -->
                                    <div class="w-8 h-8 bg-gray-100 rounded overflow-hidden border border-gray-200 shrink-0">
                                        <img :src="v.thumbnail" class="w-full h-full object-cover">
                                    </div>
                                    
                                    <!-- Name -->
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-gray-800 truncate">{{ v.full_name }}</p>
                                        <p class="text-[10px] text-gray-500 font-medium">Rp {{ v.price.toLocaleString() }}</p>
                                    </div>
                                    
                                    <!-- DISCOUNT INPUT -->
                                    <div v-if="isSelected(v.id) && getVariantForm(v.id)" class="flex items-center gap-1 animate-in fade-in slide-in-from-right-2">
                                        <select 
                                            v-model="getVariantForm(v.id).discount_type" 
                                            class="text-[10px] border-gray-300 text-gray-800 font-bold rounded-md py-1 pl-1 pr-5 h-7 bg-white focus:ring-pink-500 cursor-pointer"
                                        >
                                            <option value="percent">%</option>
                                            <option value="fixed">Rp</option>
                                        </select>
                                        <input 
                                            type="number" 
                                            v-model="getVariantForm(v.id).discount_value"
                                            class="w-16 text-[10px] border-gray-300 rounded-md py-1 px-2 h-7 focus:ring-pink-500 font-bold text-pink-600 text-center"
                                            placeholder="0"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-2 border-t border-gray-200 text-[10px] text-gray-600 font-bold text-right">
                            {{ form.variants.length }} variants selected.
                        </div>
                        <p v-if="clientErrors.products" class="text-xs text-red-600 font-bold text-center py-1 bg-red-50 border-t border-red-100">
                            {{ clientErrors.products }}
                        </p>
                    </div>

                </div>
                
                <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3 border-t border-gray-100 shrink-0">
                    <button type="button" @click="showModal = false" class="px-5 py-2.5 border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-100 transition-all text-sm">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl shadow-md hover:shadow-lg hover:from-pink-700 hover:to-rose-700 transition-all text-sm flex items-center gap-2">
                        <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ form.processing ? 'Saving...' : 'Save Campaign' }}
                    </button>
                </div>

            </form>
        </div>
    </AdminLayout>
</template>