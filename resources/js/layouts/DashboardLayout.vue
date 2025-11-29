<!-- resources/js/Layouts/DashboardLayout.vue -->
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DashboardSidebar from '@/components/DashboardSidebar.vue';
import DashboardHeader from '@/components/DashboardHeader.vue';
import DashboardFooter from '@/components/DashboardFooter.vue';
import ScrollToTop from '@/components/ScrollToTop.vue';

defineProps<{
    title?: string;
}>();
</script>

<template>
    <Head :title="title" />

    <div class="flex min-h-screen bg-gradient-to-br from-rose-50 to-pink-50">
        <DashboardSidebar />
        
        <div class="flex-1 flex flex-col">
            <DashboardHeader />
            
            <main class="flex-1 overflow-auto p-6">
                <slot />
            </main>

            <DashboardFooter />
            <ScrollToTop />

        </div>
    </div>
</template>