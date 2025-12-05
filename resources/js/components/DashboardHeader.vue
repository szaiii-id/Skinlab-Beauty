<script setup lang="ts">
import { onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Home, LogOut, Sparkles } from 'lucide-vue-next';
import { requestPermission } from '@/firebase'; 

// IMPORT KOMPONEN NOTIFIKASI PINTAR (Lonceng + Toast ada di sini)
import NotificationDropdown from '@/components/NotificationDropdown.vue'; 

const logout = () => { router.post('/logout'); };
const goHome = () => { router.get('/'); };

onMounted(() => {
    // Minta izin notifikasi browser saat komponen ini dimuat
    requestPermission();
});
</script>

<template>
    <header class="sticky top-0 z-30 w-full bg-white/80 backdrop-blur-md border-b border-rose-100 shadow-sm transition-all duration-300 h-20 shrink-0">
        
        <div class="w-full h-full px-6 flex items-center justify-between">
            
            <div class="flex flex-col justify-center">
                <div class="flex items-center gap-2">
                    <div class="bg-rose-100 p-1.5 rounded-lg shadow-sm hidden sm:block">
                        <Sparkles class="w-4 h-4 text-rose-500" />
                    </div>
                    
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 tracking-tight leading-none">Dashboard</h1>
                    </div>
                </div>
                <p class="text-[11px] text-gray-500 font-medium ml-0 sm:ml-8 mt-0.5">Manage your beauty journey</p>
            </div>
            
            <div class="flex items-center gap-3">
                
                <button 
                    @click="goHome" 
                    class="hidden sm:flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-rose-600 bg-transparent hover:bg-rose-50 rounded-full transition-all text-xs font-bold border border-transparent hover:border-rose-100" 
                    title="Back to Home"
                >
                    <Home class="w-4 h-4" /> 
                    <span>Home</span>
                </button>

                <div class="h-6 w-px bg-gray-200 mx-1 hidden sm:block"></div>

                <div class="relative flex items-center">
                    <NotificationDropdown />
                </div>

                <button 
                    @click="logout" 
                    class="flex items-center gap-2 px-4 py-2 text-rose-600 bg-white hover:bg-rose-600 hover:text-white border border-rose-100 hover:border-rose-600 rounded-full transition-all shadow-sm hover:shadow-md text-xs font-bold uppercase tracking-wide group ml-1"
                >
                    <span>Logout</span>
                    <LogOut class="w-3.5 h-3.5 transition-transform group-hover:translate-x-1" /> 
                </button>
            </div>

        </div>
    </header>
</template>