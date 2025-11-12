<script setup>
import { defineProps, defineEmits } from 'vue';
import { AlertTriangle } from 'lucide-vue-next';

defineProps({
    show: Boolean
});

const emit = defineEmits(['close', 'confirm']);
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
          v-if="show" 
          class="fixed inset-0 flex items-center justify-center z-[9999] pointer-events-none"
        >
            <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4 pointer-events-auto">
                <div class="flex items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <AlertTriangle class="h-6 w-6 text-red-600" />
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Remove Item?
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Are you sure you want to remove this item from your cart? This action cannot be undone.
                        </p>
                    </div>
                </div>
                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <button
                        @click="emit('confirm')"
                        type="button"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Yes, Remove
                    </button>
                    <button
                        @click="emit('close')"
                        type="button"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>