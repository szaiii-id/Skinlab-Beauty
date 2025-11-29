<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { ref } from 'vue';
import { 
    ScanFace, CheckCircle2, RefreshCw, ChevronRight, 
    ArrowLeft, Sparkles, ShoppingBag 
} from 'lucide-vue-next';
import ProductCard from '@/components/ProductCard.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps<{
    existingProfile?: { skin_type: string; skin_concerns: string[]; },
    recommendedProducts?: any[]
}>();

// ==========================================
// STATE MANAGEMENT
// ==========================================
const mode = ref<'welcome' | 'quiz' | 'result'>(props.existingProfile ? 'result' : 'welcome');
const currentStep = ref(0);

// ==========================================
// DATA QUIZ
// ==========================================
const questions = [
    {
        id: 1,
        text: "Bagaimana rasanya kulitmu setelah mencuci muka?",
        options: [
            { val: 'A', label: 'Terasa kencang dan ketarik (Tight)' },
            { val: 'B', label: 'Terasa bersih dan nyaman' },
            { val: 'C', label: 'Terasa bersih tapi cepat berminyak' },
            { val: 'D', label: 'Kering di pipi, berminyak di T-zone' }
        ]
    },
    {
        id: 2,
        text: "Seberapa sering wajahmu terlihat mengkilap (oily)?",
        options: [
            { val: 'A', label: 'Hampir tidak pernah' },
            { val: 'B', label: 'Kadang-kadang' },
            { val: 'C', label: 'Sering, di seluruh wajah' },
            { val: 'D', label: 'Hanya di area hidung/dahi' }
        ]
    },
    {
        id: 3,
        text: "Bagaimana tampilan pori-pori wajahmu?",
        options: [
            { val: 'A', label: 'Sangat kecil/halus' },
            { val: 'B', label: 'Normal' },
            { val: 'C', label: 'Besar dan terlihat jelas' },
            { val: 'D', label: 'Besar di area T-zone saja' }
        ]
    }
];

const concernsList = [
    'Jerawat (Acne)', 'Bekas Jerawat', 'Kusam (Dullness)', 
    'Kerutan (Aging)', 'Kering/Mengelupas', 'Sensitif', 'Komedo'
];

// ==========================================
// FORM HANDLING
// ==========================================
const form = useForm({
    answers: {} as Record<number, string>,
    concerns: [] as string[],
    custom_concern: '' 
});

const selectOption = (qId: number, val: string) => {
    form.answers[qId] = val;
    if (currentStep.value < questions.length - 1) {
        setTimeout(() => currentStep.value++, 250);
    } else {
        currentStep.value++; 
    }
};

const submitAnalysis = () => {
    form.post(route('skin-analysis.store'), {
        onSuccess: () => {
            mode.value = 'result';
        },
        preserveScroll: true
    });
};

const retakeTest = () => {
    if(confirm('Mulai ulang analisis? Data lama akan tertimpa.')) {
        mode.value = 'quiz';
        currentStep.value = 0;
        form.reset();
    }
};
</script>

