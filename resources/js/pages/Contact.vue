<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import { Mail, Phone, MapPin, Send } from 'lucide-vue-next';

defineOptions({
    layout: AppNavbarLayout
});

// Form handling
const form = useForm({
    name: '',
    email: '',
    message: ''
});

const submitContactForm = () => {
    // Simulasi submit
    alert('Message sent (simulation)!');
    form.reset();
};
</script>

<template>
    <Head title="Contact Us" />

    <div class="bg-rose-50/30 min-h-screen py-10 md:py-20 font-sans">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            <section class="text-center mb-12 md:mb-16">
                <span class="text-rose-600 font-bold tracking-widest text-xs md:text-sm uppercase mb-2 block">
                    We're here for you
                </span>
                <h1 class="text-3xl md:text-5xl font-extrabold text-gray-900 tracking-tight mb-4">
                    Get in Touch
                </h1>
                <p class="text-base md:text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
                    Have a question about your skin type or our products? 
                    Our beauty experts are ready to help you glow.
                </p>
            </section>

            <section class="mb-12 md:mb-20">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
                    
                    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-rose-100 text-center hover:shadow-md transition-shadow">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-rose-50 text-rose-600 mb-4">
                            <Mail class="h-6 w-6" />
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Email Us</h3>
                        <p class="text-sm text-gray-500 mt-1 mb-3">
                            For general inquiries & support
                        </p>
                        <a href="mailto:support@skinlab.test" class="text-rose-600 font-medium hover:underline break-all">
                            support@skinlab.test
                        </a>
                    </div>
                    
                    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-rose-100 text-center hover:shadow-md transition-shadow">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-rose-50 text-rose-600 mb-4">
                            <Phone class="h-6 w-6" />
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Call Us</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Mon - Sat, 09:00 - 17:00
                        </p>
                        <a href="tel:+62123456789" class="block mt-2 text-rose-600 font-medium hover:underline">
                            +62 123 456 789
                        </a>
                    </div>

                    <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-rose-100 text-center hover:shadow-md transition-shadow">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-rose-50 text-rose-600 mb-4">
                            <MapPin class="h-6 w-6" />
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Visit HQ</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Come say hello at our office
                        </p>
                        <p class="mt-2 text-gray-800 font-medium text-sm">
                            123 Beauty St, Jakarta
                        </p>
                    </div>
                </div>
            </section>

            <section class="max-w-3xl mx-auto">
                <div class="bg-white rounded-2xl md:rounded-3xl shadow-xl shadow-rose-100/50 border border-rose-100 overflow-hidden">
                    <div class="p-6 md:p-10">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-bold text-gray-900">Send us a message</h2>
                            <p class="text-gray-500 text-sm mt-1">We usually reply within 24 hours.</p>
                        </div>

                        <form @submit.prevent="submitContactForm" class="space-y-5 md:space-y-6">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Your Name</label>
                                    <input 
                                        v-model="form.name"
                                        type="text" 
                                        id="name" 
                                        required
                                        placeholder="John Doe"
                                        class="block w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 focus:border-rose-500 focus:bg-white focus:ring-rose-500 transition-all text-sm shadow-sm"
                                    />
                                </div>
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Your Email</label>
                                    <input 
                                        v-model="form.email"
                                        type="email" 
                                        id="email" 
                                        required
                                        placeholder="john@example.com"
                                        class="block w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 focus:border-rose-500 focus:bg-white focus:ring-rose-500 transition-all text-sm shadow-sm"
                                    />
                                </div>
                            </div>

                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                                <textarea 
                                    v-model="form.message"
                                    id="message" 
                                    rows="5" 
                                    required
                                    placeholder="How can we help you today?"
                                    class="block w-full rounded-xl border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 focus:border-rose-500 focus:bg-white focus:ring-rose-500 transition-all text-sm shadow-sm resize-none"
                                ></textarea>
                            </div>

                            <div>
                                <button 
                                    type="submit"
                                    class="w-full flex justify-center items-center gap-2 px-6 py-3.5 bg-rose-600 text-white font-bold rounded-xl shadow-lg shadow-rose-200 hover:bg-rose-700 hover:shadow-xl transition-all duration-300 transform active:scale-95"
                                >
                                    <span>Send Message</span>
                                    <Send class="w-4 h-4" />
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>
            
        </div>
    </div>
</template>