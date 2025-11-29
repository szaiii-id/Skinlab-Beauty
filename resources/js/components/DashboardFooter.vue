<script setup>
import { Link } from '@inertiajs/vue3';
import { Heart, HelpCircle } from 'lucide-vue-next';

// Mendapatkan tahun saat ini secara otomatis
const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="bg-white border-t border-rose-100 py-6 px-8 mt-auto">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-sm">
            
            <div class="flex items-center gap-1 text-gray-500 font-medium">
                <span>&copy; {{ currentYear }} <span class="text-rose-600 font-bold">SkinLab Beauty</span>.</span>
                <span class="hidden sm:inline">Made with</span>
                <Heart class="w-3 h-3 text-rose-500 fill-rose-500 hidden sm:block" />
                <span class="hidden sm:inline">for your glow.</span>
            </div>

            <div class="flex items-center gap-6">
                <Link 
                    href="/contact" 
                    class="flex items-center gap-2 text-gray-500 hover:text-rose-600 transition-colors font-medium group"
                >
                    <HelpCircle class="w-4 h-4 text-gray-400 group-hover:text-rose-500 transition-colors" />
                    <span>Need Help?</span>
                </Link>

                <div class="flex items-center gap-4 text-gray-400">
                    <Link href="/terms-of-service" class="hover:text-gray-600 transition-colors">Terms</Link>
                    <Link href="/privacy-policy" class="hover:text-gray-600 transition-colors">Privacy</Link>
                </div>
            </div>

        </div>
    </footer>
</template>