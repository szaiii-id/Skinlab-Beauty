<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Bell, Gift, Info, Check, X, Trash2, ArrowRight } from 'lucide-vue-next';
import { router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { listenForMessages } from '@/firebase';
import Swal from 'sweetalert2';

const notifications = ref([]);
const unreadCount = ref(0);
const isOpen = ref(false);

// State Toast
const showToast = ref(false);
const toastData = ref({ title: '', body: '' });
let toastTimeout = null;

// --- API ---
const fetchNotifications = async () => {
    try {
        const res = await axios.get(route('notifications.index'));
        notifications.value = res.data.notifications;
        unreadCount.value = res.data.unread_count;
    } catch (error) {
        console.error(error);
    }
};

const markRead = async (notif) => {
    if (!notif.read_at) {
        await axios.post(route('notifications.read', notif.id));
        unreadCount.value = Math.max(0, unreadCount.value - 1);
        const idx = notifications.value.findIndex(n => n.id === notif.id);
        if (idx !== -1) notifications.value[idx].read_at = new Date().toISOString();
    }
    isOpen.value = false;
    if (notif.data.action_url) router.visit(notif.data.action_url);
};

const markAllRead = () => {
    if (unreadCount.value === 0) return;
    router.post(route('notifications.readAll'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            unreadCount.value = 0;
            notifications.value.forEach(n => n.read_at = new Date());
        }
    });
};

const clearAllNotifications = () => {
    Swal.fire({
        title: 'Clear History?',
        text: "Delete all notifications?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        confirmButtonText: 'Yes, Clear',
        customClass: { popup: 'rounded-2xl' }
    }).then((result) => {
        if (result.isConfirmed) {
            // Pastikan route ini ada di web.php: Route::delete('/notifications/clear', ...)
            router.delete(route('notifications.clear'), {
                preserveScroll: true,
                onSuccess: () => {
                    notifications.value = [];
                    unreadCount.value = 0;
                }
            });
        }
    });
};

// --- REALTIME ---
const playSound = () => {
    try {
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
        audio.volume = 0.5;
        audio.play().catch(() => {});
    } catch (e) {}
};

onMounted(() => {
    fetchNotifications();
    listenForMessages((payload) => {
        toastData.value = { title: payload.title, body: payload.body };
        showToast.value = true;
        playSound();
        unreadCount.value++;
        notifications.value.unshift({
            id: Date.now(),
            data: { title: payload.title, message: payload.body, type: 'gift', time: 'Just now' },
            created_at: new Date().toISOString(),
            read_at: null
        });
        if (toastTimeout) clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => showToast.value = false, 5000);
    });
});
</script>

<template>
    <div class="relative">
        <button @click="isOpen = !isOpen" class="relative p-2 rounded-full text-gray-500 hover:text-rose-600 hover:bg-rose-50 transition-all">
            <Bell class="w-6 h-6" />
            <span v-if="unreadCount > 0" class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 ring-2 ring-white text-[10px] font-bold text-white animate-bounce">
                {{ unreadCount }}
            </span>
        </button>

        <div v-if="isOpen" @click="isOpen = false" class="fixed inset-0 z-40 bg-transparent cursor-default"></div>

        <div v-if="isOpen" class="absolute right-0 mt-3 w-80 md:w-96 bg-white rounded-2xl shadow-xl border border-gray-100 z-50 origin-top-right animate-in fade-in zoom-in-95 duration-200 overflow-hidden flex flex-col">
            
            <div class="px-5 py-3 border-b border-gray-50 flex justify-between items-center bg-gray-50/50 shrink-0">
                <h3 class="font-bold text-gray-900 text-sm">Notifications</h3>
                <div class="flex items-center gap-3">
                    <button v-if="unreadCount > 0" @click="markAllRead" class="text-[10px] font-bold text-rose-600 hover:underline flex items-center gap-1" title="Mark all as read">
                        <Check class="w-3 h-3" /> Read All
                    </button>
                    <button v-if="notifications.length > 0" @click="clearAllNotifications" class="text-[10px] font-bold text-gray-400 hover:text-red-500 flex items-center gap-1 transition-colors" title="Delete history">
                        <Trash2 class="w-3 h-3" /> Clear
                    </button>
                </div>
            </div>
            
            <div class="overflow-y-auto custom-scrollbar" style="max-height: 350px;">
                <div v-if="notifications.length === 0" class="p-10 text-center text-gray-400">
                    <Bell class="w-10 h-10 mx-auto mb-2 opacity-20" />
                    <p class="text-xs">No notifications yet.</p>
                </div>

                <div v-else>
                    <div v-for="notif in notifications" :key="notif.id" @click="markRead(notif)" class="px-5 py-4 border-b border-gray-50 hover:bg-rose-50/30 cursor-pointer flex gap-4 transition-colors" :class="{'bg-white': notif.read_at, 'bg-blue-50/30': !notif.read_at}">
                        <div class="shrink-0 mt-1">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center shadow-sm" :class="notif.data.type === 'gift' ? 'bg-rose-100 text-rose-600' : 'bg-gray-100 text-gray-500'">
                                <Gift v-if="notif.data.type === 'gift'" class="w-5 h-5" />
                                <Info v-else class="w-5 h-5" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start">
                                <p class="text-sm font-bold text-gray-900 truncate pr-2">{{ notif.data.title }}</p>
                                <span class="text-[10px] text-gray-400 whitespace-nowrap">{{ notif.data.time || 'Just now' }}</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-0.5 leading-relaxed line-clamp-2">{{ notif.data.message }}</p>
                        </div>
                        <div v-if="!notif.read_at" class="shrink-0 self-center">
                            <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 p-0 shrink-0 border-t border-gray-100">
                <Link 
                    href="/rewards" 
                    @click="isOpen = false" 
                    class="flex items-center justify-center gap-2 w-full py-3 text-xs font-bold text-rose-600 hover:bg-rose-100 transition-colors"
                >
                    View Reward Catalog <ArrowRight class="w-3 h-3" />
                </Link>
            </div>

        </div>

        <Teleport to="body">
            <Transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-[-1rem] opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showToast" class="fixed top-24 right-6 z-[9999] max-w-sm w-full bg-white shadow-2xl rounded-2xl pointer-events-auto border border-rose-100 overflow-hidden ring-1 ring-black ring-opacity-5">
                    <div class="p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-rose-100 flex items-center justify-center animate-pulse">
                                    <Gift class="h-5 w-5 text-rose-600" />
                                </div>
                            </div>
                            <div class="flex-1 pt-0.5">
                                <p class="text-sm font-bold text-gray-900">{{ toastData.title }}</p>
                                <p class="mt-1 text-xs text-gray-500 leading-relaxed">{{ toastData.body }}</p>
                            </div>
                            <button @click="showToast = false" class="text-gray-400 hover:text-gray-600"><X class="h-4 w-4" /></button>
                        </div>
                    </div>
                    <div class="h-1 bg-gradient-to-r from-rose-400 to-pink-600 animate-shrink" style="animation-duration: 5s;"></div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
@keyframes shrink { from { width: 100%; } to { width: 0%; } }
.animate-shrink { animation-name: shrink; animation-timing-function: linear; }
</style>