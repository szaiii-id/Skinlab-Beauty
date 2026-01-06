<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import { HelpCircle, MessageCircle } from 'lucide-vue-next';
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion';

defineOptions({
    layout: AppNavbarLayout
});

// Daftar Q&A
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

    <div class="bg-rose-50/30 min-h-screen py-10 md:py-20 font-sans">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <section class="text-center mb-10 md:mb-16">
                <div class="inline-flex items-center justify-center p-3 bg-rose-100 rounded-full mb-4 text-rose-600">
                    <HelpCircle class="w-6 h-6" />
                </div>
                <h1 class="text-3xl md:text-5xl font-extrabold text-gray-900 tracking-tight mb-3">
                    Frequently Asked Questions
                </h1>
                <p class="text-base md:text-lg text-gray-600 max-w-xl mx-auto leading-relaxed">
                    Everything you need to know about our products and billing. Can’t find the answer? Chat to our friendly team.
                </p>
            </section>

            <section class="bg-white rounded-2xl md:rounded-3xl shadow-xl shadow-rose-100/50 border border-rose-100 overflow-hidden">
                <Accordion type="single" collapsible class="w-full divide-y divide-rose-50">
                    
                    <AccordionItem v-for="faq in faqs" :key="faq.value" :value="faq.value" class="border-b-0">
                        <AccordionTrigger class="px-5 md:px-8 py-4 md:py-5 text-base md:text-lg font-bold text-left text-gray-800 hover:text-rose-600 hover:bg-rose-50/30 transition-colors [&[data-state=open]]:text-rose-600">
                            {{ faq.question }}
                        </AccordionTrigger>
                        
                        <AccordionContent class="px-5 md:px-8 pb-5 pt-0 prose max-w-none text-sm md:text-base text-gray-600 leading-relaxed">
                            {{ faq.answer }}
                        </AccordionContent>
                    </AccordionItem>

                </Accordion>
            </section>

            <section class="mt-10 md:mt-16 text-center">
                <div class="bg-white p-6 rounded-2xl border border-dashed border-rose-200 inline-block shadow-sm">
                    <div class="flex flex-col md:flex-row items-center gap-4">
                        <div class="bg-rose-50 p-3 rounded-full text-rose-600">
                            <MessageCircle class="w-5 h-5" />
                        </div>
                        <div class="text-left">
                            <h4 class="text-sm font-bold text-gray-900">Still have questions?</h4>
                            <p class="text-xs text-gray-500">Can’t find the answer you’re looking for? Please chat to our friendly team.</p>
                        </div>
                        <Link 
                            href="/contact" 
                            class="w-full md:w-auto px-5 py-2.5 bg-rose-600 text-white text-sm font-semibold rounded-xl hover:bg-rose-700 transition-colors shadow-lg shadow-rose-200"
                        >
                            Contact Us
                        </Link>
                    </div>
                </div>
            </section>

        </div>
    </div>
</template>