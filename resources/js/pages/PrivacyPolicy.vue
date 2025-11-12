<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import { ChevronDown } from 'lucide-vue-next';

defineOptions({
    layout: AppNavbarLayout
});

const openItem = ref('item-1');

// Your privacy content, broken down
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
    details: 'We may use information to: <ul><li>Operate and maintain our website;</li><li>Improve, personalize, and expand our website;</li><li>Understand and analyze how you use our website;</li><li>Process your transactions and manage your orders.</li></ul>'
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

    <div class="bg-white py-16 md:py-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            
            <section class="text-center mb-16">
                <h1 class="text-5xl font-extrabold text-gray-900 tracking-tighter">
                    Privacy Policy
                </h1>
                <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                    Your privacy is important to us. Our policy is to respect 
                    your privacy regarding any information we may collect.
                </p>
            </section>

            <section class="space-y-4">
                <div v-for="point in privacyPoints" :key="point.value">
                    <button 
                        @click="openItem = (openItem === point.value ? null : point.value)"
                        class="w-full flex justify-between items-center text-left px-6 py-4 bg-rose-50 rounded-lg shadow-sm hover:bg-rose-100 transition-colors"
                    >
                        <span class="text-xl font-semibold text-rose-900">{{ point.title }}</span>
                        <ChevronDown 
                            class="h-6 w-6 text-rose-600 transition-transform duration-300"
                            :class="{ 'rotate-180': openItem === point.value }"
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
                        <div v-if="openItem === point.value" class="px-6 py-4 mt-2 bg-gray-50 rounded-b-lg border border-gray-200">
                            <p class="text-lg text-gray-700 italic mb-4">
                                {{ point.summary }}
                            </p>
                            <div class="prose prose-sm max-w-none text-gray-600" v-html="point.details">
                            </div>
                        </div>
                    </Transition>
                </div>
            </section>

        </div>
    </div>
</template>