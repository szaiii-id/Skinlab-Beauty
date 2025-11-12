<script setup>
import { Head } from '@inertiajs/vue3';
// Gunakan layout navbar Anda
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
// Import komponen Accordion bawaan starter kit Anda
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion';

defineOptions({
    layout: AppNavbarLayout
});

// Daftar Q&A (Nanti bisa Anda pindahkan ke database jika mau)
const faqs = [
  {
    value: 'item-1',
    question: 'What is your shipping policy?',
    answer: 'We offer free shipping on all orders over $50. For orders under $50, a flat rate of $5 applies. Most orders are processed within 1-2 business days.'
  },
  {
    value: 'item-2',
    question: 'What is your return policy?',
    answer: 'We accept returns within 30 days of purchase, provided the product is unused and in its original packaging. Please visit our "Shipping & Returns" page for full details.'
  },
  {
    value: 'item-3',
    question: 'Are your products cruelty-free?',
    answer: 'Yes! We are 100% committed to cruelty-free beauty. None of our products or the ingredients we use are tested on animals. This is a core part of our brand philosophy.'
  },
  {
    value: 'item-4',
    question: 'How do I track my order?',
    answer: 'Once your order has shipped, you will receive an email with a tracking number and a link to the carrier\'s website. You can also track your order status from your "My Account" page if you were logged in during checkout.'
  },
  {
    value: 'item-5',
    question: 'Do you offer international shipping?',
    answer: 'Currently, we only ship within [Your Country/Region]. We are working on expanding our shipping options, so please subscribe to our newsletter for updates!'
  }
];
</script>

<template>
    <Head title="FAQ" />

    <div class="bg-white py-16 md:py-24">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

            <section class="text-center mb-16">
                <h1 class="text-5xl font-extrabold text-gray-900 tracking-tighter">
                    Frequently Asked Questions
                </h1>
                <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                    Have questions? We're here to help. Find answers to common queries below.
                </p>
            </section>

            <section>
                <Accordion type="single" collapsible class="w-full">
                    
                    <AccordionItem v-for="faq in faqs" :key="faq.value" :value="faq.value">
                        <AccordionTrigger class="text-lg font-medium text-left text-gray-800 hover:text-rose-600">
                            {{ faq.question }}
                        </AccordionTrigger>
                        <AccordionContent class="prose max-w-none text-gray-600">
                            <p>{{ faq.answer }}</p>
                        </AccordionContent>
                    </AccordionItem>

                </Accordion>
            </section>
        </div>
    </div>
</template>