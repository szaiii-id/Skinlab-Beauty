<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Searchbar from '@/components/Searchbar.vue';
import { listenForMessages } from '@/firebase'; // Import Helper Firebase
import { 
    ShoppingBag, 
    ShoppingCart, 
    Heart,
    ChevronDown,
    User,
    Info,
    Home,
    Bell, // Icon Lonceng Baru
    Trash2 // Icon Hapus
} from 'lucide-vue-next';

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const page = usePage();
const categories = computed(() => page.props.categories || []);
const brands = computed(() => page.props.brands || []);
const cartCount = computed(() => page.props.cartCount || 0);
const user = computed(() => page.props.auth.user);

// Ambil jumlah pending order dari props (backend)
const pendingOrdersCount = computed(() => page.props.pendingOrdersCount || 0);

// Wishlist Logic
const wishlistCount = ref(page.props.wishlistCount || 0);

watch(() => page.props.wishlistCount, (newCount) => {
    wishlistCount.value = newCount || 0;
});

const handleWishlistUpdate = (event: any) => {
    wishlistCount.value = event.detail.count;
};

// --- LOGIC NOTIFIKASI FIREBASE (BARU) ---
const notificationCount = ref(0);
const showNotifDropdown = ref(false);
const notifications = ref<Array<{ title: string, body: string, time: string }>>([]);

// Load notifikasi dari localStorage saat mounted
const loadNotifications = () => {
    try {
        const stored = localStorage.getItem('notifications');
        if (stored) {
            notifications.value = JSON.parse(stored);
            notificationCount.value = notifications.value.length;
        }
    } catch (e) {
        console.error("Gagal load notifikasi lokal", e);
    }
};

const toggleNotifications = () => {
    showNotifDropdown.value = !showNotifDropdown.value;
};

const clearNotifications = () => {
    notifications.value = [];
    notificationCount.value = 0;
    localStorage.removeItem('notifications');
    showNotifDropdown.value = false;
};

const removeNotification = (index: number) => {
    notifications.value.splice(index, 1);
    notificationCount.value = notifications.value.length;
    localStorage.setItem('notifications', JSON.stringify(notifications.value));
};

const playNotificationSound = () => {
    try {
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
        audio.volume = 0.5;
        audio.play().catch(() => {});
    } catch (e) {}
};

onMounted(() => {
    // 1. Wishlist Listener
    window.addEventListener('wishlist-updated', handleWishlistUpdate);

    // 2. Load Notifikasi Awal
    loadNotifications();

    // 3. Listen Firebase Message (Foreground)
    listenForMessages((payload: any) => {
        console.log("Notifikasi masuk di Navbar:", payload);
        playNotificationSound();
        
        // Tambah ke state
        const newNotif = {
            title: payload.title,
            body: payload.body,
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        };
        
        notifications.value.unshift(newNotif);
        notificationCount.value = notifications.value.length;
        
        // Simpan ke Storage agar persisten antar halaman
        localStorage.setItem('notifications', JSON.stringify(notifications.value));
    });

    // 4. Listen Event Manual (Jika update dari tab lain/komponen lain)
    window.addEventListener('notification-updated', loadNotifications);
});

onUnmounted(() => {
    window.removeEventListener('wishlist-updated', handleWishlistUpdate);
    window.removeEventListener('notification-updated', loadNotifications);
});
// ------------------------------------------

// Helper Kondisi Aktif
const isHomeActive = computed(() => page.url === '/');
const isCatalogActive = computed(() => page.url === '/catalog' || page.url.startsWith('/products'));
const isAboutActive = computed(() => page.url === '/about');
const isWishlistActive = computed(() => page.url === '/wishlist');
const isCartActive = computed(() => page.url === '/cart');
const isCategoryDropdownActive = computed(() => page.url.startsWith('/categories'));
const isBrandDropdownActive = computed(() => page.url.startsWith('/brands'));

const accountHref = computed(() => user.value ? '/dashboard' : '/login');
const accountTitle = computed(() => user.value ? 'My Dashboard' : 'Login / Register');
</script>

