<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import { ChevronDown, FileText, Info } from 'lucide-vue-next';

defineOptions({
    layout: AppNavbarLayout
});

const openItem = ref('item-1');

// Data Terms
const terms = [
  {
    value: 'item-1',
    title: '1. Your Account',
    summary: 'You are responsible for maintaining the security of your account and password. Please provide accurate information.',
    details: 'When you create an account with us, you must provide us with information that is accurate, complete, and current at all times. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account on our Service. You are responsible for safeguarding the password that you use to access the Service and for any activities or actions under your password.'
  },
  {
    value: 'item-2',
    title: '2. Products or Services',
    summary: 'We do our best to display product colors and images accurately. We reserve the right to refuse or cancel any order at any time.',
    details: 'We reserve the right to refuse or cancel certain orders at our sole discretion. We have made every effort to display as accurately as possible the colors and images of our products that appear at the store. We cannot guarantee that your computer monitor\'s display of any color will be accurate. We reserve the right to limit the sales of our products or Services to any person, geographic region or jurisdiction.'
  },
  {
    value: 'item-3',
    title: '3. Links To Other Web Sites',
    summary: 'Our Service may contain links to third-party sites. SkinLab Beauty has no control over, and assumes no responsibility for, the content or privacy practices of these sites.',
    details: 'Our Service may contain links to third-party web sites or services that are not owned or controlled by SkinLab Beauty. SkinLab Beauty has no control over, and assumes no responsibility for, the content, privacy policies, or practices of any third party web sites or services. You acknowledge and agree that SkinLab Beauty shall not be responsible or liable, directly or indirectly, for any damage or loss caused or alleged to be caused by or in connection with use of or reliance on any such content, goods or services available on or through any such web sites or services.'
  },
  {
    value: 'item-4',
    title: '4. Contact Us',
    summary: 'If you have any questions about these Terms, please feel free to contact us.',
    details: 'If you have any questions about these Terms, please contact us at our <a href="/contact" class="text-rose-600 font-bold hover:underline">contact page</a> or email us directly at legal@skinlab.beauty.'
  },
];
</script>

<template>
    <Head title="Terms of Service" />

    <div class="bg-rose-50/30 min-h-screen py-10 md:py-20 font-sans">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <section class="text-center mb-10 md:mb-16">
                <div class="inline-flex items-center justify-center p-3 bg-white rounded-2xl shadow-sm mb-5 text-rose-600 border border-rose-100">
                    <FileText class="w-8 h-8" />
                </div>
                <h1 class="text-3xl md:text-5xl font-extrabold text-gray-900 tracking-tight mb-4">
                    Terms of Service
                </h1>
                <p class="text-base md:text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
                    Please read these terms carefully before using our service. 
                    By accessing or using the Service you agree to be bound by these Terms.
                </p>
                <p class="text-xs text-gray-400 mt-4 uppercase tracking-widest font-semibold">
                    Effective Date: January 1, 2025
                </p>
            </section>

            <section class="bg-white rounded-2xl md:rounded-3xl shadow-xl shadow-rose-100/50 border border-rose-100 overflow-hidden">
                <div class="divide-y divide-gray-100">
                    
                    <div v-for="term in terms" :key="term.value" class="group">
                        <button 
                            @click="openItem = (openItem === term.value ? null : term.value)"
                            class="w-full flex justify-between items-center text-left px-6 py-5 md:px-8 md:py-6 transition-all duration-300 hover:bg-gray-50 focus:outline-none"
                            :class="openItem === term.value ? 'bg-rose-50/30' : 'bg-white'"
                        >
                            <span 
                                class="text-lg md:text-xl font-bold transition-colors duration-300"
                                :class="openItem === term.value ? 'text-rose-600' : 'text-gray-800 group-hover:text-rose-600'"
                            >
                                {{ term.title }}
                            </span>
                            
                            <span 
                                class="ml-4 p-2 rounded-full transition-all duration-300"
                                :class="openItem === term.value ? 'bg-rose-100 text-rose-600 rotate-180' : 'bg-gray-100 text-gray-400 group-hover:bg-rose-50 group-hover:text-rose-500'"
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
                            <div v-if="openItem === term.value" class="overflow-hidden">
                                <div class="px-6 pb-6 md:px-8 md:pb-8 pt-0">
                                    
                                    <div class="bg-rose-50 rounded-xl p-4 md:p-5 mb-4 border border-rose-100">
                                        <div class="flex gap-3">
                                            <Info class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" />
                                            <p class="text-sm md:text-base font-medium text-gray-800 italic leading-snug">
                                                "{{ term.summary }}"
                                            </p>
                                        </div>
                                    </div>

                                    <div 
                                        class="prose prose-sm md:prose-base max-w-none text-gray-600 leading-relaxed pl-2 md:pl-4 border-l-2 border-gray-100" 
                                        v-html="term.details"
                                    ></div>
                                </div>
                            </div>
                        </Transition>
                    </div>

                </div>
            </section>

            <div class="mt-10 text-center">
                <p class="text-sm text-gray-500">
                    Still have questions regarding our terms? 
                    <Link href="/contact" class="text-rose-600 font-semibold hover:underline decoration-2">Contact Support</Link>.
                </p>
            </div>

        </div>
    </div>
</template>