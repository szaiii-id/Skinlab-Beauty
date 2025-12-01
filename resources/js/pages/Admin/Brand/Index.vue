<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

// --- PROPS ---
const props = defineProps({
    brands: Object, // Changed from categories
    filters: Object,
});

const search = ref(props.filters.search || '');
const showModal = ref(false);
const isEditing = ref(false);

const form = useForm({
    id: null,
    name: '',
});

// --- SEARCH LOGIC ---
watch(search, debounce((value) => {
    router.get(route('admin.brands.index'), { search: value }, { // Route changed
        preserveState: true,
        replace: true,
    });
}, 300));

// --- MODAL LOGIC ---
const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEditModal = (brand: any) => {
    isEditing.value = true;
    form.reset();
    form.clearErrors();
    form.id = brand.id;
    form.name = brand.name;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.brands.update', form.id), {
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: 'Brand has been updated.',
                    timer: 1500,
                    showConfirmButton: false,
                    confirmButtonColor: '#db2777'
                });
            },
        });
    } else {
        form.post(route('admin.brands.store'), {
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Created!',
                    text: 'New brand added.',
                    timer: 1500,
                    showConfirmButton: false,
                    confirmButtonColor: '#db2777'
                });
            },
        });
    }
};

const deleteBrand = (id: number) => {
    Swal.fire({
        title: 'Delete Brand?',
        text: "This might affect products linked to this brand!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db2777',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.brands.destroy', id), {
                onSuccess: () => {
                    Swal.fire('Deleted!', 'Brand removed.', 'success');
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Brands" />

    <AdminLayout>
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-end md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Brands</h1>
                <p class="text-sm text-gray-600 mt-1 font-medium">Manage skincare brands (Somethinc, Avoskin, etc).</p>
            </div>
            
            <button 
                @click="openCreateModal"
                class="px-5 py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl shadow-lg shadow-pink-200 hover:shadow-pink-300 hover:-translate-y-0.5 transition-all duration-200 flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Brand
            </button>
        </div>

        <!-- Table Card -->
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <!-- Search -->
            <div class="p-5 border-b border-gray-100 bg-gray-50/30">
                <div class="relative w-full max-w-md">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <input 
                        v-model="search" 
                        type="text" 
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-pink-500 transition-all placeholder:text-gray-400" 
                        placeholder="Search brands..."
                    >
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-pink-50/30 text-gray-700 text-xs uppercase tracking-wider font-extrabold">
                            <th class="px-6 py-4 rounded-tl-2xl">Brand Name</th>
                            <th class="px-6 py-4">Slug</th>
                            <th class="px-6 py-4 text-center">Products</th>
                            <th class="px-6 py-4 rounded-tr-2xl text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="brand in brands.data" :key="brand.id" class="group hover:bg-pink-50/20 transition-colors">
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-gray-900 group-hover:text-pink-700 transition-colors">
                                    {{ brand.name }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600 font-mono border border-gray-200">
                                    /{{ brand.slug }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-600 border border-blue-100">
                                    {{ brand.products_count || 0 }} Products
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="openEditModal(brand)" class="p-2 text-gray-400 hover:text-pink-600 hover:bg-pink-50 rounded-lg transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>
                                    <button @click="deleteBrand(brand.id)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="brands.data.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400 font-medium">No brands found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30 flex items-center justify-between" v-if="brands.data.length > 0">
                <span class="text-xs text-gray-500 font-medium">
                    Showing {{ brands.from }} - {{ brands.to }}
                </span>
                <div class="flex gap-1">
                    <Link v-for="(link, k) in brands.links" :key="k" :href="link.url || '#'" 
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all"
                        :class="[link.active ? 'bg-pink-600 text-white shadow-md' : 'text-gray-500 hover:bg-white hover:text-pink-600', !link.url ? 'opacity-50' : '']"
                        v-html="link.label" />
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div @click="closeModal" class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative z-10 p-6 space-y-5">
                <h3 class="text-lg font-bold text-gray-900">{{ isEditing ? 'Edit Brand' : 'Create Brand' }}</h3>
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="text-xs font-extrabold text-gray-500 uppercase">Brand Name</label>
                        <input v-model="form.name" type="text" class="w-full px-4 py-3 mt-1 bg-white border rounded-xl text-sm font-bold text-gray-900 focus:ring-2 focus:ring-pink-500 transition-all placeholder:text-gray-400" placeholder="e.g. Somethinc" autofocus>
                        <p v-if="form.errors.name" class="text-xs text-red-500 font-bold mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="closeModal" class="flex-1 px-4 py-3 border border-gray-200 text-gray-600 font-bold rounded-xl hover:bg-gray-50 text-sm">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="flex-1 px-4 py-3 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl shadow-lg hover:shadow-pink-300 text-sm">
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Update' : 'Create') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>