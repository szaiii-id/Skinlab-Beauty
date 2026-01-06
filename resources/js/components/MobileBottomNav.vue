<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { 
    Home, 
    ShoppingCart, 
    Calendar, 
    Menu as MenuIcon,
    X,
    Package,
    Heart,
    Scan,
    Gift,
    LogOut,
    UserCircle
} from 'lucide-vue-next';

const page = usePage();
const currentPath = computed(() => page.url);
const badges = computed(() => page.props.sidebar_badges || {});

const isMenuOpen = ref(false);

const isActive = (route: string) => {
    if (route === '/') return currentPath.value === '/';
    return currentPath.value.startsWith(route);
};

// --- MAIN MENU (High Priority) ---
const mainMenus = [
    { label: 'Home', icon: Home, route: '/dashboard' },
    { label: 'Routine', icon: Calendar, route: '/my-routine', badge: !badges.value.has_routine ? '!' : null },
    { label: 'Cart', icon: ShoppingCart, route: '/cart', badge: badges.value.cart_count },
    { label: 'Orders', icon: Package, route: '/orders', badge: badges.value.order_status ? '!' : null },
];

// --- SECONDARY MENU (Drawer) ---
const secondaryMenus = [
    { label: 'Profile', icon: UserCircle, route: '/settings/profile', color: 'text-gray-600 bg-gray-50 border-gray-100' },
    { label: 'Wishlist', icon: Heart, route: '/wishlist', color: 'text-pink-600 bg-pink-50 border-pink-100', badge: badges.value.wishlist_count },
    { label: 'Analysis', icon: Scan, route: '/skin-analysis', color: 'text-violet-600 bg-violet-50 border-violet-100', badge: !badges.value.has_skin_analysis ? '!' : null },
    { label: 'Rewards', icon: Gift, route: '/rewards', color: 'text-amber-600 bg-amber-50 border-amber-100', badge: badges.value.has_rewards ? '•' : null },
];
</script>

<template>
    <div>
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-full"
            enter-to-class="translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0"
            leave-to-class="translate-y-full"
        >
            <div v-if="isMenuOpen" class="fixed inset-0 z-[60] lg:hidden flex flex-col justify-end">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="isMenuOpen = false"></div>
                
                <div class="relative bg-white rounded-t-[2.5rem] p-8 pb-32 shadow-2xl z-50 border-t border-rose-50">
                    <div class="flex justify-center mb-6">
                        <div class="w-12 h-1.5 bg-gray-200 rounded-full"></div>
                    </div>
                    
                    <div class="flex justify-between items-center mb-8">
                        <h3 class="text-gray-900 font-black text-xl">Other Services</h3>
                        <button @click="isMenuOpen = false" class="p-2 bg-gray-50 rounded-full text-gray-400">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="grid grid-cols-4 gap-y-8 gap-x-4">
                        <Link 
                            v-for="item in secondaryMenus" 
                            :key="item.label"
                            :href="item.route"
                            @click="isMenuOpen = false"
                            class="flex flex-col items-center gap-3 group"
                        >
                            <div :class="`relative w-16 h-16 rounded-[1.5rem] flex items-center justify-center border shadow-sm transition-all group-active:scale-90 ${item.color}`">
                                <component :is="item.icon" class="w-7 h-7" />
                                <div v-if="item.badge" class="absolute -top-1 -right-1 bg-rose-600 text-white text-[10px] min-w-[20px] h-[20px] flex items-center justify-center rounded-full border-2 border-white font-black shadow-sm">
                                    {{ item.badge }}
                                </div>
                            </div>
                            <span class="text-[11px] font-bold text-gray-500 uppercase tracking-tight">{{ item.label }}</span>
                        </Link>
                    </div>
                    
                    <div class="mt-10 pt-6 border-t border-gray-100">
                        <Link href="/logout" method="post" as="button" class="w-full flex items-center justify-center gap-3 py-4 text-rose-600 font-black bg-rose-50 rounded-2xl active:scale-[0.98] transition-transform">
                            <LogOut class="w-5 h-5" /> SIGN OUT
                        </Link>
                    </div>
                </div>
            </div>
        </Transition>

        <nav class="fixed bottom-0 left-0 w-full bg-white/90 backdrop-blur-lg border-t border-gray-100 shadow-[0_-5px_25px_-10px_rgba(0,0,0,0.1)] z-50 lg:hidden px-4 pb-safe">
            <div class="flex justify-between items-center h-[75px] max-w-md mx-auto">
                
                <Link 
                    v-for="item in mainMenus" 
                    :key="item.label"
                    :href="item.route"
                    @click="isMenuOpen = false"
                    class="flex-1 flex flex-col items-center justify-center h-full relative"
                >
                    <div 
                        class="p-2 rounded-2xl transition-all duration-300 relative"
                        :class="isActive(item.route) ? 'bg-rose-600 text-white shadow-lg shadow-rose-200 -translate-y-1' : 'text-gray-400'"
                    >
                        <component 
                            :is="item.icon" 
                            class="w-6 h-6" 
                            :stroke-width="isActive(item.route) ? 2.5 : 2" 
                        />

                        <div v-if="item.badge && item.badge > 0" class="absolute -top-1 -right-1 bg-rose-500 text-white text-[9px] min-w-[18px] h-[18px] flex items-center justify-center rounded-full border-2 border-white font-black shadow-sm">
                            {{ item.badge }}
                        </div>
                        <div v-else-if="item.badge === '!'" class="absolute top-0 right-0 w-3 h-3 bg-rose-500 rounded-full border-2 border-white"></div>
                    </div>
                    
                    <span 
                        class="text-[10px] font-black mt-1.5 transition-colors uppercase tracking-tighter"
                        :class="isActive(item.route) ? 'text-rose-600' : 'text-gray-400'"
                    >
                        {{ item.label }}
                    </span>
                </Link>

                <button 
                    @click="isMenuOpen = !isMenuOpen"
                    class="flex-1 flex flex-col items-center justify-center h-full relative"
                >
                    <div 
                        class="p-2 rounded-2xl transition-all duration-300"
                        :class="isMenuOpen ? 'bg-gray-900 text-white shadow-xl -translate-y-1' : 'text-gray-400'"
                    >
                        <MenuIcon class="w-6 h-6" />
                    </div>
                    <span 
                        class="text-[10px] font-black mt-1.5 transition-colors uppercase tracking-tighter"
                        :class="isMenuOpen ? 'text-gray-900' : 'text-gray-400'"
                    >
                        Menu
                    </span>
                </button>

            </div>
        </nav>
    </div>
</template>

<style scoped>
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom, 12px);
}
</style>