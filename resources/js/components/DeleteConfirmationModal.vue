<!-- [file name]: components/DeleteConfirmationModal.vue -->
<template>
    <div
        v-if="show"
        class="fixed inset-0 flex items-center justify-center p-4 z-50"
        @click="close"
    >
        <!-- Overlay dengan efek blur -->
        <div class="absolute inset-0 bg-white/80 backdrop-blur-sm"></div>
        
        <!-- Modal Content -->
        <div 
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden border border-gray-100"
            @click.stop
        >
            <!-- Header -->
            <div class="bg-gradient-to-r from-red-50 to-orange-50 px-6 py-4 border-b border-red-100">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900">
                        Hapus Alamat
                    </h3>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <!-- Warning Icon -->
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                </div>

                <!-- Message -->
                <div class="text-center mb-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-2">
                        Konfirmasi Penghapusan
                    </h4>
                    <p class="text-gray-600">
                        Apakah Anda yakin ingin menghapus alamat 
                        <span class="font-semibold text-gray-900">"{{ address?.receiver_name }}"</span>?
                    </p>
                    <p class="text-sm text-red-600 mt-2">
                        Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>

                <!-- Form Actions -->
                <div class="flex space-x-3">
                    <button
                        type="button"
                        @click="close"
                        class="flex-1 px-6 py-3 bg-white border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-gray-400 active:scale-95 transition-all duration-200 font-medium"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="confirmDelete"
                        :disabled="deleting"
                        class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-orange-600 text-white rounded-xl hover:from-red-700 hover:to-orange-700 disabled:from-gray-400 disabled:to-gray-400 disabled:cursor-not-allowed active:scale-95 transition-all duration-200 font-medium shadow-lg shadow-red-200"
                    >
                        <div class="flex items-center justify-center space-x-2">
                            <svg v-if="deleting" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ deleting ? 'Menghapus...' : 'Ya, Hapus' }}</span>
                        </div>
                    </button>
                </div>

                <!-- Error Message -->
                <div v-if="error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <div class="flex items-start space-x-2">
                        <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-red-800 text-sm font-medium">Error</p>
                            <p class="text-red-700 text-sm mt-1">{{ error }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const props = defineProps({
    show: Boolean,
    address: Object
})

const emit = defineEmits(['close', 'confirmed'])

const deleting = ref(false)
const error = ref('')

const close = () => {
    error.value = ''
    emit('close')
}

const confirmDelete = async () => {
    deleting.value = true
    error.value = ''
    
    try {
        emit('confirmed', props.address)
    } catch (err) {
        error.value = 'Gagal menghapus alamat. Silakan coba lagi.'
        console.error('Delete error:', err)
    } finally {
        deleting.value = false
    }
}
</script>