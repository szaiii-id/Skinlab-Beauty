<script setup lang="ts">
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-gradient-to-br from-rose-50 to-pink-50 p-6 md:p-10"
    >
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_center,_var(--tw-gradient-stops))] from-rose-100/20 to-transparent"></div>
            <div class="absolute top-0 left-0 w-72 h-72 bg-rose-200/30 rounded-full blur-3xl -translate-y-1/2 -translate-x-1/2"></div>
            <div class="absolute bottom-0 right-0 w-72 h-72 bg-pink-200/30 rounded-full blur-3xl translate-y-1/2 translate-x-1/2"></div>
        </div>

        <div class="relative w-full max-w-2xl">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-6">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-4 font-medium group"
                    >
                        <div class="text-center space-y-4">
                            <div class="text-5xl font-light text-rose-800 tracking-wider bg-gradient-to-r from-rose-700 to-pink-600 bg-clip-text text-transparent">
                                SkinLab
                            </div>
                            <div class="text-lg text-rose-600/80 font-semibold tracking-widest uppercase letter-spacing-2">
                                B E A U T Y
                            </div>
                            <div class="w-24 h-1.5 bg-gradient-to-r from-rose-400 to-pink-400 rounded-full mx-auto mt-4"></div>
                        </div>
                    </Link>
                    
                    <div class="space-y-4 text-center">
                        <h1 class="text-3xl font-light text-rose-800 tracking-wide">{{ title }}</h1>
                        <p class="text-lg text-rose-600/80 leading-relaxed max-w-md mx-auto">
                            {{ description }}
                        </p>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl shadow-2xl border border-rose-100/80 p-10 transition-all duration-300 w-full">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.letter-spacing-2 {
    letter-spacing: 0.2em;
}
</style>