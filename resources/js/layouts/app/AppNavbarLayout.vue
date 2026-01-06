<script setup>
import AppNavbar from '@/components/AppNavbar.vue'; 
import AppFooter from '@/components/AppFooter.vue';
import ScrollToTop from '@/components/ScrollToTop.vue';

</script>

<template>
    <div class="min-h-screen bg-rose-50 pb-20 md:pb-0">
        
        <AppNavbar />

        <main>
            <slot />
        </main>
        
        <AppFooter /> 
        <ScrollToTop />
    </div>
</template>