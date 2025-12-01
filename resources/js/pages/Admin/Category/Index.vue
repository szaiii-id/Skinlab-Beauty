<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue'; // Sesuaikan path layout Anda
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2'; 

// --- PROPS & STATE ---
const props = defineProps({
    categories: Object,
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
    router.get(route('admin.categories.index'), { search: value }, {
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

const openEditModal = (category: any) => {
    isEditing.value = true;
    form.reset();
    form.clearErrors();
    form.id = category.id;
    form.name = category.name;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.categories.update', form.id), {
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Updated!',
                    text: 'Category has been updated successfully.',
                    timer: 1500,
                    showConfirmButton: false,
                    confirmButtonColor: '#db2777' // Pink-600
                });
            },
        });
    } else {
        form.post(route('admin.categories.store'), {
            onSuccess: () => {
                closeModal();
                Swal.fire({
                    icon: 'success',
                    title: 'Created!',
                    text: 'New category added successfully.',
                    timer: 1500,
                    showConfirmButton: false,
                    confirmButtonColor: '#db2777'
                });
            },
        });
    }
};

// --- DELETE LOGIC (SWEETALERT2) ---
const deleteCategory = (id: number) => {
    Swal.fire({
        title: 'Delete Category?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db2777', // Pink-600
        cancelButtonColor: '#9ca3af', // Gray-400
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        reverseButtons: true, // Tombol cancel di kiri
        background: '#ffffff',
        customClass: {
            popup: 'rounded-2xl font-sans', // Style rounded modern
            confirmButton: 'px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-pink-200',
            cancelButton: 'px-5 py-2.5 rounded-xl font-bold hover:bg-gray-100'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.categories.destroy', id), {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Category has been removed.',
                        icon: 'success',
                        confirmButtonColor: '#db2777',
                        customClass: { popup: 'rounded-2xl font-sans' }
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Categories" />

    <AdminLayout>
        
        <!-- HEADER -->
        <div class="flex flex-col md:flex-row justify-between items-end md:items-center mb-8 gap-4">
            <div>
                <!-- Text Gray-900 agar hitam pekat tajam -->
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Categories</h1>
                <p class="text-sm text-gray-600 mt-1 font-medium">Manage product categories for catalog organization.</p>
            </div>
            
            <button 
                @click="openCreateModal"
                class="px-5 py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl shadow-lg shadow-pink-200 hover:shadow-pink-300 hover:-translate-y-0.5 transition-all duration-200 flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                New Category
            </button>
        </div>

        <!-- CONTENT CARD -->
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            
            <!-- Toolbar -->
            <div class="p-5 border-b border-gray-100 bg-gray-50/30">
                <div class="relative w-full max-w-md">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </span>
                    <!-- Input Text diubah ke Gray-900 -->
                    <input 
                        v-model="search" 
                        type="text" 
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent transition-all shadow-sm placeholder:text-gray-400" 
                        placeholder="Search categories..."
                    >
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <!-- Header Table lebih kontras -->
                        <tr class="bg-pink-50/30 text-gray-700 text-xs uppercase tracking-wider font-extrabold">
                            <th class="px-6 py-4 rounded-tl-2xl">Category Name</th>
                            <th class="px-6 py-4">Slug</th>
                            <th class="px-6 py-4 text-center">Status</th>
                            <th class="px-6 py-4 rounded-tr-2xl text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="category in categories.data" :key="category.id" class="group hover:bg-pink-50/20 transition-colors duration-150">
                            
                            <!-- Name: Hitam Pekat -->
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-gray-900 group-hover:text-pink-700 transition-colors">
                                    {{ category.name }}
                                </span>
                            </td>
                            
                            <!-- Slug -->
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600 font-mono border border-gray-200">
                                    /{{ category.slug }}
                                </span>
                            </td>
                            
                            <!-- Status -->
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide bg-emerald-50 text-emerald-600 border border-emerald-100">
                                    Active
                                </span>
                            </td>
                            
                            <!-- Actions -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button 
                                        @click="openEditModal(category)"
                                        class="p-2 text-gray-400 hover:text-pink-600 hover:bg-pink-50 rounded-lg transition-all"
                                        title="Edit"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </button>
                                    
                                    <button 
                                        @click="deleteCategory(category.id)"
                                        class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                                        title="Delete"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="categories.data.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="p-4 bg-gray-50 rounded-full mb-3">
                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    </div>
                                    <p class="font-medium text-gray-500">No categories found.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30 flex items-center justify-between" v-if="categories.data.length > 0">
                <span class="text-xs text-gray-500 font-medium">
                    Showing data {{ categories.from }} to {{ categories.to }}
                </span>
                <div class="flex gap-1">
                    <Link 
                        v-for="(link, k) in categories.links" 
                        :key="k" 
                        :href="link.url || '#'" 
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all"
                        :class="[
                            link.active 
                                ? 'bg-pink-600 text-white shadow-md shadow-pink-200' 
                                : 'text-gray-500 hover:bg-white hover:text-pink-600 hover:shadow-sm',
                            !link.url ? 'opacity-50 cursor-not-allowed' : ''
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>

        <!-- MODAL -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center px-4">
            <div @click="closeModal" class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
            
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative z-10 overflow-hidden transform transition-all border border-gray-100">
                
                <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-800">
                        {{ isEditing ? 'Edit Category' : 'Create New Category' }}
                    </h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-red-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-5">
                    <div class="space-y-1.5">
                        <label class="text-xs font-extrabold text-gray-500 uppercase tracking-wide">Category Name</label>
                        <!-- Input Text di Modal juga dibuat Hitam Pekat (text-gray-900) -->
                        <input 
                            v-model="form.name"
                            type="text" 
                            class="w-full px-4 py-3 bg-white border rounded-xl text-sm font-bold text-gray-900 focus:outline-none focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 transition-all placeholder:text-gray-400 font-sans"
                            :class="form.errors.name ? 'border-red-500' : 'border-gray-200'"
                            placeholder="e.g., Facial Wash"
                            autofocus
                        >
                        <p v-if="form.errors.name" class="text-xs text-red-500 font-bold mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div class="pt-2 flex gap-3">
                        <button 
                            type="button" 
                            @click="closeModal"
                            class="flex-1 px-4 py-3 border border-gray-200 text-gray-600 font-bold rounded-xl hover:bg-gray-50 transition-colors text-sm"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="flex-1 px-4 py-3 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl shadow-lg shadow-pink-200 hover:shadow-pink-300 transition-all hover:-translate-y-0.5 text-sm flex justify-center items-center"
                        >
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>{{ isEditing ? 'Update Changes' : 'Create Category' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </AdminLayout>
</template>