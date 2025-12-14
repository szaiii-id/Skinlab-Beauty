<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffIdCard from '@/components/StaffIdCard.vue'; // <--- Import ini
import Swal from 'sweetalert2';

const props = defineProps({
    staffs: Array,
    filters: Object
});

const search = ref(props.filters.search || '');
const showIdCardModal = ref(false);
const selectedStaff = ref(null);

const handleSearch = () => {
    router.get(route('admin.staff.index'), { search: search.value }, { preserveState: true, replace: true });
};

// Open ID Card Modal
const openIdCard = (staff) => {
    selectedStaff.value = staff;
    showIdCardModal.value = true;
};

const deleteStaff = (id, name) => {
    if(id === usePage().props.auth.user.id) return; 

    Swal.fire({
        title: 'Remove Staff?',
        text: `Are you sure you want to remove "${name}"? This cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'Yes, Remove',
        cancelButtonText: 'Cancel',
        customClass: { popup: 'rounded-3xl font-sans' }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.staff.destroy', id));
        }
    });
};

const toggleStatus = (staff) => {
    if(staff.id === usePage().props.auth.user.id) return;

    router.patch(route('admin.staff.toggle', staff.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            const msg = staff.is_active ? 'Account Deactivated' : 'Account Activated';
            Swal.fire({
                toast: true, position: 'top', icon: 'success', 
                title: msg, showConfirmButton: false, timer: 1500,
                customClass: { popup: 'rounded-xl' }
            });
        }
    });
};
</script>

<template>
    <AdminLayout title="Staff Management">
        <div class="font-sans text-slate-600 space-y-8">
            
            <div class="flex flex-col md:flex-row justify-between items-center gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800 tracking-tight">Staff & Roles</h1>
                    <p class="text-slate-500 mt-2 text-lg font-light">Manage team access and permissions.</p>
                </div>
                
                <Link 
                    :href="route('admin.staff.create')"
                    class="group relative px-8 py-3 bg-gradient-to-r from-rose-400 to-pink-500 text-white rounded-full font-bold shadow-lg shadow-rose-200 hover:shadow-rose-300 transition-all transform hover:-translate-y-1"
                >
                    <span class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Add New Staff
                    </span>
                </Link>
            </div>

            <div class="relative max-w-md">
                <input 
                    v-model="search"
                    @keyup.enter="handleSearch"
                    type="text" 
                    placeholder="Search by name or email..." 
                    class="w-full pl-12 pr-4 py-3 bg-white border border-slate-100 rounded-2xl text-slate-800 font-medium focus:ring-2 focus:ring-rose-200 focus:border-rose-300 transition-all shadow-sm placeholder-slate-400"
                >
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="staff in staffs" 
                    :key="staff.id"
                    class="bg-white p-6 rounded-3xl border border-slate-50 shadow-sm hover:shadow-lg hover:shadow-rose-50/50 transition-all duration-300 relative group"
                    :class="{'opacity-75 grayscale': !staff.is_active}"
                >
                    <div class="absolute top-6 right-6 flex items-center gap-2">
                        <span class="h-2.5 w-2.5 rounded-full ring-2 ring-white" 
                              :class="staff.is_active ? 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]' : 'bg-slate-300'"></span>
                    </div>

                    <div class="flex items-center gap-4 mb-5">
                        <div class="h-16 w-16 rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-2xl font-bold text-slate-500 shadow-inner">
                            {{ staff.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 text-lg leading-tight line-clamp-1">{{ staff.name }}</h3>
                            <p class="text-sm text-slate-400 mt-1 line-clamp-1">{{ staff.email }}</p>
                        </div>
                    </div>

                    <div class="mb-6">
                        <span 
                            class="px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide border"
                            :class="{
                                'bg-rose-50 text-rose-600 border-rose-100': staff.role === 'super_admin',
                                'bg-amber-50 text-amber-600 border-amber-100': staff.role === 'warehouse',
                                'bg-indigo-50 text-indigo-600 border-indigo-100': staff.role === 'marketing'
                            }"
                        >
                            {{ staff.role.replace('_', ' ') }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between pt-4 border-t border-slate-50">
                        <button 
                            v-if="staff.id !== $page.props.auth.user.id"
                            @click="toggleStatus(staff)"
                            class="text-xs font-bold uppercase tracking-wide transition-colors flex items-center gap-2"
                            :class="staff.is_active ? 'text-slate-400 hover:text-amber-500' : 'text-emerald-600 hover:text-emerald-700'"
                        >
                            <svg v-if="staff.is_active" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ staff.is_active ? 'Suspend' : 'Activate' }}
                        </button>
                        <span v-else class="text-xs font-medium text-slate-300 italic">You</span>

                        <div class="flex gap-2">
                            <button 
                                @click="openIdCard(staff)"
                                class="p-2 text-slate-400 hover:text-indigo-500 hover:bg-indigo-50 rounded-xl transition"
                                title="View ID Card"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                            </button>

                            <Link :href="route('admin.staff.edit', staff.id)" class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                            </Link>
                            
                            <button 
                                v-if="staff.id !== $page.props.auth.user.id"
                                @click="deleteStaff(staff.id, staff.name)" 
                                class="p-2 text-slate-400 hover:text-rose-500 hover:bg-rose-50 rounded-xl transition"
                            >
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="staffs.length === 0" class="text-center py-16">
                <p class="text-slate-400">No staff members found matching your search.</p>
            </div>
        </div>

        <StaffIdCard 
            :show="showIdCardModal" 
            :staff="selectedStaff" 
            @close="showIdCardModal = false"
        />
    </AdminLayout>
</template>