<!-- resources/js/components/dashboardsidebar.vue -->
<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
    LayoutDashboard, 
    Package, 
    ShoppingCart, 
    Heart,
    Calendar,
    Scan,
    Gift,
    User,
    ShoppingBag
} from 'lucide-vue-next';

const page = usePage();

const userMenu = [
    { label: 'Dashboard', icon: LayoutDashboard, route: '/dashboard' },
    { label: 'My Orders', icon: Package, route: '/orders' },
    { label: 'Shopping Cart', icon: ShoppingCart, route: '/cart' },
    { label: 'My Wishlist', icon: Heart, route: '/wishlist' },
    { label: 'Skincare Routine', icon: Calendar, route: '/my-routine' },
    { label: 'Skin Analysis', icon: Scan, route: '/skin-analysis' },
    { label: 'Points & Rewards', icon: Gift, route: '/rewards' },
    { label: 'My Profile', icon: User, route: '/profile' }
];

// Use current URL from Inertia page
const currentPath = computed(() => {
    return page.url;
});

const isActive = (route) => {
    if (route === '/dashboard') {
        return currentPath.value === '/dashboard';
    }
    return currentPath.value.startsWith(route);
};

const navigateTo = (route: string) => {
    router.get(route);
};

const goToCatalog = () => {
    router.get('/catalog');
};
</script>

<template>
    <aside class="w-64 bg-white border-r border-rose-200 shadow-sm">
        <!-- Logo & User Info -->
        <div class="p-6 border-b border-rose-100">
            <div class="text-center">
                <div class="text-2xl font-light text-rose-800 bg-gradient-to-r from-rose-600 to-pink-600 bg-clip-text text-transparent">
                    SkinLab
                </div>
                <div class="text-xs text-rose-500 font-medium tracking-wider uppercase mt-1">
                    BEAUTY
                </div>
            </div>
            <div class="mt-4 p-3 bg-rose-50 rounded-lg border border-rose-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-rose-500 to-pink-500 rounded-full flex items-center justify-center text-white font-medium">
                        U
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-rose-800 truncate">Beauty User</p>
                        <p class="text-xs text-rose-600">Gold Member</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex flex-col space-y-1 p-4">
            <button
                v-for="item in userMenu"
                :key="item.label"
                @click="navigateTo(item.route)"
                :class="[
                    'flex items-center gap-3 px-3 py-3 rounded-lg text-sm font-medium w-full text-left transition-colors relative',
                    isActive(item.route)
                        ? 'bg-rose-50 text-rose-600 border border-rose-200'
                        : 'text-gray-700 hover:bg-rose-50 hover:text-rose-600'
                ]"
            >
                <component 
                    :is="item.icon" 
                    class="w-5 h-5"
                    :class="isActive(item.route) ? 'text-rose-600' : 'text-gray-500'"
                />
                <span>{{ item.label }}</span>
                
                <!-- Active indicator -->
                <div 
                    v-if="isActive(item.route)"
                    class="ml-auto w-2 h-2 bg-rose-500 rounded-full"
                ></div>
            </button>
        </nav>

        <!-- Shopping Button -->
        <div class="px-4 mt-4">
            <button
                @click="goToCatalog"
                class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-rose-500 to-pink-500 text-white font-semibold rounded-lg hover:from-rose-600 hover:to-pink-600 transition-colors shadow"
            >
                <ShoppingBag class="w-5 h-5" />
                <span>Shop Products</span>
            </button>
        </div>

        <!-- Points Summary -->
        <div class="mx-4 mt-4 p-4 bg-gradient-to-r from-rose-400 to-pink-500 rounded-lg text-white">
            <div class="text-xs font-medium">MY POINTS</div>
            <div class="text-2xl font-bold mt-1">1,250</div>
            <div class="text-xs opacity-90">Redeem for exciting rewards!</div>
        </div>
    </aside>
</template>