<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    opnames: Array
});

// --- ACTION: START SESSION ---
const startNewSession = () => {
    Swal.fire({
        title: 'New Stock Audit',
        text: "System will freeze the current stock levels. Are you ready to start?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#fb7185', // Soft Rose
        cancelButtonColor: '#94a3b8',  // Soft Slate
        confirmButtonText: 'Start Session',
        background: '#fff',
        customClass: {
            popup: 'rounded-3xl font-sans',
            title: 'text-slate-800',
            confirmButton: 'rounded-full px-6 shadow-lg',
            cancelButton: 'rounded-full px-6'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('admin.stock-opname.store'));
        }
    });
};

// --- ACTION: DELETE SESSION ---
const deleteOpname = (id) => {
    Swal.fire({
        title: 'Delete Session?',
        text: "Are you sure you want to delete this draft? It cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48', // Red
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Delete',
        customClass: {
            popup: 'rounded-3xl font-sans',
            confirmButton: 'rounded-full px-6 shadow-lg',
            cancelButton: 'rounded-full px-6'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.stock-opname.destroy', id));
        }
    });
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        day: 'numeric', month: 'long', year: 'numeric'
    });
};
</script>

<template>
    <AdminLayout title="Stock Opname">
        <div class="font-sans text-slate-600 space-y-8">
            
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Stock Opname</h1>
                    <p class="text-slate-500 mt-2 text-lg font-light">Monitor and validate your inventory accuracy.</p>
                </div>
                
                <button 
                    @click="startNewSession"
                    class="group relative px-8 py-3 bg-gradient-to-r from-rose-400 to-rose-500 text-white rounded-full font-bold shadow-lg shadow-rose-200 hover:shadow-rose-300 transition-all transform hover:-translate-y-1"
                >
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        New Session
                    </span>
                </button>
            </div>

            <div class="grid gap-4">
                <div 
                    v-for="opname in opnames" 
                    :key="opname.id"
                    class="bg-white p-6 rounded-3xl shadow-sm border border-slate-50 hover:shadow-md transition-all duration-300 flex flex-col md:flex-row items-center justify-between gap-6"
                >
                    <div class="flex items-center gap-5 w-full md:w-auto">
                        <div class="h-16 w-16 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center flex-shrink-0">
                            <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-800">{{ opname.opname_number }}</h3>
                            <div class="flex flex-wrap items-center gap-3 mt-1 text-sm text-slate-500 font-medium">
                                <span>{{ formatDate(opname.opname_date) }}</span>
                                <span class="hidden sm:inline w-1 h-1 bg-slate-300 rounded-full"></span>
                                <span class="uppercase text-xs tracking-wider bg-slate-100 px-2 py-0.5 rounded text-slate-600">
                                    {{ opname.created_by || 'ADMIN' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex-shrink-0">
                        <span 
                            class="px-5 py-2 rounded-full text-xs font-bold tracking-wide uppercase border"
                            :class="{
                                'bg-amber-50 text-amber-600 border-amber-100': opname.status === 'processing',
                                'bg-emerald-50 text-emerald-600 border-emerald-100': opname.status === 'completed',
                                'bg-slate-50 text-slate-500 border-slate-100': opname.status === 'draft'
                            }"
                        >
                            {{ opname.status === 'processing' ? 'In Progress' : (opname.status === 'completed' ? 'Completed' : 'Draft') }}
                        </span>
                    </div>

                    <div class="w-full md:w-auto flex items-center gap-2">
                        
                        <button 
                            v-if="opname.status !== 'completed'"
                            @click="deleteOpname(opname.id)"
                            class="h-10 w-10 flex items-center justify-center rounded-full border border-rose-100 text-rose-400 hover:bg-rose-50 hover:text-rose-600 transition"
                            title="Delete Session"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>

                        <Link 
                            :href="route('admin.stock-opname.edit', opname.id)" 
                            class="block w-full md:w-auto text-center px-8 py-3 rounded-full font-bold text-sm transition-all border shadow-sm"
                            :class="opname.status === 'completed' 
                                ? 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50 hover:text-slate-800' 
                                : 'bg-rose-50 text-rose-600 border-rose-100 hover:bg-rose-100 hover:shadow-md'"
                        >
                            {{ opname.status === 'completed' ? 'View Report' : 'Continue Checking' }}
                        </Link>
                    </div>
                </div>

                <div v-if="opnames.length === 0" class="text-center py-24 bg-white rounded-3xl border border-dashed border-slate-200">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                        <svg class="w-8 h-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-700">No Audit History</h3>
                    <p class="mt-1 text-slate-400">Start a new session to verify your stock.</p>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>