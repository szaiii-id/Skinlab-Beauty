<script setup>
import { X, Copy, Check, Crown, Sparkles, Gem, Flower2 } from 'lucide-vue-next';
import { ref, computed } from 'vue';

const props = defineProps({
    show: Boolean,
    staff: Object,
});

const emit = defineEmits(['close']);
const copied = ref(false);

const copyId = () => {
    if (!props.staff) return;
    const fullId = `STF-${props.staff.id.toString().padStart(6, '0')}`;
    navigator.clipboard.writeText(fullId);
    copied.value = true;
    setTimeout(() => copied.value = false, 2000);
};

const initials = computed(() => {
    if (!props.staff?.name) return 'SL';
    return props.staff.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
});

// --- THEME LOGIC (LIGHT & ELEGANT VERSION) ---
const cardTheme = computed(() => {
    if (!props.staff) return {};
    switch (props.staff.role) {
        case 'super_admin':
            return {
                // THEME: Champagne Gold (Luxury & Bright)
                // Terang, Bersih, Mahal.
                wrapper: 'bg-[#FDFAF6]', // Cream sangat muda
                gradient: 'from-amber-50 via-orange-50/40 to-yellow-50/60',
                
                // Text (High Contrast Dark Brown/Bronze)
                textMain: 'text-amber-950', 
                textSub: 'text-amber-900/60',
                textBrand: 'text-amber-900',
                
                border: 'border-amber-900/10', // Garis tipis elegan
                
                icon: Crown,
                iconColor: 'text-amber-600',
                
                labelBg: 'bg-amber-100/80 border border-amber-200',
                labelText: 'text-amber-800',
                roleName: 'DIRECTOR',
                
                glow: 'bg-amber-300/20', // Aura emas lembut
                divider: 'bg-amber-900/10'
            };
        case 'warehouse':
            return {
                // THEME: Sage Green (Fresh & Clean)
                wrapper: 'bg-[#F4F9F6]',
                gradient: 'from-emerald-50/60 via-teal-50/30 to-emerald-50/40',
                
                textMain: 'text-emerald-950',
                textSub: 'text-emerald-900/60',
                textBrand: 'text-emerald-800',
                
                border: 'border-emerald-900/10',
                
                icon: Gem,
                iconColor: 'text-emerald-600',
                
                labelBg: 'bg-emerald-100/80 border border-emerald-200',
                labelText: 'text-emerald-800',
                roleName: 'LOGISTICS',
                
                glow: 'bg-emerald-400/10',
                divider: 'bg-emerald-900/10'
            };
        default: // Marketing
            return {
                // THEME: Soft Rose (Feminine & Sweet)
                wrapper: 'bg-[#FFF5F8]',
                gradient: 'from-rose-50 via-pink-50/40 to-rose-50/60',
                
                textMain: 'text-rose-950',
                textSub: 'text-rose-900/60',
                textBrand: 'text-rose-800',
                
                border: 'border-rose-900/10',
                
                icon: Sparkles,
                iconColor: 'text-rose-500',
                
                labelBg: 'bg-rose-100/80 border border-rose-200',
                labelText: 'text-rose-800',
                roleName: 'CREATIVE',
                
                glow: 'bg-rose-400/10',
                divider: 'bg-rose-900/10'
            };
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition 
            enter-active-class="transition ease-out duration-500"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-300"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div v-if="show && staff" class="fixed inset-0 z-[9999] flex items-center justify-center p-6 overflow-y-auto font-sans">
                
                <div class="fixed inset-0 bg-[#3f3f46]/40 backdrop-blur-md transition-opacity" @click="$emit('close')"></div>

                <div class="relative w-full max-w-[340px] flex flex-col items-center">
                    
                    <button @click="$emit('close')" class="absolute -top-14 right-0 text-white/80 hover:text-white transition-colors bg-white/10 hover:bg-white/20 p-3 rounded-full backdrop-blur-md">
                        <X class="w-5 h-5" />
                    </button>

                    <div 
                        class="w-full aspect-[9/15] rounded-[3rem] relative overflow-hidden flex flex-col items-center shadow-2xl transition-transform hover:scale-[1.01] duration-700 border-[1.5px]"
                        :class="[cardTheme.wrapper, cardTheme.border]"
                    >
                        <div class="absolute inset-0 bg-gradient-to-b opacity-100" :class="cardTheme.gradient"></div>
                        
                        <div class="absolute -top-20 -left-20 w-64 h-64 rounded-full blur-[80px] mix-blend-multiply" :class="cardTheme.glow"></div>
                        <div class="absolute -bottom-20 -right-20 w-64 h-64 rounded-full blur-[80px] mix-blend-multiply" :class="cardTheme.glow"></div>

                        <div class="relative z-10 w-full h-full flex flex-col p-8">
                            
                            <div class="flex flex-col items-center gap-1 mt-2">
                                <div class="flex items-center gap-2 opacity-90">
                                    <Flower2 stroke-width="2" class="w-4 h-4" :class="cardTheme.textBrand"/>
                                    <span class="text-[10px] font-bold tracking-[0.25em] uppercase" :class="cardTheme.textBrand">SkinLab Beauty</span>
                                </div>
                            </div>

                            <div class="mt-10 mb-6 relative flex justify-center">
                                <div class="w-36 h-36 rounded-full border border-white/60 p-1.5 shadow-xl bg-white/40 backdrop-blur-md">
                                    <div class="w-full h-full rounded-full bg-white flex items-center justify-center overflow-hidden relative shadow-inner">
                                        <span class="text-4xl font-serif font-medium opacity-50 select-none text-slate-800">
                                            {{ initials }}
                                        </span>
                                        
                                        <div v-if="staff.is_active" class="absolute bottom-3 right-3 w-4 h-4 bg-emerald-400 border-[3px] border-white rounded-full shadow-sm"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center flex-1">
                                <div class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full mb-5 shadow-sm backdrop-blur-md" :class="cardTheme.labelBg">
                                    <component :is="cardTheme.icon" class="w-3 h-3" :class="cardTheme.iconColor" />
                                    <span class="text-[10px] font-bold tracking-[0.15em] uppercase" :class="cardTheme.labelText">
                                        {{ cardTheme.roleName }}
                                    </span>
                                </div>

                                <h2 class="text-3xl font-serif leading-tight mb-2" :class="cardTheme.textMain">
                                    {{ staff.name }}
                                </h2>
                                
                                <p class="text-xs font-medium tracking-wide font-mono uppercase" :class="cardTheme.textSub">
                                    {{ staff.email }}
                                </p>
                            </div>

                            <div class="mt-auto w-full">
                                <div class="w-12 h-[1px] mx-auto mb-6" :class="cardTheme.divider"></div>

                                <div class="flex justify-between items-end">
                                    <div class="text-left">
                                        <p class="text-[8px] uppercase tracking-widest mb-1 opacity-60 font-bold" :class="cardTheme.textMain">ID No.</p>
                                        <p class="font-mono text-sm font-bold tracking-wider" :class="cardTheme.textMain">
                                            {{ staff.id.toString().padStart(6, '0') }}
                                        </p>
                                    </div>

                                    <div class="bg-white/80 p-1.5 rounded-xl shadow-sm border border-white/60">
                                        <img 
                                            :src="`https://api.qrserver.com/v1/create-qr-code/?size=100x100&color=333&data=STF:${staff.id}`" 
                                            class="w-12 h-12 object-contain opacity-80 mix-blend-multiply"
                                            alt="QR"
                                        >
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <button 
                        @click="copyId"
                        class="mt-8 group flex items-center gap-3 px-6 py-3 bg-white rounded-full shadow-xl shadow-slate-200/50 hover:shadow-2xl hover:-translate-y-0.5 transition-all duration-300"
                    >
                        <div class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center text-slate-400 group-hover:text-rose-500 transition-colors">
                            <component :is="copied ? Check : Copy" class="w-4 h-4" />
                        </div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-slate-800 transition-colors">
                            {{ copied ? 'ID Copied' : 'Copy ID Number' }}
                        </span>
                    </button>

                </div>
            </div>
        </Transition>
    </Teleport>
</template>