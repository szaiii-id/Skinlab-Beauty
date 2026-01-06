<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DashboardSidebar from '@/components/DashboardSidebar.vue';
import DashboardHeader from '@/components/DashboardHeader.vue';
import DashboardFooter from '@/components/DashboardFooter.vue';
import ScrollToTop from '@/components/ScrollToTop.vue';
import MobileBottomNav from '@/components/MobileBottomNav.vue'; // <--- Import Komponen Baru

defineProps<{
    title?: string;
}>();
defineOptions({
    inheritAttrs: false
});
</script>

<template>
    <Head :title="title" />

    <div class="flex min-h-screen bg-gradient-to-br from-rose-50 to-pink-50 pb-24 lg:pb-0">
        
        <div class="hidden lg:block">
            <DashboardSidebar />
        </div>
        
        <div class="flex-1 flex flex-col min-w-0">
            <DashboardHeader />
            
            <main class="flex-1 overflow-auto p-4 sm:p-6">
                <slot />
            </main>

            <div class="hidden lg:block">
                <DashboardFooter />
            </div>
            
            <ScrollToTop />
        </div>

        <MobileBottomNav />

    </div>
</template>