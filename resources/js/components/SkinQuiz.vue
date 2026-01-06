<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { CheckCircle2, ArrowLeft, ChevronRight, Check } from 'lucide-vue-next';

const emit = defineEmits(['completed', 'back']);

// Data Quiz (English)
const questions = [
    {
        id: 1,
        text: "How does your skin feel after washing your face?",
        options: [
            { val: 'A', label: 'Tight and dry' },
            { val: 'B', label: 'Clean and comfortable' },
            { val: 'C', label: 'Clean but gets oily quickly' },
            { val: 'D', label: 'Dry on cheeks, oily on T-zone' }
        ]
    },
    {
        id: 2,
        text: "How often does your face look shiny/oily?",
        options: [
            { val: 'A', label: 'Almost never' },
            { val: 'B', label: 'Sometimes' },
            { val: 'C', label: 'Often, all over the face' },
            { val: 'D', label: 'Only on nose/forehead' }
        ]
    },
    {
        id: 3,
        text: "How do your pores look?",
        options: [
            { val: 'A', label: 'Very small / invisible' },
            { val: 'B', label: 'Normal' },
            { val: 'C', label: 'Large and visible' },
            { val: 'D', label: 'Large only on T-zone' }
        ]
    }
];

const concernsList = [
    'Acne', 'Acne Scars', 'Dullness', 
    'Aging / Wrinkles', 'Dry / Flaky', 'Sensitive', 'Blackheads'
];

const currentStep = ref(0);
const form = useForm({
    answers: {},
    concerns: [],
    custom_concern: '' 
});

const selectOption = (qId, val) => {
    form.answers[qId] = val;
    if (currentStep.value < questions.length - 1) {
        setTimeout(() => currentStep.value++, 250);
    } else {
        currentStep.value++; 
    }
};

const submit = () => {
    form.post(route('skin-analysis.store'), {
        onSuccess: () => emit('completed'),
        preserveScroll: true
    });
};
</script>

