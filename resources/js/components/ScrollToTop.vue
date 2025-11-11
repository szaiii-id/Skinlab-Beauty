<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { ArrowUp } from 'lucide-vue-next'; // Ikon panah

const isVisible = ref(false);

const checkScroll = () => {
    // Tombol akan muncul jika posisi scroll lebih dari 300px
    isVisible.value = window.scrollY > 300;
};

const scrollToTop = () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth' // Membuat transisi scroll lebih halus
    });
};

onMounted(() => {
    // Daftarkan event listener saat komponen dimuat
    window.addEventListener('scroll', checkScroll);
});

onUnmounted(() => {
    // Hapus event listener saat komponen dihancurkan
    window.removeEventListener('scroll', checkScroll);
});
</script>

<template>
    <Transition name="fade">
        <button
            v-if="isVisible"
            @click="scrollToTop"
            aria-label="Scroll to top"
            class="fixed bottom-6 right-6 p-3 bg-rose-600 text-white rounded-full shadow-xl 
                   transition-all duration-300 hover:bg-rose-700 focus:outline-none focus:ring-4 focus:ring-rose-300"
        >
            <ArrowUp class="h-6 w-6" />
        </button>
    </Transition>
</template>

<style scoped>
/* Styling Transisi untuk Tombol Agar Muncul/Hilang dengan Elegan */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.5s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>