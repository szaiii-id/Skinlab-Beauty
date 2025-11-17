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

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const submit = () => {
    form.post('/register', {
        onSuccess: () => {
            window.location.href = '/email/verify';
        },
        onError: (errors) => {
            console.log('Registration errors:', errors);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        }
    });
};

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const toggleConfirmPasswordVisibility = () => {
    showConfirmPassword.value = !showConfirmPassword.value;
};
</script>

<template>
    <AuthLayout
        title="Create an account"
        description="Join our beauty community and discover your perfect skincare routine"
    >
        <Head title="Register" />

        <!-- Success Message -->
        <div 
            v-if="$page.props.flash.success" 
            class="mb-6 rounded-xl bg-green-50 p-4 text-sm text-green-700 border border-green-200"
        >
            {{ $page.props.flash.success }}
        </div>

        <!-- Error Summary -->
        <div 
            v-if="Object.keys(form.errors).length > 0" 
            class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-700 border border-red-200"
        >
            <p class="font-medium">Please fix the following errors:</p>
            <ul class="mt-1 list-disc list-inside">
                <li v-for="error in form.errors" :key="error">
                    {{ error }}
                </li>
            </ul>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6 w-full">
            <div class="grid gap-6">
                <!-- Name Field -->
                <div class="grid gap-2">
                    <Label for="name" class="text-rose-700 font-medium text-base">
                        Full Name
                    </Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        v-model="form.name"
                        placeholder="Enter your full name"
                        :class="{ 'border-red-300': form.errors.name }"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <!-- Email Field -->
                <div class="grid gap-2">
                    <Label for="email" class="text-rose-700 font-medium text-base">
                        Email Address
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="your.email@example.com"
                        :class="{ 'border-red-300': form.errors.email }"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <!-- Password Field -->
                <div class="grid gap-2">
                    <Label for="password" class="text-rose-700 font-medium text-base">
                        Password
                    </Label>
                    <div class="relative">
                        <Input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="new-password"
                            v-model="form.password"
                            placeholder="Create a strong password"
                            :class="{ 'border-red-300': form.errors.password }"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-rose-500 hover:text-rose-600 transition-colors duration-200"
                            @click="togglePasswordVisibility"
                        >
                            <span class="sr-only">
                                {{ showPassword ? 'Hide password' : 'Show password' }}
                            </span>
                            <svg 
                                v-if="showPassword" 
                                class="h-5 w-5" 
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L7.757 7.757M9.878 9.878l2.121-2.121" />
                            </svg>
                            <svg 
                                v-else 
                                class="h-5 w-5" 
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <InputError :message="form.errors.password" />
                    <p class="text-xs text-rose-600 mt-1" :class="{ 'text-red-500': form.errors.password }">
                        Password must be at least 8 characters long
                    </p>
                </div>

                <!-- Confirm Password Field -->
                <div class="grid gap-2">
                    <Label for="password_confirmation" class="text-rose-700 font-medium text-base">
                        Confirm Password
                    </Label>
                    <div class="relative">
                        <Input
                            id="password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            required
                            autocomplete="new-password"
                            v-model="form.password_confirmation"
                            placeholder="Confirm your password"
                            :class="{ 'border-red-300': form.errors.password_confirmation }"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-rose-500 hover:text-rose-600 transition-colors duration-200"
                            @click="toggleConfirmPasswordVisibility"
                        >
                            <span class="sr-only">
                                {{ showConfirmPassword ? 'Hide password' : 'Show password' }}
                            </span>
                            <svg 
                                v-if="showConfirmPassword" 
                                class="h-5 w-5" 
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L7.757 7.757M9.878 9.878l2.121-2.121" />
                            </svg>
                            <svg 
                                v-else 
                                class="h-5 w-5" 
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <!-- Elegant Terms Agreement -->
                <div class="space-y-4">
                    <div class="flex items-start gap-4 p-4 rounded-xl border border-rose-200 bg-rose-50/50 transition-all duration-200 hover:border-rose-300">
                        <div class="flex items-center h-5 mt-0.5">
                            <input
                                id="terms"
                                type="checkbox"
                                class="h-5 w-5 rounded border-rose-300 text-rose-500 focus:ring-rose-400 transition-colors duration-200"
                                v-model="form.terms"
                            />
                        </div>
                        <div class="flex-1 min-w-0">
                            <Label for="terms" class="text-sm leading-relaxed cursor-pointer text-rose-700 font-medium block mb-1">
                                Agreement to Terms & Policies
                            </Label>
                            <p class="text-xs text-rose-600 leading-snug">
                                By creating an account, you agree to our 
                                <a href="/terms-of-service" class="text-rose-500 hover:text-rose-700 underline font-medium transition-colors duration-200" target="_blank">
                                    Terms of Service
                                </a> 
                                and acknowledge you have read our 
                                <a href="/privacy-policy" class="text-rose-500 hover:text-rose-700 underline font-medium transition-colors duration-200" target="_blank">
                                    Privacy Policy
                                </a>.
                            </p>
                        </div>
                    </div>
                    <InputError :message="form.errors.terms" class="text-center" />
                </div>

                <!-- Premium Submit Button -->
                <Button
                    type="submit"
                    class="w-full mt-2 bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white font-medium py-3 px-4 rounded-xl transition-all duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl border-0 text-base"
                    :disabled="form.processing"
                    size="lg"
                >
                    <Spinner v-if="form.processing" class="mr-2 h-5 w-5" />
                    {{ form.processing ? 'Creating Account...' : 'Create Account' }}
                </Button>
            </div>

            <!-- Elegant Login Link -->
            <div class="text-center text-sm text-rose-600 pt-4 border-t border-rose-100">
                Already have an account?
                <TextLink
                    href="/login"
                    class="text-rose-500 hover:text-rose-700 font-medium underline transition-colors duration-200 ml-1"
                >
                    Log in here
                </TextLink>
            </div>
        </form>
    </AuthLayout>
</template>