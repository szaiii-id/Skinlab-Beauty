<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { ArrowUp } from 'lucide-vue-next';

const isVisible = ref(false);

const checkScroll = () => {
    isVisible.value = window.scrollY > 300;
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

onMounted(() => {
    window.addEventListener('scroll', checkScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', checkScroll);
});
</script>

<template>
    <Transition name="fade">
        <button
            v-if="isVisible"
            @click="scrollToTop"
            aria-label="Scroll to top"
            class="fixed z-40 
                   /* --- POSISI --- */
                   bottom-20 right-4         /* Mobile: Lebih tinggi (biar gak ketutup Bottom Nav) */
                   md:bottom-8 md:right-8    /* Desktop: Posisi standar di pojok bawah */

                   /* --- UKURAN --- */
                   p-2.5                     /* Mobile: Padding agak kecil */
                   md:p-3                    /* Desktop: Padding normal */

                   /* --- STYLE --- */
                   bg-rose-600/90 backdrop-blur-sm text-white rounded-full shadow-lg shadow-rose-200/50
                   border border-white/20
                   
                   /* --- INTERAKSI --- */
                   transition-all duration-300 
                   hover:bg-rose-700 hover:-translate-y-1 hover:shadow-xl
                   focus:outline-none focus:ring-2 focus:ring-rose-400 focus:ring-offset-2"
        >
            <ArrowUp class="w-5 h-5 md:w-6 md:h-6" stroke-width="2.5" />
        </button>
    </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(20px); /* Efek muncul dari bawah */
}
</style>