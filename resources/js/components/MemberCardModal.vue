<script setup lang="ts">
import { computed } from 'vue';
import QrcodeVue from 'qrcode.vue';
import { X, Crown, Copy } from 'lucide-vue-next';

const props = defineProps<{
    show: boolean;
    user: any;
    tier: string;
}>();

const emit = defineEmits(['close']);

// 1. Tentukan Warna Kartu Berdasarkan Tier
const cardStyle = computed(() => {
    switch (props.tier) {
        case 'Gold':
            return 'bg-gradient-to-br from-yellow-400 via-yellow-500 to-yellow-600 text-white border-yellow-300';
        case 'Silver':
            return 'bg-gradient-to-br from-slate-300 via-slate-400 to-slate-500 text-white border-slate-200';
        default: // Bronze
            return 'bg-gradient-to-br from-orange-300 via-orange-400 to-orange-600 text-white border-orange-200';
    }
});

// 2. Data QR Code
const qrValue = computed(() => {
    return JSON.stringify({
        id: props.user.id,
        email: props.user.email,
        tier: props.tier,
        type: 'membership'
    });
});

// 3. Format Tanggal Join
const joinDate = computed(() => {
    return new Date(props.user.created_at).toLocaleDateString('id-ID', {
        month: 'long', year: 'numeric'
    });
});

// 4. ID Member Visual
const memberId = computed(() => {
    return 'MEM-' + String(props.user.id).padStart(6, '0');
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div v-if="show" class="fixed inset-0 z-[999] flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm overflow-y-auto" @click.self="emit('close')">
            
            <div class="relative w-full max-w-sm my-auto">
                
                <!-- KARTU UTAMA -->
                <div 
                    class="relative w-full aspect-[1.58/1] rounded-2xl shadow-2xl overflow-hidden p-5 flex flex-col justify-between border-t border-l border-white/30"
                    :class="cardStyle"
                >
                    <!-- TOMBOL CLOSE -->
                    <button 
                        @click="emit('close')" 
                        class="absolute top-3 right-3 text-white/80 hover:text-white transition z-20 p-1 rounded-full hover:bg-white/10"
                    >
                        <X class="w-5 h-5" />
                    </button>

                    <!-- Decor -->
                    <div class="absolute -top-24 -right-24 w-48 h-48 bg-white opacity-20 blur-3xl rounded-full z-0"></div>

                    <!-- Header -->
                    <div class="flex justify-between items-start relative z-10 mt-1">
                        <div>
                            <p class="text-[10px] font-medium tracking-[0.25em] uppercase opacity-80">SkinLab Beauty</p>
                            <h3 class="text-xl font-bold flex items-center gap-2 mt-0.5">
                                <Crown class="w-5 h-5 fill-current" /> {{ tier }} Member
                            </h3>
                        </div>
                        <!-- Chip Decor -->
                        <div class="w-11 h-8 bg-gradient-to-r from-yellow-100/50 to-yellow-300/50 rounded-md border border-white/30 backdrop-blur-sm mr-6"></div>
                    </div>

                    <!-- QR Code (Ukuran disesuaikan agar tidak mendorong footer) -->
                    <div class="flex justify-center relative z-10 flex-1 items-center py-2">
                        <div class="bg-white p-2 rounded-xl shadow-sm">
                            <QrcodeVue :value="qrValue" :size="95" level="H" render-as="svg" />
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="relative z-10">
                        <div class="flex justify-between items-end">
                            <div class="min-w-0 flex-1 pr-4">
                                <p class="text-[9px] uppercase opacity-70 mb-0.5">Member Name</p>
                                <p class="font-mono font-bold text-base tracking-wide truncate">{{ user.name }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-[9px] uppercase opacity-70 mb-0.5">Since</p>
                                <p class="font-mono font-bold text-base">{{ joinDate }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ID Section -->
                <div class="mt-5 text-center">
                    <p class="text-gray-300 text-xs mb-1.5 font-medium">Member ID</p>
                    <div class="inline-flex items-center gap-2 bg-white/10 px-5 py-2.5 rounded-full text-white font-mono tracking-widest border border-white/20 shadow-sm hover:bg-white/20 transition-colors cursor-pointer group text-sm">
                        {{ memberId }}
                        <Copy class="w-3.5 h-3.5 ml-1 opacity-50 group-hover:opacity-100 transition-opacity" />
                    </div>
                </div>

            </div>
        </div>
    </Transition>
</template>