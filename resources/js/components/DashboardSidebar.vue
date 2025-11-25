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
    ShoppingBag,
    LogOut // Import Logout Icon
} from 'lucide-vue-next';

const page = usePage();

// Get authenticated user from Inertia shared props
const user = computed(() => page.props.auth.user);

const userMenu = [
    { label: 'Dashboard', icon: LayoutDashboard, route: '/dashboard' },
    { label: 'My Orders', icon: Package, route: '/orders' }, // Linked to Order History
    { label: 'Shopping Cart', icon: ShoppingCart, route: '/cart' },
    { label: 'My Wishlist', icon: Heart, route: '/wishlist' },
    { label: 'Skincare Routine', icon: Calendar, route: '/my-routine' },
    { label: 'Skin Analysis', icon: Scan, route: '/skin-analysis' },
    { label: 'Points & Rewards', icon: Gift, route: '/rewards' },
    { label: 'My Profile', icon: User, route: '/profile' }
];

// Get current URL path
const currentPath = computed(() => page.url);

const isActive = (route: string) => {
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

// Logout function
const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <aside class="w-64 bg-white border-r border-rose-200 shadow-sm flex flex-col min-h-screen">
        <div class="p-6 border-b border-rose-100">
            <div class="text-center cursor-pointer" @click="router.get('/')">
                <div class="text-2xl font-light text-rose-800 bg-gradient-to-r from-rose-600 to-pink-600 bg-clip-text text-transparent">
                    SkinLab
                </div>
                <div class="text-xs text-rose-500 font-medium tracking-wider uppercase mt-1">
                    BEAUTY
                </div>
            </div>
            
            <div class="mt-4 p-3 bg-rose-50 rounded-lg border border-rose-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-rose-500 to-pink-500 rounded-full flex items-center justify-center text-white font-medium text-lg">
                        {{ user?.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-rose-800 truncate">
                            {{ user?.name || 'Guest User' }}
                        </p>
                        <p class="text-xs text-rose-600 truncate">
                            {{ user?.email || '' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <nav class="flex-1 flex flex-col space-y-1 p-4 overflow-y-auto">
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
                
                <div 
                    v-if="isActive(item.route)"
                    class="ml-auto w-2 h-2 bg-rose-500 rounded-full"
                ></div>
            </button>
        </nav>

        <div class="p-4 border-t border-rose-100 space-y-3 bg-white">
            <button
                @click="goToCatalog"
                class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-rose-500 to-pink-500 text-white font-semibold rounded-lg hover:from-rose-600 hover:to-pink-600 transition-colors shadow-sm"
            >
                <ShoppingBag class="w-5 h-5" />
                <span>Shop Products</span>
            </button>

            <button
                @click="logout"
                class="w-full flex items-center justify-center gap-2 px-4 py-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors text-sm font-medium"
            >
                <LogOut class="w-4 h-4" />
                <span>Logout</span>
            </button>
        </div>
    </aside>
</template>