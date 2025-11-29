<script setup>
import { X, Copy, CheckCircle2, Crown, Sparkles, Gem } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps({
    show: Boolean,
    user: Object,
    tier: String // 'Bronze', 'Silver', 'Gold'
});

const emit = defineEmits(['close']);

const copied = ref(false);

const copyId = () => {
    const memberId = props.user.id.toString().padStart(6, '0');
    const fullId = `MEM-${memberId}`;
    navigator.clipboard.writeText(fullId);
    copied.value = true;
    setTimeout(() => copied.value = false, 2000);
};

// --- CARD DESIGN LOGIC ---
const cardDesign = computed(() => {
    switch (props.tier) {
        case 'Gold':
            return {
                bg: 'bg-gradient-to-br from-yellow-500 via-amber-300 to-yellow-600',
                text: 'text-yellow-950', // Teks gelap agar kontras dengan emas
                border: 'border-yellow-200',
                icon: Crown,
                label: 'VIP GOLD',
                shine: 'bg-white/30',
                pattern: 'radial-gradient(circle at 50% 0%, rgba(255,255,255,0.4) 0%, transparent 60%)'
            };
        case 'Silver':
            return {
                bg: 'bg-gradient-to-br from-slate-300 via-gray-100 to-slate-400',
                text: 'text-slate-800', // Teks gelap
                border: 'border-slate-200',
                icon: Gem,
                label: 'PLATINUM SILVER',
                shine: 'bg-white/40',
                pattern: 'linear-gradient(45deg, transparent 45%, rgba(255,255,255,0.5) 50%, transparent 55%)'
            };
        default: // Bronze (Rose Gold - Theme Standard)
            return {
                bg: 'bg-gradient-to-br from-rose-400 via-pink-500 to-rose-600',
                text: 'text-white', // Teks putih
                border: 'border-rose-300',
                icon: Sparkles,
                label: 'ROSE BRONZE',
                shine: 'bg-white/10',
                pattern: 'radial-gradient(circle at 100% 100%, rgba(255,255,255,0.2) 0%, transparent 50%)'
            };
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition 
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
                
                <div class="absolute inset-0 bg-black/70 backdrop-blur-md transition-opacity" @click="$emit('close')"></div>

                <div class="relative w-full max-w-md transform transition-all scale-100 flex flex-col items-center">
                    
                    <button @click="$emit('close')" class="absolute -top-12 right-0 md:-right-12 text-white/80 hover:text-white transition-colors bg-white/10 hover:bg-white/20 p-2 rounded-full backdrop-blur-sm">
                        <X class="w-6 h-6" />
                    </button>

                    <div 
                        class="w-full aspect-[1.58/1] rounded-3xl shadow-2xl relative overflow-hidden flex flex-col justify-between p-6 select-none transition-transform hover:scale-[1.02] duration-500"
                        :class="[cardDesign.bg, cardDesign.text]"
                    >
                        <div class="absolute inset-0 pointer-events-none" :style="{ background: cardDesign.pattern }"></div>
                        <div class="absolute -top-20 -right-20 w-60 h-60 rounded-full blur-3xl" :class="cardDesign.shine"></div>

                        <div class="relative z-10 flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-extrabold tracking-widest uppercase opacity-90">SkinLab</h3>
                                <p class="text-[10px] font-medium tracking-[0.3em] uppercase opacity-75">Beauty Club</p>
                            </div>
                            <div class="flex flex-col items-end">
                                <component :is="cardDesign.icon" class="w-8 h-8 mb-1 opacity-90" />
                                <span class="text-[10px] font-black uppercase tracking-wider border-b-2 border-current pb-0.5 opacity-80">
                                    {{ cardDesign.label }}
                                </span>
                            </div>
                        </div>

                        <div class="relative z-10 flex items-center justify-between mt-2">
                            <div class="w-12 h-9 rounded-lg bg-gradient-to-tr from-yellow-200 to-yellow-500 border border-yellow-600 shadow-inner opacity-90 relative overflow-hidden">
                                <div class="absolute top-1/2 w-full h-[1px] bg-yellow-700 opacity-50"></div>
                                <div class="absolute left-1/3 h-full w-[1px] bg-yellow-700 opacity-50"></div>
                                <div class="absolute right-1/3 h-full w-[1px] bg-yellow-700 opacity-50"></div>
                            </div>

                            <div class="bg-white p-1.5 rounded-xl shadow-lg">
                                <img 
                                    :src="`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${user.email}`" 
                                    class="w-20 h-20 object-contain"
                                    alt="QR"
                                >
                            </div>
                        </div>

                        <div class="relative z-10 mt-auto pt-4">
                            <div class="flex justify-between items-end">
                                <div>
                                    <p class="text-[9px] uppercase tracking-widest opacity-60 mb-1">Card Holder</p>
                                    <p class="text-xl font-bold tracking-wide truncate max-w-[200px] drop-shadow-sm font-mono">
                                        {{ user.name.toUpperCase() }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[9px] uppercase tracking-widest opacity-60 mb-1">Valid Thru</p>
                                    <p class="text-sm font-bold font-mono">12/30</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8 flex flex-col items-center gap-3 w-full">
                        <p class="text-white/60 text-xs font-medium uppercase tracking-widest">Membership ID</p>
                        
                        <button 
                            @click="copyId"
                            class="group relative w-full max-w-xs bg-white/10 hover:bg-white/15 border border-white/10 rounded-2xl p-1 pr-4 flex items-center justify-between transition-all"
                        >
                            <div class="bg-white/10 rounded-xl px-4 py-2.5 font-mono text-lg text-white font-bold tracking-widest shadow-inner">
                                MEM-{{ user.id.toString().padStart(6, '0') }}
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-white/50 group-hover:text-white transition-colors font-medium">
                                    {{ copied ? 'Copied!' : 'Copy' }}
                                </span>
                                <component 
                                    :is="copied ? CheckCircle2 : Copy" 
                                    class="w-5 h-5 transition-all"
                                    :class="copied ? 'text-green-400 scale-110' : 'text-white/70 group-hover:text-white'"
                                />
                            </div>
                        </button>
                    </div>

                </div>
            </div>
        </Transition>
    </Teleport>
</template>