<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

// Script Anda tetap sama
const flash = computed(() => usePage().props.flash?.success); 
const isVisible = ref(false);

watch(flash, (newValue) => {
  if (newValue) {
    isVisible.value = true; 
    setTimeout(() => {
      isVisible.value = false;
    }, 2500); // Hilang setelah 2.5 detik
  }
});
</script>

<template>
  <Transition
    enter-active-class="ease-out duration-300"
    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
    leave-active-class="ease-in duration-200"
    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
  >
    <div 
      v-if="isVisible" 
      class="fixed inset-0 flex items-center justify-center z-[9999] pointer-events-none"
    >
      <div class="bg-rose-50 border border-rose-200 rounded-lg shadow-xl p-8 text-center max-w-sm w-full pointer-events-auto">
        
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100">
          <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
          </svg>
        </div>

        <h3 class="mt-5 text-2xl font-semibold text-rose-900"> Success!
        </h3>
        <p class="mt-2 text-rose-700"> {{ flash }}
        </p>
      </div>
    </div>
  </Transition>
</template>