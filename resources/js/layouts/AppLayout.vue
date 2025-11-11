<script setup lang="ts">
import AppLayout from '@/layouts/app/AppNavbarLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <aside class="w-64 bg-white border-r border-gray-200">
        <nav class="flex flex-col space-y-2 p-4 mt-4">
            
            <Link 
                href="/"
                class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-rose-50 hover:text-rose-600"
            >
                Catalog
            </Link>
            
            <Link 
                href="/cart" 
                class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-rose-50 hover:text-rose-600"
            >
                Cart
            </Link>
            
            <Link 
                href="/dashboard"
                class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-rose-50 hover:text-rose-600"
            >
                Dashboard
            </Link>

            </nav>
    </aside>
</template>
