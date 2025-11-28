<script setup>
import { ref, onMounted } from 'vue';
import { listenForMessages } from '@/firebase'; // Import helper tadi
import { X, Bell } from 'lucide-vue-next';

const show = ref(false);
const notification = ref({ title: '', body: '' });
let timeout = null;

onMounted(() => {
    // Aktifkan pendengar Firebase
    listenForMessages((payload) => {
        // Saat pesan masuk, isi data & tampilkan Toast
        notification.value = payload;
        show.value = true;
        
        // Putar suara notifikasi "Ting!" (Opsional)
        playNotificationSound();

        // Auto hide setelah 5 detik
        if (timeout) clearTimeout(timeout);
        timeout = setTimeout(() => {
            show.value = false;
        }, 5000);
    });
});

const closeToast = () => {
    show.value = false;
};

// Suara sederhana (Base64 beep pendek)
const playNotificationSound = () => {
    try {
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3'); // URL sampel beep
        audio.volume = 0.5;
        audio.play().catch(e => console.log("Audio autoplay blocked", e));
    } catch (e) {}
};
</script>

<template>
    <!-- Transisi Animasi -->
    <Transition
        enter-active-class="transform ease-out duration-300 transition"
        enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" class="fixed top-4 right-4 z-[9999] max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <!-- Icon Bell / Logo -->
                        <div class="h-10 w-10 rounded-full bg-rose-100 flex items-center justify-center">
                            <Bell class="h-6 w-6 text-rose-600" />
                        </div>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-bold text-gray-900">{{ notification.title }}</p>
                        <p class="mt-1 text-sm text-gray-500">{{ notification.body }}</p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="closeToast" class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500">
                            <span class="sr-only">Close</span>
                            <X class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>
            <!-- Progress Bar Kecil (Opsional) -->
            <div class="h-1 bg-rose-500 animate-shrink" style="animation-duration: 5s;"></div>
        </div>
    </Transition>
</template>

<style scoped>
@keyframes shrink {
    from { width: 100%; }
    to { width: 0%; }
}
.animate-shrink {
    animation-name: shrink;
    animation-timing-function: linear;
}
</style>