<script setup>
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { ref } from 'vue';
import { ScanFace, ChevronRight } from 'lucide-vue-next';
import Swal from 'sweetalert2'; // Import SweetAlert2

// Components
import SkinQuiz from '@/components/SkinQuiz.vue';
import SkinResult from '@/components/SkinResult.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    existingProfile: Object,
    recommendedProducts: Array
});

// State
const mode = ref(props.existingProfile ? 'result' : 'welcome');

// Actions
const startQuiz = () => {
    mode.value = 'quiz';
};

const handleCompletion = () => {
    mode.value = 'result';
};

// [UPDATED] Retake with Beautiful Popup
const retakeTest = () => {
    Swal.fire({
        title: '<span class="text-gray-900 font-bold text-xl">Retake Analysis?</span>',
        html: '<p class="text-gray-600 text-sm">Your previous skin profile data will be <b>overwritten</b>.</p>',
        icon: 'question',
        iconColor: '#e11d48', // Rose-600
        showCancelButton: true,
        confirmButtonText: 'Yes, Start Over',
        cancelButtonText: 'Cancel',
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-2xl border border-rose-100 shadow-2xl p-6',
            confirmButton: 'bg-gradient-to-r from-rose-500 to-pink-600 text-white font-bold py-3 px-6 rounded-xl hover:shadow-lg hover:scale-105 transition-all duration-200 mx-2',
            cancelButton: 'bg-white text-gray-500 font-medium py-3 px-6 rounded-xl border border-gray-200 hover:bg-gray-50 hover:text-gray-700 transition-colors duration-200 mx-2'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            mode.value = 'quiz';
        }
    });
};
</script>

<template>
    <Head title="Skin Analysis" />

    <div class="min-h-screen bg-gradient-to-br from-rose-50 to-pink-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-8">
                <h1 class="text-3xl font-light text-gray-900 mb-2">Skin Analysis</h1>
                <p class="text-gray-600">Discover your skin type and get personalized product recommendations.</p>
            </div>

            <div v-if="mode === 'welcome'" class="bg-white rounded-2xl shadow border border-gray-100 p-12 text-center animate-fade-in">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-rose-50 rounded-full mb-6">
                    <ScanFace class="w-12 h-12 text-rose-500" />
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Skin Profile Yet</h3>
                <p class="text-gray-600 mb-8 max-w-lg mx-auto">You haven't analyzed your skin yet. It only takes 2 minutes.</p>
                <button 
                    @click="startQuiz" 
                    class="inline-flex items-center px-6 py-3 bg-rose-600 text-white font-semibold rounded-lg hover:bg-rose-700 transition-colors shadow-lg shadow-rose-200"
                >
                    Start Analysis <ChevronRight class="ml-2 w-4 h-4" />
                </button>
            </div>

            <SkinQuiz 
                v-else-if="mode === 'quiz'" 
                @completed="handleCompletion"
            />

            <SkinResult 
                v-else-if="mode === 'result'" 
                :profile="existingProfile" 
                :products="recommendedProducts"
                @retake="retakeTest"
            />

        </div>
    </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>