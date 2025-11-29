<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { listenForMessages } from '@/firebase';
import { 
    Bell, LogOut, Home, X, Trash2 
} from 'lucide-vue-next';

// --- STATE ---
// Data dummy awal
const notifications = ref<Array<{ title: string; body: string; time: string }>>([]);

const showToast = ref(false);
const showDropdown = ref(false); // State untuk membuka dropdown
const toastData = ref({ title: '', body: '' });
let toastTimeout: any = null;

// --- ACTIONS ---
const logout = () => { router.post('/logout'); };
const goHome = () => { router.get('/'); };
const closeToast = () => { showToast.value = false; };

// Toggle Dropdown Notifikasi
const toggleNotifications = () => {
    showDropdown.value = !showDropdown.value;
};

// Hapus Semua Notifikasi (Reset Badge)
const clearNotifications = () => {
    notifications.value = [];
    showDropdown.value = false;
};

// Hapus Satu Notifikasi
const removeNotification = (index: number) => {
    notifications.value.splice(index, 1);
};

const playNotificationSound = () => {
    try {
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
        audio.volume = 0.5;
        audio.play().catch(() => {});
    } catch (e) {}
};

// --- LIFECYCLE ---
onMounted(() => {
    listenForMessages((payload: any) => {
        // 1. Tampilkan Toast
        toastData.value = { title: payload.title, body: payload.body };
        showToast.value = true;
        playNotificationSound();

        if (toastTimeout) clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => showToast.value = false, 5000);

        // 2. Tambah ke List (Badge bertambah)
        notifications.value.unshift({
            title: payload.title,
            body: payload.body,
            time: 'Baru saja'
        });
    });
});
</script>

<template>
    <header class="bg-white border-b border-rose-200 shadow-sm sticky top-0 z-40 relative">
        <div class="flex items-center justify-between px-6 py-4">
            
            <!-- Judul -->
            <div>
                <slot name="title"><h1 class="text-2xl font-light text-rose-800">Dashboard</h1></slot>
                <slot name="subtitle"><p class="text-rose-600/80 text-sm mt-1">Selamat datang kembali</p></slot>
            </div>
            
            <!-- Actions -->
            <div class="flex items-center gap-3">
                <button @click="goHome" class="flex items-center gap-2 px-4 py-2 text-gray-600 hover:text-rose-600 border border-gray-200 hover:border-rose-300 rounded-lg hover:bg-rose-50 transition-all">
                    <Home class="w-5 h-5" /> <span class="hidden sm:inline">Beranda</span>
                </button>

                <!-- BUTTON NOTIFIKASI (Sekarang Bisa Diklik) -->
                <div class="relative">
                    <button 
                        @click="toggleNotifications"
                        class="flex items-center gap-2 px-4 py-2 text-rose-600 hover:text-rose-700 border border-rose-300 hover:border-rose-400 rounded-lg hover:bg-rose-50 transition-all"
                        :class="{'bg-rose-50 border-rose-400': showDropdown}"
                    >
                        <Bell class="w-5 h-5" />
                        <span class="hidden sm:inline">Notifikasi</span>
                    </button>
                    
                    <!-- Badge Merah -->
                    <span v-if="notifications.length > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full flex items-center justify-center shadow-sm animate-pulse">
                        {{ notifications.length }}
                    </span>

                    <!-- DROPDOWN LIST NOTIFIKASI (BARU) -->
                    <div v-if="showDropdown" class="absolute right-0 mt-3 w-80 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50 animate-in fade-in slide-in-from-top-2">
                        <div class="p-3 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                            <h3 class="font-semibold text-gray-700 text-sm">Notifikasi</h3>
                            <button v-if="notifications.length > 0" @click="clearNotifications" class="text-xs text-rose-600 hover:text-rose-800 hover:underline">
                                Tandai semua dibaca
                            </button>
                        </div>
                        
                        <div class="max-h-64 overflow-y-auto">
                            <div v-if="notifications.length === 0" class="p-6 text-center text-gray-400 text-sm">
                                Tidak ada notifikasi baru.
                            </div>
                            <ul v-else>
                                <li v-for="(notif, index) in notifications" :key="index" class="p-4 border-b border-gray-50 hover:bg-rose-50/50 transition-colors relative group">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-sm font-bold text-gray-800">{{ notif.title }}</p>
                                            <p class="text-xs text-gray-600 mt-1 leading-relaxed">{{ notif.body }}</p>
                                            <p class="text-[10px] text-gray-400 mt-2">{{ notif.time }}</p>
                                        </div>
                                        <button @click="removeNotification(index)" class="text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity p-1">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <!-- END DROPDOWN -->
                </div>

                <button @click="logout" class="flex items-center gap-2 px-4 py-2 text-rose-600 hover:text-white border border-rose-300 hover:border-rose-600 hover:bg-rose-600 rounded-lg transition-all">
                    <LogOut class="w-5 h-5" /> <span class="hidden sm:inline">Keluar</span>
                </button>
            </div>
        </div>
    </header>

    <!-- TOAST (Sama seperti sebelumnya) -->
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="showToast" class="fixed top-24 right-6 z-[100] max-w-sm w-full bg-white shadow-xl rounded-xl ring-1 ring-black ring-opacity-5 overflow-hidden border-l-4 border-rose-500">
            <div class="p-4 flex items-start">
                <div class="flex-shrink-0"><Bell class="h-5 w-5 text-rose-600" /></div>
                <div class="ml-3 w-0 flex-1 pt-0.5">
                    <p class="text-sm font-bold text-gray-900">{{ toastData.title }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ toastData.body }}</p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button @click="closeToast" class="text-gray-400 hover:text-gray-500"><X class="h-5 w-5" /></button>
                </div>
            </div>
        </div>
    </Transition>
    
    <!-- Backdrop untuk menutup dropdown saat klik luar -->
    <div v-if="showDropdown" class="fixed inset-0 z-30" @click="showDropdown = false"></div>
</template>