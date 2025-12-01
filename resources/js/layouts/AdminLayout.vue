<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminSidebar from '@/components/Admin/AdminSidebar.vue';
import AdminHeader from '@/components/Admin/AdminHeader.vue';
import AdminFooter from '@/components/Admin/AdminFooter.vue';

// Definisi Props untuk Title Halaman
defineProps<{
    title?: string;
}>();
</script>

<template>
    <Head :title="title ? `${title} - Skin Lab Center` : 'Skin Lab Center'" />

    <div class="flex min-h-screen bg-gray-50">
        
        <AdminSidebar />
        
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <AdminHeader />
            
            <main class="flex-1 overflow-auto p-6">
                <slot />
            </main>

            <AdminFooter />

        </div>
    </div>
</template>