<template>
    <div>
        
        <div class="hidden md:block bg-white rounded-2xl shadow border border-gray-100 overflow-hidden animate-fade-in">
            
            <div class="w-full bg-gray-50 h-2">
                <div class="bg-rose-500 h-2 transition-all duration-500 ease-out" 
                     :style="{ width: `${((currentStep) / (questions.length + 1)) * 100}%` }"></div>
            </div>

            <div class="p-8 md:p-10">
                <div v-if="currentStep < questions.length">
                    <span class="text-rose-600 font-bold tracking-wider text-xs uppercase mb-3 block">
                        Question {{ currentStep + 1 }} of {{ questions.length }}
                    </span>
                    <h2 class="text-2xl font-light text-gray-900 mb-8">{{ questions[currentStep].text }}</h2>
                    
                    <div class="space-y-4">
                        <button 
                            v-for="opt in questions[currentStep].options" 
                            :key="opt.val" 
                            @click="selectOption(questions[currentStep].id, opt.val)" 
                            class="w-full text-left p-5 rounded-xl border transition-all flex items-center justify-between group" 
                            :class="form.answers[questions[currentStep].id] === opt.val 
                                ? 'border-rose-500 bg-rose-50 text-rose-700 ring-1 ring-rose-500' 
                                : 'border-gray-200 hover:border-rose-300 hover:bg-gray-50 text-gray-600'"
                        >
                            <span class="text-lg">{{ opt.label }}</span>
                            <CheckCircle2 v-if="form.answers[questions[currentStep].id] === opt.val" class="w-4 h-4 text-rose-600" />
                        </button>
                    </div>

                    <div class="mt-8 pt-4 border-t border-gray-100 flex justify-start" v-if="currentStep > 0">
                        <button @click="currentStep--" class="text-gray-500 hover:text-gray-900 flex items-center gap-2 font-medium">
                            <ArrowLeft class="w-4 h-4" /> Back
                        </button>
                    </div>
                </div>

                <div v-else>
                    <h2 class="text-2xl font-light text-gray-900 mb-2">What are your main skin concerns?</h2>
                    <p class="text-gray-500 mb-6">Select the issues you want to address.</p>
                    
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                        <label v-for="concern in concernsList" :key="concern" class="cursor-pointer relative">
                            <input type="checkbox" :value="concern" v-model="form.concerns" class="peer sr-only">
                            <div class="p-4 rounded-xl border border-gray-200 text-center font-medium text-gray-600 transition-all peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700 peer-checked:ring-1 peer-checked:ring-rose-500 hover:bg-gray-50">
                                {{ concern }}
                            </div>
                        </label>
                    </div>

                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Any other concerns? (Optional)</label>
                        <textarea 
                            v-model="form.custom_concern" 
                            rows="2"
                            class="w-full rounded-xl border-gray-200 focus:border-rose-500 focus:ring-rose-500 text-sm shadow-sm p-3"
                            placeholder="Example: I feel bumpiness on my forehead..."
                        ></textarea>
                        <p class="text-xs text-gray-400 mt-1">Our system will match keywords from your input.</p>
                    </div>

                    <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                        <button @click="currentStep--" class="text-gray-500 hover:text-gray-900 font-medium">Back</button>
                        <button 
                            @click="submit" 
                            :disabled="form.processing || (form.concerns.length === 0 && !form.custom_concern)" 
                            class="inline-flex items-center px-8 py-3 bg-rose-600 text-white font-semibold rounded-lg hover:bg-rose-700 disabled:opacity-50 transition-colors"
                        >
                            See Results
                        </button>
                    </div>
                </div>
            </div>
        </div>


        <div class="md:hidden bg-white rounded-[2rem] shadow-sm border border-gray-100 overflow-hidden flex flex-col min-h-[60vh] animate-in slide-in-from-bottom duration-300">
            
            <div class="w-full bg-gray-100 h-1.5">
                <div class="bg-rose-500 h-1.5 transition-all duration-300 ease-linear" 
                     :style="{ width: `${((currentStep) / (questions.length + 1)) * 100}%` }"></div>
            </div>

            <div class="px-5 pt-6 pb-2">
                <div v-if="currentStep < questions.length" class="flex justify-between items-center mb-4">
                    <span class="text-rose-600 font-bold text-[10px] uppercase tracking-widest bg-rose-50 px-2 py-1 rounded-full">
                        Step {{ currentStep + 1 }} / {{ questions.length + 1 }}
                    </span>
                    <button v-if="currentStep > 0" @click="currentStep--" class="text-gray-400 hover:text-gray-600">
                        <ArrowLeft class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <div class="px-5 pb-8 flex-1 flex flex-col">
                
                <div v-if="currentStep < questions.length" class="flex flex-col h-full">
                    <h2 class="text-xl font-bold text-gray-900 leading-tight mb-6">
                        {{ questions[currentStep].text }}
                    </h2>
                    
                    <div class="space-y-3 flex-1">
                        <button 
                            v-for="opt in questions[currentStep].options" 
                            :key="opt.val" 
                            @click="selectOption(questions[currentStep].id, opt.val)" 
                            class="w-full text-left p-4 rounded-2xl border-2 transition-all flex items-center justify-between active:scale-[0.98]" 
                            :class="form.answers[questions[currentStep].id] === opt.val 
                                ? 'border-rose-500 bg-rose-50 text-rose-800 shadow-sm' 
                                : 'border-gray-100 bg-white text-gray-600 hover:bg-gray-50'"
                        >
                            <span class="text-sm font-medium">{{ opt.label }}</span>
                            <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                                :class="form.answers[questions[currentStep].id] === opt.val ? 'border-rose-500 bg-rose-500' : 'border-gray-300'">
                                <Check v-if="form.answers[questions[currentStep].id] === opt.val" class="w-3 h-3 text-white" />
                            </div>
                        </button>
                    </div>
                </div>

                <div v-else class="flex flex-col h-full">
                    <h2 class="text-xl font-bold text-gray-900 mb-1">Skin Concerns</h2>
                    <p class="text-sm text-gray-500 mb-5">Select what applies to you.</p>
                    
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <label v-for="concern in concernsList" :key="concern" class="cursor-pointer relative group">
                            <input type="checkbox" :value="concern" v-model="form.concerns" class="peer sr-only">
                            <div class="p-3 rounded-xl border border-gray-200 bg-white text-center text-xs font-bold text-gray-600 transition-all peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700 active:scale-95 shadow-sm">
                                {{ concern }}
                            </div>
                        </label>
                    </div>

                    <div class="mb-auto">
                        <label class="block text-xs font-bold text-gray-700 mb-2 uppercase">Other Concerns</label>
                        <textarea 
                            v-model="form.custom_concern" 
                            rows="2"
                            class="w-full rounded-xl border-gray-200 bg-gray-50 focus:bg-white focus:border-rose-500 focus:ring-rose-500 text-sm p-3 transition-colors"
                            placeholder="Type here..."
                        ></textarea>
                    </div>

                    <div class="pt-6 mt-4 border-t border-gray-100 flex gap-3">
                        <button @click="currentStep--" class="p-3 rounded-xl border border-gray-200 text-gray-500 hover:bg-gray-50">
                            <ArrowLeft class="w-5 h-5" />
                        </button>
                        <button 
                            @click="submit" 
                            :disabled="form.processing || (form.concerns.length === 0 && !form.custom_concern)" 
                            class="flex-1 py-3 bg-gradient-to-r from-rose-500 to-pink-600 text-white font-bold rounded-xl shadow-lg shadow-rose-200 active:scale-95 transition-all disabled:opacity-50 disabled:shadow-none flex items-center justify-center gap-2"
                        >
                            Analyze Now <ChevronRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>

            </div>
        </div>

    </div>
</template>