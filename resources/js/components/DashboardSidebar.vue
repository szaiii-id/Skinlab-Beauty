<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { 
    LayoutDashboard, 
    Package, 
    ShoppingCart, 
    Heart,
    Calendar,
    Scan,
    Gift,
    User,
    Crown 
} from 'lucide-vue-next';
import MemberCardModal from '@/components/MemberCardModal.vue'; // Import Komponen Kartu

const page = usePage();

// Get authenticated user from Inertia shared props
const user = computed(() => page.props.auth.user);

// --- LOGIC MEMBERSHIP ---
const userTier = computed(() => user.value?.membership?.tier || 'Bronze');

// Helper Warna Badge berdasarkan Tier
const membershipColor = computed(() => {
    switch (userTier.value) {
        case 'Gold': return 'bg-yellow-100 text-yellow-700 border border-yellow-300';
        case 'Silver': return 'bg-slate-100 text-slate-600 border border-slate-300';
        default: return 'bg-orange-50 text-orange-700 border border-orange-200'; 
    }
});

// State untuk Modal Kartu Member
const showCard = ref(false);

const userMenu = [
    { label: 'Dashboard', icon: LayoutDashboard, route: '/dashboard' },
    { label: 'My Orders', icon: Package, route: '/orders' },
    { label: 'Shopping Cart', icon: ShoppingCart, route: '/cart' },
    { label: 'My Wishlist', icon: Heart, route: '/wishlist' },
    { label: 'Skincare Routine', icon: Calendar, route: '/my-routine' },
    { label: 'Skin Analysis', icon: Scan, route: '/skin-analysis' },
    { label: 'Points & Rewards', icon: Gift, route: '/rewards' },
    { label: 'My Profile', icon: User, route: '/settings/profile' } 
];

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
</script>

<template>
    <aside class="w-64 bg-white border-r border-rose-200 shadow-sm flex flex-col h-screen sticky top-0">
        <div class="p-6 border-b border-rose-100">
            <div class="text-center cursor-pointer" @click="router.get('/')">
                <div class="text-2xl font-light text-rose-800 bg-gradient-to-r from-rose-600 to-pink-600 bg-clip-text text-transparent">
                    SkinLab
                </div>
                <div class="text-xs text-rose-500 font-medium tracking-wider uppercase mt-1">
                    BEAUTY
                </div>
            </div>
            
            <div 
                class="mt-4 p-3 bg-rose-50 rounded-lg border border-rose-200 cursor-pointer hover:bg-rose-100 hover:shadow-sm transition-all group relative"
                @click="showCard = true"
            >
                <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-gray-800 text-white text-[10px] px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">
                    Klik untuk lihat kartu
                    <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-800"></div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-rose-500 to-pink-500 rounded-full flex items-center justify-center text-white font-medium text-lg shrink-0">
                        {{ user?.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-rose-800 truncate">
                            {{ user?.name || 'Guest User' }}
                        </p>
                        <p class="text-xs text-rose-600 truncate mb-1">
                            {{ user?.email || '' }}
                        </p>
                        
                        <span 
                            class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wide w-fit"
                            :class="membershipColor"
                        >
                            <Crown class="w-3 h-3" /> {{ userTier }}
                        </span>
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
        
        <MemberCardModal 
            v-if="user"
            :show="showCard" 
            :user="user" 
            :tier="userTier"
            @close="showCard = false" 
        />

    </aside>
</template>