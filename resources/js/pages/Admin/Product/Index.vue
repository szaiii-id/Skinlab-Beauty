<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import debounce from 'lodash/debounce';
import Swal from 'sweetalert2';

const props = defineProps({
    products: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

// Search Logic
watch(search, debounce((value) => {
    router.get(route('admin.products.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));

// Delete Logic
const deleteProduct = (id: number) => {
    Swal.fire({
        title: 'Delete Product?',
        text: "All variants will be deleted too!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#db2777',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('admin.products.destroy', id), {
                onSuccess: () => Swal.fire('Deleted!', 'Product removed.', 'success')
            });
        }
    });
};
</script>

<template>
    <Head title="Products" />

    <AdminLayout>
        
        <div class="flex flex-col md:flex-row justify-between items-end md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Products</h1>
                <p class="text-sm text-gray-600 mt-1 font-medium">Manage your product catalog and inventory.</p>
            </div>
            
            <Link 
                :href="route('admin.products.create')"
                class="px-5 py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white font-bold rounded-xl shadow-lg shadow-pink-200 hover:shadow-pink-300 hover:-translate-y-0.5 transition-all duration-200 flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Product
            </Link>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-5 border-b border-gray-100 bg-gray-50/30">
                <input 
                    v-model="search" 
                    type="text" 
                    class="w-full max-w-md pl-4 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm text-gray-900 font-medium focus:outline-none focus:ring-2 focus:ring-pink-500 transition-all placeholder:text-gray-400" 
                    placeholder="Search by name, SKU, or brand..."
                >
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-pink-50/30 text-gray-700 text-xs uppercase tracking-wider font-extrabold">
                            <th class="px-6 py-4 rounded-tl-2xl">Product Info</th>
                            <th class="px-6 py-4">Brand & Category</th>
                            <th class="px-6 py-4 text-center">Variants</th>
                            <th class="px-6 py-4 rounded-tr-2xl text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="product in products.data" :key="product.id" class="group hover:bg-pink-50/20 transition-colors">
                            
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-lg bg-gray-100 border border-gray-200 overflow-hidden shrink-0">
                                        <img v-if="product.thumbnail" :src="product.thumbnail" class="w-full h-full object-cover" alt="Thumb">
                                        <div v-else class="w-full h-full flex items-center justify-center text-gray-300">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-gray-900">{{ product.name }}</div>
                                        <div class="text-xs text-gray-500 truncate max-w-[200px]">{{ product.description }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-100 w-fit">
                                        {{ product.brand ? product.brand.name : 'No Brand' }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-100 w-fit">
                                        {{ product.category ? product.category.name : 'No Category' }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-bold text-gray-700">{{ product.variants_count || 0 }}</span>
                                <span class="text-xs text-gray-400 block">Types</span>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <Link :href="route('admin.products.edit', product.id)" class="p-2 text-gray-400 hover:text-pink-600 hover:bg-pink-50 rounded-lg transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                    </Link>
                                    <button @click="deleteProduct(product.id)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="products.data.length === 0">
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400 font-medium">No products found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/30 flex items-center justify-between" v-if="products.data.length > 0">
                <span class="text-xs text-gray-500">
                    Showing {{ products.from }} - {{ products.to }}
                </span>
                <div class="flex gap-1">
                    <Link v-for="(link, k) in products.links" :key="k" :href="link.url || '#'" 
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all"
                        :class="[link.active ? 'bg-pink-600 text-white shadow-md' : 'text-gray-500 hover:bg-white hover:text-pink-600', !link.url ? 'opacity-50' : '']"
                        v-html="link.label" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>