<template>
    <Head title="Skin Analysis" />

    <div class="min-h-screen bg-gradient-to-br from-rose-50 to-pink-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <div class="mb-8">
                <h1 class="text-3xl font-light text-gray-900 mb-2">Analisis Kulit</h1>
                <p class="text-gray-600">Kenali jenis kulitmu untuk rekomendasi produk yang tepat</p>
            </div>

            <!-- VIEW 1: WELCOME -->
            <div v-if="mode === 'welcome'" class="bg-white rounded-2xl shadow border border-gray-100 p-12 text-center animate-fade-in">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-rose-50 rounded-full mb-6">
                    <ScanFace class="w-12 h-12 text-rose-500" />
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Belum ada Profil Kulit</h3>
                <p class="text-gray-600 mb-8 max-w-lg mx-auto">Anda belum melakukan analisis kulit. Luangkan waktu 2 menit.</p>
                <button @click="mode = 'quiz'" class="inline-flex items-center px-6 py-3 bg-rose-600 text-white font-semibold rounded-lg hover:bg-rose-700 transition-colors shadow-lg shadow-rose-200">
                    Mulai Analisis <ChevronRight class="ml-2 w-4 h-4" />
                </button>
            </div>

            <!-- VIEW 2: QUIZ WIZARD -->
            <div v-else-if="mode === 'quiz'" class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden animate-fade-in">
                
                <div class="w-full bg-gray-50 h-2">
                    <div class="bg-rose-500 h-2 transition-all duration-500 ease-out" :style="{ width: `${((currentStep) / (questions.length + 1)) * 100}%` }"></div>
                </div>

                <div class="p-8 md:p-10">
                    <!-- Pertanyaan -->
                    <div v-if="currentStep < questions.length">
                        <span class="text-rose-600 font-bold tracking-wider text-xs uppercase mb-3 block">Pertanyaan {{ currentStep + 1 }} dari {{ questions.length }}</span>
                        <h2 class="text-2xl font-light text-gray-900 mb-8">{{ questions[currentStep].text }}</h2>
                        <div class="space-y-4">
                            <button v-for="opt in questions[currentStep].options" :key="opt.val" @click="selectOption(questions[currentStep].id, opt.val)" class="w-full text-left p-5 rounded-xl border transition-all flex items-center justify-between group" :class="form.answers[questions[currentStep].id] === opt.val ? 'border-rose-500 bg-rose-50 text-rose-700 ring-1 ring-rose-500' : 'border-gray-200 hover:border-rose-300 hover:bg-gray-50 text-gray-600'">
                                <span class="text-lg">{{ opt.label }}</span>
                                <CheckCircle2 v-if="form.answers[questions[currentStep].id] === opt.val" class="w-4 h-4 text-rose-600" />
                            </button>
                        </div>
                        <div class="mt-8 pt-4 border-t border-gray-100 flex justify-start" v-if="currentStep > 0">
                            <button @click="currentStep--" class="text-gray-500 hover:text-gray-900 flex items-center gap-2 font-medium"><ArrowLeft class="w-4 h-4" /> Kembali</button>
                        </div>
                    </div>

                    <!-- Step Terakhir: Concerns + Input Manual -->
                    <div v-else>
                        <h2 class="text-2xl font-light text-gray-900 mb-2">Apa masalah utama kulitmu?</h2>
                        <p class="text-gray-500 mb-6">Pilih masalah yang ingin kamu atasi.</p>
                        
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                            <label v-for="concern in concernsList" :key="concern" class="cursor-pointer relative">
                                <input type="checkbox" :value="concern" v-model="form.concerns" class="peer sr-only">
                                <div class="p-4 rounded-xl border border-gray-200 text-center font-medium text-gray-600 transition-all peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700 peer-checked:ring-1 peer-checked:ring-rose-500 hover:bg-gray-50">{{ concern }}</div>
                            </label>
                        </div>

                        <div class="mb-8">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Ada keluhan lain? (Opsional)</label>
                            <textarea 
                                v-model="form.custom_concern" 
                                rows="2"
                                class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-rose-500 text-sm shadow-sm p-3"
                                placeholder="Contoh: Saya merasa bruntusan di dahi dan ada mata panda..."
                            ></textarea>
                            <p class="text-xs text-gray-400 mt-1">Sistem akan mencari produk berdasarkan kata kunci keluhanmu.</p>
                        </div>

                        <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                            <button @click="currentStep--" class="text-gray-500 hover:text-gray-900 font-medium">Kembali</button>
                            <button @click="submitAnalysis" :disabled="form.processing || (form.concerns.length === 0 && !form.custom_concern)" class="inline-flex items-center px-8 py-3 bg-rose-600 text-white font-semibold rounded-lg hover:bg-rose-700 disabled:opacity-50 transition-colors">Lihat Hasil</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW 3: RESULT (UPDATED: Tombol Analisis Ulang Pindah Ke Atas) -->
            <div v-else-if="mode === 'result'" class="space-y-8 animate-fade-in">
                <!-- Banner Hasil -->
                <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden relative">
                    <div class="bg-gradient-to-r from-rose-500 to-pink-600 p-8 md:p-10 text-white">
                        
                        <!-- Flex Container: Judul di Kiri, Tombol di Kanan -->
                        <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                            
                            <!-- KIRI: Info Hasil -->
                            <div>
                                <div class="flex items-center gap-2 text-rose-100 mb-2">
                                    <Sparkles class="w-5 h-5" />
                                    <span class="uppercase tracking-wider text-sm font-bold">Hasil Analisis</span>
                                </div>
                                <h2 class="text-4xl md:text-5xl font-bold mb-4">{{ existingProfile?.skin_type }}</h2>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="c in existingProfile?.skin_concerns" :key="c" class="bg-white/20 backdrop-blur-sm px-4 py-1.5 rounded-full text-sm font-medium border border-white/30">{{ c }}</span>
                                </div>
                            </div>

                            <!-- KANAN: Tombol Analisis Ulang (Posisi Baru) -->
                            <button 
                                @click="retakeTest" 
                                class="flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 backdrop-blur-sm border border-white/40 rounded-lg text-sm font-medium transition-all text-white shadow-sm"
                            >
                                <RefreshCw class="w-4 h-4" /> Analisis Ulang
                            </button>

                        </div>

                        <!-- Background Decor -->
                        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
                        <div class="absolute bottom-0 left-0 w-32 h-32 bg-rose-900 opacity-20 rounded-full blur-2xl"></div>
                    </div>
                </div>

                <!-- Grid Produk -->
                <div class="space-y-4">
                    <h3 class="text-2xl font-light text-gray-900 flex items-center gap-2">
                        <ShoppingBag class="text-rose-600 w-6 h-6" /> Rekomendasi Produk Personal
                    </h3>
                    
                    <div v-if="recommendedProducts && recommendedProducts.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <ProductCard v-for="product in recommendedProducts" :key="product.id" :product="product">
                            <template #badge>
                                <div class="absolute top-3 right-3 bg-rose-500/90 backdrop-blur text-white text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider shadow-sm z-10">Best Match</div>
                            </template>
                        </ProductCard>
                    </div>

                    <div v-else class="bg-white rounded-2xl shadow border border-gray-100 p-12 text-center">
                        <div class="text-gray-300 mb-4 mx-auto w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center"><ScanFace class="w-10 h-10" /></div>
                        <p class="text-gray-500 mb-6">Belum ada produk spesifik. Coba ubah kata kunci pencarianmu.</p>
                        <!-- Tombol di sini juga masih ada sebagai alternatif jika user scroll ke bawah -->
                        <button @click="retakeTest" class="text-rose-600 font-medium hover:text-rose-700 underline">Analisis Ulang</button>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-fade-in { animation: fadeIn 0.4s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>