<template>
    <header class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                
                <!-- 1. LOGO & MENU -->
                <div class="flex items-center">
                    <div class="flex-shrink-0 mr-6 cursor-pointer">
                        <Link href="/" class="flex flex-col items-start justify-center">
                            <div class="text-xl font-light leading-none text-rose-800 bg-gradient-to-r from-rose-600 to-pink-600 bg-clip-text text-transparent">
                                SkinLab
                            </div>
                            <div class="text-[8px] text-rose-500 font-medium tracking-[0.25em] uppercase mt-0.5">
                                BEAUTY
                            </div>
                        </Link>
                    </div>
                    
                    <nav class="hidden sm:flex sm:space-x-4 items-center ml-4">
                        <!-- Menu Home -->
                        <Link href="/" :class="isHomeActive ? 'text-rose-600 border-b-2 border-rose-600' : 'text-gray-500 hover:text-gray-900'" class="h-16 inline-flex items-center px-2 text-sm font-medium transition-colors gap-2">
                            <Home class="w-4 h-4" /> Home
                        </Link>
                        
                        <!-- Menu Catalog -->
                        <Link href="/catalog" :class="isCatalogActive ? 'text-rose-600 border-b-2 border-rose-600' : 'text-gray-500 hover:text-gray-900'" class="h-16 inline-flex items-center px-2 text-sm font-medium transition-colors gap-2">
                            <ShoppingBag class="w-4 h-4" /> Catalog
                        </Link>

                        <!-- Dropdown Category -->
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button :class="isCategoryDropdownActive ? 'text-rose-600 border-b-2 border-rose-600' : 'text-gray-500 hover:text-gray-900'" class="h-16 inline-flex items-center px-2 text-sm font-medium transition-colors gap-1 outline-none">
                                    Category <ChevronDown class="w-3 h-3" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-56 bg-white shadow-lg ring-1 ring-black ring-opacity-5 rounded-md" align="start">
                                <DropdownMenuGroup>
                                    <DropdownMenuItem v-for="category in categories" :key="category.id" as-child>
                                        <Link :href="`/categories/${category.slug}`" class="block w-full px-4 py-2 text-sm text-gray-700 hover:bg-rose-50 hover:text-rose-600">
                                            {{ category.name }}
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>

                        <!-- Dropdown Brand -->
                        <DropdownMenu>
                            <DropdownMenuTrigger as-child>
                                <button :class="isBrandDropdownActive ? 'text-rose-600 border-b-2 border-rose-600' : 'text-gray-500 hover:text-gray-900'" class="h-16 inline-flex items-center px-2 text-sm font-medium transition-colors gap-1 outline-none">
                                    Brand <ChevronDown class="w-3 h-3" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-56 bg-white shadow-lg ring-1 ring-black ring-opacity-5 rounded-md" align="start">
                                <DropdownMenuGroup>
                                    <DropdownMenuItem v-for="brand in brands" :key="brand.id" as-child>
                                        <Link :href="`/brands/${brand.slug}`" class="block w-full px-4 py-2 text-sm text-gray-700 hover:bg-rose-50 hover:text-rose-600">
                                            {{ brand.name }}
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuGroup>
                            </DropdownMenuContent>
                        </DropdownMenu>
                        
                        <!-- Menu About -->
                        <Link href="/about" :class="isAboutActive ? 'text-rose-600 border-b-2 border-rose-600' : 'text-gray-500 hover:text-gray-900'" class="h-16 inline-flex items-center px-2 text-sm font-medium transition-colors flex items-center gap-2">
                            <Info class="w-4 h-4" /> About
                        </Link>
                    </nav>
                </div>

                <!-- 2. SEARCH BAR (Tengah) -->
                <div class="hidden sm:flex items-center justify-center flex-1 px-8">
                    <Searchbar />
                </div>

                <!-- 3. ICON KANAN -->
                <div class="flex items-center space-x-2">
                    
                    <!-- [BARU] NOTIFIKASI (LONCENG) -->
                    <div class="relative">
                        <button 
                            @click="toggleNotifications"
                            class="relative p-2 rounded-full text-gray-500 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                        >
                            <Bell class="h-5 w-5" />
                            <!-- Badge Merah (Pesan Masuk) -->
                            <span v-if="notificationCount > 0" class="absolute top-1 right-1 bg-red-500 text-white text-[10px] font-bold rounded-full h-4 w-4 flex items-center justify-center ring-2 ring-white animate-pulse">
                                {{ notificationCount }}
                            </span>
                        </button>

                        <!-- Dropdown List -->
                        <div v-if="showNotifDropdown" class="absolute right-0 mt-3 w-80 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden z-50 origin-top-right">
                            <div class="p-3 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                                <span class="text-xs font-bold text-gray-700">Notifikasi</span>
                                <button v-if="notificationCount > 0" @click="clearNotifications" class="text-xs text-rose-600 hover:underline">Hapus Semua</button>
                            </div>
                            
                            <div class="max-h-64 overflow-y-auto">
                                <div v-if="notifications.length === 0" class="p-6 text-center text-gray-400 text-xs">
                                    Tidak ada notifikasi baru.
                                </div>
                                <ul v-else>
                                    <li v-for="(n, i) in notifications" :key="i" class="p-3 border-b border-gray-50 hover:bg-rose-50/50 transition-colors relative group">
                                        <div class="flex justify-between items-start">
                                            <div class="pr-6">
                                                <p class="font-bold text-xs text-gray-800">{{ n.title }}</p>
                                                <p class="text-xs text-gray-600 mt-0.5 leading-snug">{{ n.body }}</p>
                                                <p class="text-[10px] text-gray-400 mt-1">{{ n.time }}</p>
                                            </div>
                                            <button @click="removeNotification(i)" class="absolute top-3 right-3 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <Trash2 class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Backdrop untuk menutup saat klik luar -->
                        <div v-if="showNotifDropdown" class="fixed inset-0 z-40" @click="showNotifDropdown = false"></div>
                    </div>

                    <!-- WISHLIST -->
                    <Link href="/wishlist" :class="isWishlistActive ? 'text-rose-600 bg-rose-50' : 'text-gray-500 hover:text-rose-600 hover:bg-rose-50'" class="relative p-2 rounded-full transition-colors">
                        <Heart class="h-5 w-5" />
                        <span v-if="wishlistCount > 0" class="absolute top-0 right-0 bg-rose-600 text-white text-[10px] font-bold rounded-full h-4 w-4 flex items-center justify-center ring-2 ring-white">
                            {{ wishlistCount }}
                        </span>
                    </Link>

                    <!-- CART -->
                    <Link href="/cart" :class="isCartActive ? 'text-rose-600 bg-rose-50' : 'text-gray-500 hover:text-rose-600 hover:bg-rose-50'" class="relative p-2 rounded-full transition-colors">
                        <ShoppingCart class="h-5 w-5" />
                        <span v-if="cartCount > 0" class="absolute top-0 right-0 bg-rose-600 text-white text-[10px] font-bold rounded-full h-4 w-4 flex items-center justify-center ring-2 ring-white">
                            {{ cartCount }}
                        </span>
                    </Link>

                    <!-- ACCOUNT -->
                    <Link 
                        :href="accountHref"
                        class="relative p-1 rounded-full text-gray-500 hover:text-rose-600 hover:bg-rose-50 transition-colors ml-1"
                        :title="accountTitle"
                    >
                        <span v-if="user" class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-rose-500 to-pink-500 text-white font-medium text-sm shadow-sm ring-2 ring-white">
                            {{ user.name.charAt(0).toUpperCase() }}
                        </span>
                        
                        <div v-else class="p-1">
                            <User class="h-6 w-6" />
                        </div>

                        <!-- Badge Pending Order (Warna Orange/Kuning) -->
                        <span 
                            v-if="user && pendingOrdersCount > 0"
                            class="absolute -top-1 -right-1 bg-orange-500 text-white text-[10px] font-bold rounded-full h-5 w-5 flex items-center justify-center ring-2 ring-white animate-pulse"
                            title="Pesanan belum selesai"
                        >
                            {{ pendingOrdersCount }}
                        </span>
                    </Link>

                </div>
            </div>
        </div>
    </header>
</template>