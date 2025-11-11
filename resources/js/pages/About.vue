<script setup>
import { Head } from '@inertiajs/vue3';
// Kita gunakan layout navbar baru kita
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';

defineOptions({
    layout: AppNavbarLayout
});
</script>

<template>
    <Head title="About Us" />

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-xl overflow-hidden p-8">
                <h1 class="text-4xl font-light text-gray-900 mb-6">
                    About SkinLab Beauty
                </h1>
                
                <div class="prose prose-lg max-w-none">
                    <p>
                        Welcome to SkinLab Beauty, your new destination for curated cosmetic excellence. 
                        Our mission is to bring you the best in skincare and makeup, focusing on ingredients that work 
                        and brands that inspire.
                    </p>
                    <p>
                        Founded in 2025, we believe that beauty is a form of self-care. 
                        We meticulously select every product, ensuring it meets our high standards 
                        of quality, efficacy, and safety.
                    </p>
                    <p>
                        (Ini adalah konten placeholder. Anda bisa mengisinya dengan cerita brand Anda.)
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>