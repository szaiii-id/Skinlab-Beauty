<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import StaffIdCard from '@/components/StaffIdCard.vue'; // <--- Import
import Swal from 'sweetalert2';

const props = defineProps({
    staff: Object
});

const isEdit = !!props.staff;
const showIdCardModal = ref(false); // <--- Modal State

const form = useForm({
    name: props.staff?.name || '',
    email: props.staff?.email || '',
    role: props.staff?.role || 'warehouse',
    password: '', 
});

const submit = () => {
    const options = {
        onSuccess: () => {
            Swal.fire({
                title: 'Success!',
                text: isEdit ? 'Staff profile updated.' : 'New staff member registered.',
                icon: 'success',
                confirmButtonColor: '#f43f5e',
                customClass: { popup: 'rounded-3xl font-sans' }
            });
        },
        onError: () => {
            Swal.fire({
                title: 'Error',
                text: 'Please check the input fields.',
                icon: 'error',
                confirmButtonColor: '#f43f5e',
                customClass: { popup: 'rounded-3xl font-sans' }
            });
        }
    };

    if (isEdit) {
        form.put(route('admin.staff.update', props.staff.id), options);
    } else {
        form.post(route('admin.staff.store'), options);
    }
};
</script>

<template>
    <AdminLayout :title="isEdit ? 'Edit Staff' : 'Add New Staff'">
        <div class="max-w-3xl mx-auto font-sans">
            
            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-4">
                    <Link :href="route('admin.staff.index')" class="p-3 rounded-full bg-white border border-slate-100 text-slate-400 hover:text-slate-600 hover:shadow-md transition-all">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-800">{{ isEdit ? 'Edit Staff Profile' : 'Register New Staff' }}</h1>
                        <p class="text-slate-500 text-sm">Fill in the details to assign access.</p>
                    </div>
                </div>

                <button 
                    v-if="isEdit"
                    @click="showIdCardModal = true"
                    class="flex items-center gap-2 px-4 py-2 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 rounded-xl text-sm font-bold transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" /></svg>
                    View Badge
                </button>
            </div>

            <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-50">
                <form @submit.prevent="submit" class="space-y-8">
                    
                    <div class="space-y-5">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Personal Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-slate-700">Full Name</label>
                                <input v-model="form.name" type="text" class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-2xl text-slate-800 font-medium placeholder-slate-400 focus:ring-2 focus:ring-rose-200 focus:bg-white transition-all" placeholder="e.g. Sarah Jenkins" required>
                                <p v-if="form.errors.name" class="text-rose-500 text-xs font-medium">{{ form.errors.name }}</p>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-slate-700">Email Address</label>
                                <input v-model="form.email" type="email" class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-2xl text-slate-800 font-medium placeholder-slate-400 focus:ring-2 focus:ring-rose-200 focus:bg-white transition-all" placeholder="staff@skinlab.com" required>
                                <p v-if="form.errors.email" class="text-rose-500 text-xs font-medium">{{ form.errors.email }}</p>
                            </div>
                        </div>
                    </div>

                    <hr class="border-slate-100">

                    <div class="space-y-5">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Access Level</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="cursor-pointer group">
                                <input type="radio" v-model="form.role" value="warehouse" class="peer sr-only">
                                <div class="relative p-5 rounded-2xl border-2 border-slate-100 bg-white hover:border-amber-200 peer-checked:border-amber-400 peer-checked:bg-amber-50/50 transition-all h-full">
                                    <div class="text-amber-500 mb-2 font-bold">Warehouse</div>
                                    <p class="text-xs text-slate-400">Manage products & stock.</p>
                                </div>
                            </label>
                            <label class="cursor-pointer group">
                                <input type="radio" v-model="form.role" value="marketing" class="peer sr-only">
                                <div class="relative p-5 rounded-2xl border-2 border-slate-100 bg-white hover:border-indigo-200 peer-checked:border-indigo-400 peer-checked:bg-indigo-50/50 transition-all h-full">
                                    <div class="text-indigo-500 mb-2 font-bold">Marketing</div>
                                    <p class="text-xs text-slate-400">Manage orders & chat.</p>
                                </div>
                            </label>
                            <label class="cursor-pointer group">
                                <input type="radio" v-model="form.role" value="super_admin" class="peer sr-only">
                                <div class="relative p-5 rounded-2xl border-2 border-slate-100 bg-white hover:border-rose-200 peer-checked:border-rose-400 peer-checked:bg-rose-50/50 transition-all h-full">
                                    <div class="text-rose-500 mb-2 font-bold">Super Admin</div>
                                    <p class="text-xs text-slate-400">Full Access.</p>
                                </div>
                            </label>
                        </div>
                        <p v-if="form.errors.role" class="text-rose-500 text-xs font-medium">{{ form.errors.role }}</p>
                    </div>

                    <hr class="border-slate-100">

                    <div class="space-y-5">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider">Security</h3>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-slate-700">
                                {{ isEdit ? 'New Password (Optional)' : 'Set Password' }}
                            </label>
                            <input v-model="form.password" type="password" class="w-full px-5 py-3.5 bg-slate-50 border-none rounded-2xl text-slate-800 font-medium placeholder-slate-400 focus:ring-2 focus:ring-rose-200 focus:bg-white transition-all" :placeholder="isEdit ? 'Leave empty to keep current password' : 'Min. 6 characters'">
                            <p v-if="form.errors.password" class="text-rose-500 text-xs font-medium">{{ form.errors.password }}</p>
                        </div>
                    </div>

                    <div class="pt-6 flex gap-4">
                        <Link :href="route('admin.staff.index')" class="px-8 py-4 rounded-2xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition-colors">Cancel</Link>
                        <button type="submit" :disabled="form.processing" class="flex-1 px-8 py-4 bg-gradient-to-r from-rose-500 to-pink-600 hover:from-rose-600 hover:to-pink-700 text-white rounded-2xl font-bold shadow-xl shadow-rose-200 transition-all transform hover:-translate-y-1">
                            {{ isEdit ? 'Save Changes' : 'Create Staff Account' }}
                        </button>
                    </div>

                </form>
            </div>
        </div>

        <StaffIdCard 
            v-if="isEdit"
            :show="showIdCardModal" 
            :staff="props.staff" 
            @close="showIdCardModal = false"
        />
    </AdminLayout>
</template>