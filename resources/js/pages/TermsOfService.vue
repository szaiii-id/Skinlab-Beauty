<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
// Use your main navbar layout
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import { ChevronDown } from 'lucide-vue-next'; // Icon for the arrow

defineOptions({
    layout: AppNavbarLayout
});

// State to control which accordion item is open
const openItem = ref('item-1'); // Default to item-1 being open

// Your legal content, broken down into sections
const terms = [
  {
    value: 'item-1',
    title: '1. Your Account',
    summary: 'You are responsible for maintaining the security of your account and password. Please provide accurate information.',
    details: 'When you create an account with us, you must provide us with information that is accurate, complete, and current at all times. Failure to do so constitutes a breach of the Terms, which may result in immediate termination of your account on our Service. You are responsible for safeguarding the password...'
  },
  {
    value: 'item-2',
    title: '2. Products or Services',
    summary: 'We do our best to display product colors and images accurately. We reserve the right to refuse or cancel any order at any time.',
    details: 'We reserve the right to refuse or cancel certain orders at our sole discretion. We have made every effort to display as accurately as possible the colors and images of our products that appear at the store. We cannot guarantee that your computer monitor\'s display of any color will be accurate.'
  },
  {
    value: 'item-3',
    title: '3. Links To Other Web Sites',
    summary: 'Our Service may contain links to third-party sites. SkinLab Beauty has no control over, and assumes no responsibility for, the content or privacy practices of these sites.',
    details: 'Our Service may contain links to third-party web sites or services that are not owned or controlled by SkinLab Beauty. SkinLab Beauty has no control over, and assumes no responsibility for, the content, privacy policies, or practices of any third party web sites or services.'
  },
  {
    value: 'item-4',
    title: '4. Contact Us',
    summary: 'If you have any questions about these Terms, please feel free to contact us.',
    details: 'If you have any questions about these Terms, please contact us at our <a href="/contact" class="text-rose-600 hover:underline">contact page</a>.'
  },
];
</script>

<template>
    <Head title="Terms of Service" />

    <div class="bg-white py-16 md:py-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <section class="text-center mb-16">
                <h1 class="text-5xl font-extrabold text-gray-900 tracking-tighter">
                    Terms of Service
                </h1>
                <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                    Please read these Terms of Service carefully before using the 
                    SkinLab Beauty website.
                </p>
            </section>

            <section class="space-y-4">
                <div v-for="term in terms" :key="term.value">
                    <button 
                        @click="openItem = (openItem === term.value ? null : term.value)"
                        class="w-full flex justify-between items-center text-left px-6 py-4 bg-rose-50 rounded-lg shadow-sm hover:bg-rose-100 transition-colors"
                    >
                        <span class="text-xl font-semibold text-rose-900">{{ term.title }}</span>
                        <ChevronDown 
                            class="h-6 w-6 text-rose-600 transition-transform duration-300"
                            :class="{ 'rotate-180': openItem === term.value }"
                        />
                    </button>
                    
                    <Transition
                        enter-active-class="transition ease-out duration-300"
                        enter-from-class="transform opacity-0 -translate-y-2"
                        enter-to-class="transform opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-200"
                        leave-from-class="transform opacity-100 translate-y-0"
                        leave-to-class="transform opacity-0 -translate-y-2"
                    >
                        <div v-if="openItem === term.value" class="px-6 py-4 mt-2 bg-gray-50 rounded-b-lg border border-gray-200">
                            <p class="text-lg text-gray-700 italic mb-4">
                                {{ term.summary }}
                            </p>
                            <div class="prose prose-sm max-w-none text-gray-600" v-html="term.details">
                            </div>
                        </div>
                    </Transition>
                </div>
            </section>

        </div>
    </div>
</template>