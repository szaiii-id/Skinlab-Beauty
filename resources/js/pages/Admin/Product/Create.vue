<script setup lang="ts">
import { useForm, Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { ref, onMounted } from 'vue';
import Swal from 'sweetalert2';

// --- PROPS ---
const props = defineProps({
    brands: Array,
    categories: Array,
    skinTypes: Array,
    skinConcerns: Array,
    product: { type: Object, default: null }, 
});

// --- CONSTANTS ---
const UNIT_OPTIONS = ['ml', 'gram', 'pcs', 'pack', 'set', 'sheet'];

// --- STATE ---
const isEditing = !!props.product; 
const thumbnailPreview = ref<string | null>(null);

// Helper untuk memecah "50ml" menjadi { val: 50, unit: 'ml' }
const parseVolume = (volumeStr: string) => {
    if (!volumeStr) return { val: '', unit: 'ml' };
    
    // Regex untuk memisahkan angka dan huruf
    const match = volumeStr.match(/^(\d+)\s*([a-zA-Z]+)$/);
    if (match) {
        return { val: match[1], unit: match[2].toLowerCase() }; // Contoh: {val: "50", unit: "ml"}
    }
    // Jika format tidak standar, anggap sbg value saja, unit default ml
    return { val: volumeStr, unit: 'ml' };
};

// Form Initialization
const form = useForm({
    _method: isEditing ? 'put' : 'post',
    id: props.product?.id || null,
    name: props.product?.name || '',
    description: props.product?.description || '',
    brand_id: props.product?.brand_id || '',
    category_id: props.product?.category_id || '',
    suitability_tags: props.product?.suitability_tags || ([] as string[]),
    thumbnail: null as File | null,
    
    variants: props.product?.variants?.length 
        ? props.product.variants.map((v: any) => {
            const parsed = parseVolume(v.volume);
            return {
                ...v, 
                image_file: null, 
                preview: v.image_url,
                // Field Khusus UI (Pisah Angka & Satuan)
                volume_val: parsed.val,
                volume_unit: parsed.unit, 
            };
        }) 
        : [{ 
            volume: '', price: '', stock: '', sku: '', image_file: null, preview: null,
            // Default UI State
            volume_val: '', volume_unit: 'ml' 
          }]
});

onMounted(() => {
    if (isEditing && props.product.thumbnail) {
        thumbnailPreview.value = props.product.thumbnail;
    }
});

// --- METHODS ---
const handleThumbnailChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) { form.thumbnail = file; thumbnailPreview.value = URL.createObjectURL(file); }
};

const handleVariantImage = (index: number, event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) { form.variants[index].image_file = file; form.variants[index].preview = URL.createObjectURL(file); }
};

const addVariant = () => {
    form.variants.push({ 
        volume: '', price: '', stock: '', sku: '', image_file: null, preview: null,
        volume_val: '', volume_unit: 'ml' // Default unit
    });
};

const removeVariant = (index: number) => {
    if (form.variants.length > 1) form.variants.splice(index, 1);
    else Swal.fire({ icon: 'warning', text: 'Min 1 variant required.', confirmButtonColor: '#db2777' });
};

