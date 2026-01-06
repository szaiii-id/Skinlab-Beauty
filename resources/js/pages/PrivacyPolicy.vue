<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import { ChevronDown, ShieldCheck, Lock } from 'lucide-vue-next';

defineOptions({
    layout: AppNavbarLayout
});

const openItem = ref('item-1');

// Data Privacy
const privacyPoints = [
  {
    value: 'item-1',
    title: '1. Information We Collect',
    summary: 'We collect information you provide (like name & email) and log data (like your IP address) when you use our site.',
    details: 'Log data: When you visit our website, our servers may automatically log the standard data provided by your web browser. It may include your computer’s Internet Protocol (IP) address, your browser type and version, the pages you visit, the time and date of your visit, the time spent on each page, and other details. We also collect information you provide when you register, purchase, or contact us.'
  },
  {
    value: 'item-2',
    title: '2. Use of Your Information',
    summary: 'We use your information to process transactions, personalize your experience, and improve our website.',
    details: 'We may use information to: <ul class="list-disc pl-5 mt-2 space-y-1"><li>Operate and maintain our website;</li><li>Improve, personalize, and expand our website;</li><li>Understand and analyze how you use our website;</li><li>Process your transactions and manage your orders.</li></ul>'
  },
  {
    value: 'item-3',
    title: '3. Data Security',
    summary: 'We take reasonable steps to protect your information. However, no method of internet transmission is 100% secure.',
    details: 'We use commercially acceptable means to protect your personal information, but we cannot guarantee its absolute security. We do not sell, trade, or otherwise transfer to outside parties your Personally Identifiable Information unless we provide users with advance notice.'
  },
];
</script>

<template>
    <Head title="Privacy Policy" />

    <div class="bg-rose-50/30 min-h-screen py-10 md:py-20 font-sans">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <section class="text-center mb-10 md:mb-16">
                <div class="inline-flex items-center justify-center p-3 bg-white rounded-2xl shadow-sm mb-5 text-rose-600 border border-rose-100">
                    <ShieldCheck class="w-8 h-8" />
                </div>
                <h1 class="text-3xl md:text-5xl font-extrabold text-gray-900 tracking-tight mb-4">
                    Privacy Policy
                </h1>
                <p class="text-base md:text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
                    Your privacy is critically important to us. We are committed to protecting 
                    the personal information you share with us.
                </p>
                <p class="text-xs text-gray-400 mt-4 uppercase tracking-widest font-semibold">
                    Last Updated: January 2025
                </p>
            </section>

            <section class="bg-white rounded-2xl md:rounded-3xl shadow-xl shadow-rose-100/50 border border-rose-100 overflow-hidden">
                <div class="divide-y divide-gray-100">
                    
                    <div v-for="point in privacyPoints" :key="point.value" class="group">
                        <button 
                            @click="openItem = (openItem === point.value ? null : point.value)"
                            class="w-full flex justify-between items-center text-left px-6 py-5 md:px-8 md:py-6 transition-all duration-300 hover:bg-gray-50 focus:outline-none"
                            :class="openItem === point.value ? 'bg-rose-50/30' : 'bg-white'"
                        >
                            <span 
                                class="text-lg md:text-xl font-bold transition-colors duration-300"
                                :class="openItem === point.value ? 'text-rose-600' : 'text-gray-800 group-hover:text-rose-600'"
                            >
                                {{ point.title }}
                            </span>
                            
                            <span 
                                class="ml-4 p-2 rounded-full transition-all duration-300"
                                :class="openItem === point.value ? 'bg-rose-100 text-rose-600 rotate-180' : 'bg-gray-100 text-gray-400 group-hover:bg-rose-50 group-hover:text-rose-500'"
                            >
                                <ChevronDown class="h-5 w-5" />
                            </span>
                        </button>
                        
                        <Transition
                            enter-active-class="transition-all duration-300 ease-out"
                            enter-from-class="max-h-0 opacity-0"
                            enter-to-class="max-h-[500px] opacity-100"
                            leave-active-class="transition-all duration-200 ease-in"
                            leave-from-class="max-h-[500px] opacity-100"
                            leave-to-class="max-h-0 opacity-0"
                        >
                            <div v-if="openItem === point.value" class="overflow-hidden">
                                <div class="px-6 pb-6 md:px-8 md:pb-8 pt-0">
                                    
                                    <div class="bg-rose-50 rounded-xl p-4 md:p-5 mb-4 border border-rose-100">
                                        <div class="flex gap-3">
                                            <Lock class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" />
                                            <p class="text-sm md:text-base font-medium text-gray-800 italic">
                                                "{{ point.summary }}"
                                            </p>
                                        </div>
                                    </div>

                                    <div 
                                        class="prose prose-sm md:prose-base max-w-none text-gray-600 leading-relaxed pl-2 md:pl-4 border-l-2 border-gray-100" 
                                        v-html="point.details"
                                    ></div>
                                </div>
                            </div>
                        </Transition>
                    </div>

                </div>
            </section>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    If you have any questions about this policy, please 
                    <Link href="/contact" class="text-rose-600 font-semibold hover:underline decoration-2">contact us</Link>.
                </p>
            </div>

        </div>
    </div>
</template>