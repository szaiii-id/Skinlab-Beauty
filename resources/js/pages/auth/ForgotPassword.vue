<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    status?: string;
}>();

// 1. Initialize Form
const form = useForm({
    email: '',
});

// [NEW] Client-side Error State
const clientErrors = ref({
    email: ''
});

// 2. Validation Logic
const validateForm = () => {
    let isValid = true;
    clientErrors.value.email = '';

    if (!form.email) {
        clientErrors.value.email = 'Email Address is required.';
        isValid = false;
    } else if (!/^\S+@\S+\.\S+$/.test(form.email)) {
        clientErrors.value.email = 'Please enter a valid email address.';
        isValid = false;
    }

    return isValid;
};

// 3. Submit Handler
const submit = () => {
    // Run client validation first
    if (!validateForm()) {
        return;
    }

    form.post('/forgot-password', {
        onFinish: () => {
            // Optional: You can clear the email or keep it
            // form.reset(); 
        },
    });
};
</script>

<template>
    <AuthLayout
        title="Forgot password?"
        description="No worries! Enter your email and we will send you a reset link."
    >
        <Head title="Forgot Password" />

        <div 
            v-if="status" 
            class="mb-6 rounded-xl bg-green-50 p-4 text-sm text-green-700 border border-green-200 shadow-sm flex items-start gap-3"
        >
            <span class="text-lg">✉️</span>
            <div>
                <p class="font-bold">Email Sent!</p>
                <p>{{ status }}</p>
            </div>
        </div>

        <form @submit.prevent="submit" class="space-y-6" novalidate>
            
            <div class="grid gap-2">
                <Label for="email" class="text-rose-700 font-medium text-base">
                    Email Address
                </Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    v-model="form.email"
                    autofocus
                    autocomplete="username"
                    placeholder="name@example.com"
                    class="transition-all duration-200"
                    :class="{ 
                        'border-red-500 focus:border-red-500 focus:ring-red-500 bg-red-50/10': form.errors.email || clientErrors.email,
                        'border-gray-300 focus:border-rose-500 focus:ring-rose-500': !form.errors.email && !clientErrors.email
                    }"
                />
                <InputError :message="form.errors.email || clientErrors.email" />
            </div>

            <div class="flex items-center justify-center pt-2">
                <Button
                    class="w-full bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white font-medium py-3 px-4 rounded-xl transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl border-0 disabled:opacity-70 disabled:cursor-not-allowed"
                    :disabled="form.processing"
                    type="submit"
                >
                    <Spinner v-if="form.processing" class="mr-2 h-5 w-5" />
                    {{ form.processing ? 'Sending Link...' : 'Email Password Reset Link' }}
                </Button>
            </div>

            <div class="text-center text-sm text-gray-600 border-t border-rose-100 pt-4">
                Remember your password? 
                <TextLink href="/login" class="text-rose-600 hover:text-rose-800 font-bold hover:underline ml-1">
                    Log in
                </TextLink>
            </div>
        </form>
    </AuthLayout>
</template>