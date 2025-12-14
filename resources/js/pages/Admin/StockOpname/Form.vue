<script setup>
import { ref, computed } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    opname: Object 
});

const searchQuery = ref('');

// --- PERBAIKAN STRUKTUR DATA (OBJECT MAP) ---
// Kita ubah array menjadi Object: { id_item: qty }
// Ini mencegah error "find" yang membuat data selain baris pertama gagal tersimpan
const initialData = {};
if (props.opname.items) {
    props.opname.items.forEach(item => {
        initialData[item.id] = item.physical_qty;
    });
}

const form = useForm({
    items: initialData 
});

// Logic Pencarian (Hanya untuk filter tampilan)
const filteredItems = computed(() => {
    return props.opname.items.filter(item => {
        const text = searchQuery.value.toLowerCase();
        return (item.variant?.product?.name?.toLowerCase().includes(text) || 
                item.variant?.sku?.toLowerCase().includes(text));
    });
});

// Helper: Ambil nilai fisik langsung dari Form Object (Cepat & Akurat)
const getPhysicalVal = (itemId) => {
    return form.items[itemId];
};

const getDiff = (system, physical) => {
    if (physical === null || physical === '') return 0;
    return physical - system;
};

// Helper visual untuk Completed View
const getFinalPhysical = (item) => {
    return item.physical_qty ?? item.system_qty;
};

const Toast = Swal.mixin({
    toast: true,
    position: 'top',
    showConfirmButton: false,
    timer: 2000,
    background: '#ffffff',
    color: '#334155',
    customClass: { popup: 'shadow-xl rounded-xl border border-slate-100' }
});

const saveDraft = () => {
    form.put(route('admin.stock-opname.update', props.opname.id), {
        preserveScroll: true,
        onSuccess: () => Toast.fire({ icon: 'success', title: 'Progress Saved' })
    });
};

const finishOpname = () => {
    Swal.fire({
        title: 'Finalize & Update?',
        text: "This will permanently update your master stock data.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981', 
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Yes, Update Stock',
        cancelButtonText: 'Cancel',
        customClass: {
            popup: 'rounded-3xl font-sans',
            confirmButton: 'rounded-full px-6 shadow-lg',
            cancelButton: 'rounded-full px-6'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(route('admin.stock-opname.finish', props.opname.id));
        }
    });
};
</script>

<template>
    <AdminLayout title="Input Stock Opname">
        <div class="h-[calc(100vh-6rem)] flex flex-col font-sans bg-slate-50/50">
            
            <div class="bg-white/90 backdrop-blur-md border-b border-slate-200 px-6 py-4 z-20 sticky top-0 flex flex-col md:flex-row justify-between items-center gap-4 transition-all">
                <div class="flex items-center gap-4 w-full md:w-auto">
                    <Link :href="route('admin.stock-opname.index')" class="h-10 w-10 flex items-center justify-center rounded-full bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-800 transition shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">{{ props.opname.opname_number }}</h1>
                        <p class="text-sm text-slate-500">{{ props.opname.items.length }} Items &bull; {{ new Date(props.opname.opname_date).toLocaleDateString() }}</p>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full sm:w-64">
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            placeholder="Search product..." 
                            class="w-full pl-4 pr-4 py-2.5 bg-slate-100 border-none rounded-full text-sm text-slate-700 font-medium focus:ring-2 focus:ring-rose-200 focus:bg-white transition-all placeholder-slate-400"
                        >
                    </div>

                    <template v-if="props.opname.status !== 'completed'">
                        <button @click="saveDraft" :disabled="form.processing" class="w-full sm:w-auto px-6 py-2.5 rounded-full text-sm font-bold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition shadow-sm">
                            Save Draft
                        </button>
                        <button @click="finishOpname" :disabled="form.processing" class="w-full sm:w-auto px-6 py-2.5 rounded-full text-sm font-bold text-white bg-rose-500 hover:bg-rose-600 shadow-lg shadow-rose-200 transition">
                            Finish Audit
                        </button>
                    </template>
                </div>
            </div>

            <div class="flex-1 overflow-auto p-4 sm:p-6">
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <table class="w-full text-left">
                        <thead class="bg-slate-50/50 border-b border-slate-100 sticky top-0 z-10 backdrop-blur-sm">
                            <tr>
                                <th class="px-8 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider text-center">System</th>
                                <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider text-center w-40">Physical</th>
                                <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider text-center">Variance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="item in filteredItems" :key="item.id" class="hover:bg-rose-50/30 transition-colors group">
                                
                                <td class="px-8 py-4">
                                    <div class="flex items-center gap-4">
                                        <div class="h-14 w-14 rounded-xl bg-slate-100 overflow-hidden border border-slate-100 flex-shrink-0">
                                            <img v-if="item.variant.image_url" :src="item.variant.image_url" class="h-full w-full object-cover">
                                            <div v-else class="h-full w-full flex items-center justify-center text-[10px] text-slate-400 font-bold uppercase">No Img</div>
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-800 text-sm">{{ item.variant.product.name }}</div>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-xs font-semibold">{{ item.variant.volume }}</span>
                                                <span class="text-xs font-mono text-slate-400 uppercase">{{ item.variant.sku }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <span class="text-slate-500 font-bold bg-slate-100/80 px-4 py-1.5 rounded-lg text-sm border border-slate-100">
                                        {{ item.system_qty }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <input 
                                        v-if="props.opname.status !== 'completed'"
                                        type="number" 
                                        v-model="form.items[item.id]"
                                        class="w-full text-center font-bold text-lg text-slate-800 bg-slate-100 border-transparent rounded-xl focus:bg-white focus:border-rose-300 focus:ring-4 focus:ring-rose-100 transition-all py-2 placeholder-slate-300"
                                        placeholder="-"
                                    >
                                    <span v-else class="block text-center font-bold text-lg text-slate-800">
                                        {{ getFinalPhysical(item) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-center">
                                    <template v-if="props.opname.status !== 'completed'">
                                        <div v-if="getPhysicalVal(item.id) === null || getPhysicalVal(item.id) === ''" class="text-slate-300 text-xs font-medium italic">
                                            Waiting Input...
                                        </div>
                                        <div v-else>
                                            <div v-if="getDiff(item.system_qty, getPhysicalVal(item.id)) === 0" 
                                                    class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">
                                                Match
                                            </div>
                                            <div v-else-if="getDiff(item.system_qty, getPhysicalVal(item.id)) < 0" 
                                                    class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-rose-50 text-rose-600 border border-rose-100">
                                                Loss {{ getDiff(item.system_qty, getPhysicalVal(item.id)) }}
                                            </div>
                                            <div v-else 
                                                    class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-blue-50 text-blue-600 border border-blue-100">
                                                Surplus +{{ getDiff(item.system_qty, getPhysicalVal(item.id)) }}
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <template v-else>
                                        <div v-if="(getFinalPhysical(item) - item.system_qty) === 0" 
                                            class="text-slate-300 font-bold text-xl">
                                            -
                                        </div>
                                        <div v-else>
                                            <div v-if="(getFinalPhysical(item) - item.system_qty) < 0" 
                                                class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-rose-50 text-rose-600 border border-rose-100">
                                                Loss {{ getFinalPhysical(item) - item.system_qty }}
                                            </div>
                                            <div v-else 
                                                class="inline-flex items-center px-4 py-1.5 rounded-full text-xs font-bold bg-blue-50 text-blue-600 border border-blue-100">
                                                Surplus +{{ getFinalPhysical(item) - item.system_qty }}
                                            </div>
                                        </div>
                                    </template>
                                </td>

                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>