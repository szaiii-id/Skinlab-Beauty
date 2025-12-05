<script setup>
import { computed } from 'vue';
import { Star, Trophy, Crown } from 'lucide-vue-next';

const props = defineProps({
    points: { type: Number, required: true },
    tierName: { type: String, default: 'Bronze' } // Default ke Bronze
});

const formatNumber = (num) => new Intl.NumberFormat('en-US').format(num);

// --- LOGIC WARNA DINAMIS ---
const themeClasses = computed(() => {
    const tier = props.tierName.toLowerCase();

    if (tier.includes('gold')) {
        return {
            bg: 'from-yellow-400 via-amber-500 to-yellow-600',
            shadow: 'shadow-amber-200',
            icon: 'text-yellow-100',
            badge: 'bg-yellow-900/20 border-yellow-200/30 text-white'
        };
    } 
    else if (tier.includes('silver')) {
        return {
            bg: 'from-slate-300 via-slate-400 to-slate-500',
            shadow: 'shadow-slate-200',
            icon: 'text-slate-100',
            badge: 'bg-slate-800/20 border-slate-200/30 text-white'
        };
    } 
    else {
        // Default (Bronze / Classic)
        return {
            bg: 'from-orange-400 via-rose-400 to-rose-500',
            shadow: 'shadow-rose-200',
            icon: 'text-orange-100',
            badge: 'bg-white/20 border-white/20 text-white'
        };
    }
});
</script>

<template>
    <div 
        class="rounded-3xl p-8 md:p-10 text-white shadow-xl mb-10 relative overflow-hidden group transition-all duration-500 bg-gradient-to-br"
        :class="[themeClasses.bg, themeClasses.shadow]"
    >
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-2">
                <p class="font-bold uppercase tracking-widest text-[10px] opacity-90">Total Balance</p>
                <Crown class="w-6 h-6 opacity-30" />
            </div>
            
            <h1 class="text-5xl md:text-7xl font-black flex items-baseline gap-2 tracking-tighter drop-shadow-sm">
                {{ formatNumber(points) }} <span class="text-2xl font-bold opacity-70">pts</span>
            </h1>
            
            <div class="mt-8 flex flex-wrap items-center gap-4">
                
                <span 
                    class="backdrop-blur-md px-4 py-1.5 rounded-full text-xs font-bold border uppercase tracking-wider flex items-center gap-2 shadow-sm"
                    :class="themeClasses.badge"
                >
                    <Trophy class="w-3.5 h-3.5" :class="themeClasses.icon" />
                    {{ tierName }} Member
                </span>
                
                <p class="text-xs font-medium opacity-80 flex items-center gap-1">
                    <Star class="w-3 h-3 fill-current" />
                    Earn 1 pt / IDR 1,000 spent
                </p>
            </div>
        </div>
        
        <div class="absolute top-[-20%] right-[-10%] w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-[-20%] left-[-10%] w-48 h-48 bg-black opacity-5 rounded-full blur-3xl"></div>
        
        <Star class="absolute -right-6 -bottom-6 w-56 h-56 text-white opacity-10 rotate-12 group-hover:rotate-45 group-hover:scale-110 transition-all duration-1000 ease-in-out" />
        
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iMiIgY3k9IjIiIHI9IjEiIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIi8+PC9zdmc+')] opacity-30"></div>
    </div>
</template>