const submit = () => {
    // SEBELUM SUBMIT: GABUNGKAN VAL + UNIT JADI STRING "50ml"
    form.variants.forEach(v => {
        if (v.volume_val) {
            v.volume = `${v.volume_val}${v.volume_unit}`;
        }
    });

    const routeName = isEditing ? 'admin.products.update' : 'admin.products.store';
    const routeParams = isEditing ? form.id : undefined;

    form.post(route(routeName, routeParams), {
        forceFormData: true, 
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: isEditing ? 'Updated!' : 'Published!', confirmButtonColor: '#db2777', timer: 2000 });
            router.visit(route('admin.products.index'));
        },
        onError: (errors) => {
            console.error(errors);
            Swal.fire({ icon: 'error', title: 'Check Form', text: 'Please fix validation errors.', confirmButtonColor: '#db2777' });
        }
    });
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Product' : 'Create Product'" />

    <AdminLayout>
        
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ isEditing ? 'Edit Product' : 'Add Product' }}</h1>
                <p class="text-sm text-gray-600 mt-1 font-medium">Create a new product card for your catalog.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <Link :href="route('admin.products.index')" class="px-4 py-2.5 border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition-all text-sm">
                    Cancel
                </Link>
                <button @click="submit" :disabled="form.processing" class="px-6 py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl shadow-lg shadow-pink-200 hover:shadow-pink-300 hover:-translate-y-0.5 transition-all text-sm flex items-center gap-2">
                    <svg v-if="form.processing" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    {{ form.processing ? 'Saving...' : 'Save Product' }}
                </button>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-8 pb-10">
            
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                    <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">General Information</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-1">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Product Name</label>
                        <input 
                            v-model="form.name" 
                            type="text" 
                            class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-all placeholder:text-gray-400 font-medium"
                            placeholder="e.g. 5% Niacinamide Barrier Serum"
                        >
                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1 font-medium">{{ form.errors.name }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Description</label>
                        <textarea 
                            v-model="form.description" 
                            rows="3" 
                            class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-all placeholder:text-gray-400 font-medium"
                            placeholder="Describe the product benefits..."
                        ></textarea>
                        <p v-if="form.errors.description" class="text-red-500 text-xs mt-1 font-medium">{{ form.errors.description }}</p>
                    </div>
                </div>
            </div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-full">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="text-xs font-extrabold text-gray-500 uppercase tracking-wide">1. Organization</h3>
                    </div>
                    <div class="p-6 space-y-5 flex-1">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Brand</label>
                            <select v-model="form.brand_id" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-pink-500 font-medium shadow-sm transition-all cursor-pointer hover:border-pink-300">
                                <option value="" disabled>Select Brand</option>
                                <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
                            </select>
                            <p v-if="form.errors.brand_id" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.brand_id }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Category</label>
                            <select v-model="form.category_id" class="w-full px-4 py-2.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-900 focus:ring-2 focus:ring-pink-500 font-medium shadow-sm transition-all cursor-pointer hover:border-pink-300">
                                <option value="" disabled>Select Category</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                            </select>
                            <p v-if="form.errors.category_id" class="text-red-500 text-[10px] mt-1 font-bold">{{ form.errors.category_id }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="text-xs font-extrabold text-gray-500 uppercase tracking-wide">2. Main Image</h3>
                    </div>
                    <div class="p-6 flex-1 flex flex-col justify-center">
                        <div class="w-full aspect-[4/3] bg-gray-50 border-2 border-dashed border-gray-300 rounded-2xl flex flex-col items-center justify-center relative overflow-hidden hover:border-pink-500 hover:bg-pink-50/30 transition-all cursor-pointer group">
                            <img v-if="thumbnailPreview" :src="thumbnailPreview" class="absolute inset-0 w-full h-full object-cover rounded-2xl">
                            <div v-else class="text-center p-6">
                                <div class="bg-white p-3 rounded-full shadow-sm inline-block mb-3 group-hover:scale-110 transition-transform border border-gray-100">
                                    <svg class="w-8 h-8 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <p class="text-xs font-extrabold text-gray-700 uppercase tracking-wide">Upload Cover</p>
                                <p class="text-[10px] text-gray-400 mt-1">Max 2MB (PNG/JPG)</p>
                            </div>
                            <input type="file" class="absolute inset-0 opacity-0 cursor-pointer" @change="handleThumbnailChange" accept="image/*">
                        </div>
                        <p v-if="form.errors.thumbnail" class="text-red-500 text-[10px] mt-2 font-bold text-center">{{ form.errors.thumbnail }}</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                        <h3 class="text-xs font-extrabold text-gray-500 uppercase tracking-wide">3. Suitability Tags</h3>
                    </div>
                    <div class="p-6 space-y-6 flex-1 overflow-y-auto custom-scrollbar">
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-3 tracking-wider">Skin Type</p>
                            <div class="flex flex-wrap gap-2.5"> <label v-for="type in skinTypes" :key="type" class="cursor-pointer select-none group">
                                    <input type="checkbox" v-model="form.suitability_tags" :value="type" class="hidden">
                                    <span :class="[
                                        'px-4 py-2 rounded-xl text-xs font-bold border transition-all duration-200 inline-block shadow-sm',
                                        form.suitability_tags.includes(type) 
                                            ? 'bg-pink-600 text-white border-pink-600 transform scale-105 shadow-pink-200' 
                                            : 'bg-white text-gray-600 border-gray-200 hover:border-pink-300 hover:text-pink-600'
                                    ]">
                                        {{ type }}
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-3 tracking-wider">Concerns</p>
                            <div class="flex flex-wrap gap-2.5"> <label v-for="concern in skinConcerns" :key="concern" class="cursor-pointer select-none group">
                                    <input type="checkbox" v-model="form.suitability_tags" :value="concern" class="hidden">
                                    <span :class="[
                                        'px-4 py-2 rounded-xl text-xs font-bold border transition-all duration-200 inline-block shadow-sm',
                                        form.suitability_tags.includes(concern) 
                                            ? 'bg-pink-600 text-white border-pink-600 transform scale-105 shadow-pink-200' 
                                            : 'bg-white text-gray-600 border-gray-200 hover:border-pink-300 hover:text-pink-600'
                                    ]">
                                        {{ concern }}
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wide">Product Variants</h3>
                        <p class="text-[10px] text-gray-500 mt-0.5">Add sizes, prices, and stock levels.</p>
                    </div>
                    <button type="button" @click="addVariant" class="text-xs font-bold text-white bg-pink-600 hover:bg-pink-700 px-3 py-1.5 rounded-lg transition-colors shadow-sm shadow-pink-200">
                        + Add Row
                    </button>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[800px]">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 text-xs font-extrabold text-gray-500 uppercase tracking-wider">
                                <th class="px-4 py-3 w-16 text-center">Image</th>
                                <th class="px-4 py-3 w-48">Size / Volume <span class="text-red-500">*</span></th>
                                <th class="px-4 py-3">Price (Rp) <span class="text-red-500">*</span></th>
                                <th class="px-4 py-3 w-28">Stock <span class="text-red-500">*</span></th>
                                <th class="px-4 py-3">SKU Code</th>
                                <th class="px-2 py-3 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="(variant, index) in form.variants" :key="index" class="group hover:bg-pink-50/10 transition-colors">
                                
                                <td class="px-4 py-3 text-center align-middle">
                                    <div class="relative w-10 h-10 mx-auto rounded-lg border border-gray-200 bg-gray-100 overflow-hidden cursor-pointer hover:border-pink-500 transition-colors group/img">
                                        <img v-if="variant.preview" :src="variant.preview" class="w-full h-full object-cover">
                                        <div v-else class="w-full h-full flex items-center justify-center text-gray-300 group-hover/img:text-pink-500">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </div>
                                        <input type="file" class="absolute inset-0 opacity-0 cursor-pointer" @change="(e) => handleVariantImage(index, e)" accept="image/*" title="Change Image">
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-middle">
                                    <div class="flex w-full shadow-sm rounded-lg overflow-hidden border border-gray-300 focus-within:ring-2 focus-within:ring-pink-500 focus-within:border-pink-500 transition-all h-10">
                                        <input v-model="variant.volume_val" type="text" class="w-full px-2 h-full text-sm font-bold text-gray-900 text-center border-none focus:ring-0 placeholder:font-normal" placeholder="0">
                                        <div class="w-px bg-gray-300 h-full"></div>
                                        <div class="relative bg-gray-100 hover:bg-gray-200 transition-colors h-full">
                                            <select v-model="variant.volume_unit" class="block w-[60px] h-full pl-1 pr-4 text-xs font-bold text-gray-700 bg-transparent border-none focus:ring-0 cursor-pointer appearance-none text-center truncate">
                                                <option v-for="opt in UNIT_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                                            </select>
                                            <div class="pointer-events-none absolute inset-y-0 right-0.5 flex items-center px-1 text-gray-500"><svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></div>
                                        </div>
                                    </div>
                                    <p v-if="form.errors[`variants.${index}.volume`]" class="text-red-500 text-[10px] mt-1 font-bold">Required</p>
                                </td>

                                <td class="px-4 py-3 align-middle">
                                    <input v-model="variant.price" type="number" class="w-full px-3 h-10 bg-white border border-gray-300 rounded-lg text-sm text-gray-900 font-medium focus:ring-1 focus:ring-pink-500 focus:border-pink-500" placeholder="0">
                                </td>

                                <td class="px-4 py-3 align-middle">
                                    <input v-model="variant.stock" type="number" class="w-24 px-3 h-10 bg-white border border-gray-300 rounded-lg text-sm text-gray-900 font-medium focus:ring-1 focus:ring-pink-500 focus:border-pink-500" placeholder="0">
                                </td>

                                <td class="px-4 py-3 align-middle">
                                    <input v-model="variant.sku" type="text" class="w-full px-3 h-10 bg-gray-50 border border-gray-300 rounded-lg text-xs text-gray-500 font-mono focus:ring-1 focus:ring-pink-500 focus:border-pink-500 focus:bg-white transition-colors" placeholder="Auto-generated">
                                </td>

                                <td class="px-2 py-3 text-center align-middle">
                                    <button v-if="form.variants.length > 1" type="button" @click="removeVariant(index)" class="text-gray-300 hover:text-red-500 p-2 rounded-md transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="form.variants.length === 0" class="p-8 text-center text-gray-400 text-sm">No variants added. Click "+ Add Row" to start.</div>
            </div>

        </form>
    </AdminLayout>
</template>