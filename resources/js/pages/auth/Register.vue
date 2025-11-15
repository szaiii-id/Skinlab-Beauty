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
            // ✅ REDIRECT KE VERIFICATION PAGE SETELAH REGISTER SUKSES
            window.location.href = '/email/verify';
        },
        onError: (errors) => {
            console.log('Registration errors:', errors);
            // Scroll ke top jika ada error
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
        description="Enter your details below to create your account"
    >
        <Head title="Register" />

        <!-- Success Message -->
        <div 
            v-if="$page.props.flash.success" 
            class="mb-6 rounded-lg bg-green-50 p-4 text-sm text-green-700"
        >
            {{ $page.props.flash.success }}
        </div>

        <!-- Error Summary -->
        <div 
            v-if="Object.keys(form.errors).length > 0" 
            class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-700"
        >
            <p class="font-medium">Please fix the following errors:</p>
            <ul class="mt-1 list-disc list-inside">
                <li v-for="error in form.errors" :key="error">
                    {{ error }}
                </li>
            </ul>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <!-- Name Field -->
                <div class="grid gap-2">
                    <Label for="name" class="flex items-center gap-1">
                        Full Name
                        <span class="text-red-500">*</span>
                    </Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        autocomplete="name"
                        v-model="form.name"
                        placeholder="Enter your full name"
                        :class="{ 'border-red-500': form.errors.name }"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <!-- Email Field -->
                <div class="grid gap-2">
                    <Label for="email" class="flex items-center gap-1">
                        Email Address
                        <span class="text-red-500">*</span>
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="your.email@example.com"
                        :class="{ 'border-red-500': form.errors.email }"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <!-- Password Field -->
                <div class="grid gap-2">
                    <Label for="password" class="flex items-center gap-1">
                        Password
                        <span class="text-red-500">*</span>
                    </Label>
                    <div class="relative">
                        <Input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="new-password"
                            v-model="form.password"
                            placeholder="Create a strong password"
                            :class="{ 'border-red-500': form.errors.password, 'pr-10': true }"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                            @click="togglePasswordVisibility"
                        >
                            <span class="sr-only">
                                {{ showPassword ? 'Hide password' : 'Show password' }}
                            </span>
                            <svg 
                                v-if="showPassword" 
                                class="h-4 w-4" 
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L7.757 7.757M9.878 9.878l2.121-2.121" />
                            </svg>
                            <svg 
                                v-else 
                                class="h-4 w-4" 
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <InputError :message="form.errors.password" />
                    <p class="text-xs text-gray-500 mt-1">
                        Password must be at least 8 characters long
                    </p>
                </div>

                <!-- Confirm Password Field -->
                <div class="grid gap-2">
                    <Label for="password_confirmation" class="flex items-center gap-1">
                        Confirm Password
                        <span class="text-red-500">*</span>
                    </Label>
                    <div class="relative">
                        <Input
                            id="password_confirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            required
                            autocomplete="new-password"
                            v-model="form.password_confirmation"
                            placeholder="Confirm your password"
                            :class="{ 'border-red-500': form.errors.password_confirmation, 'pr-10': true }"
                        />
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                            @click="toggleConfirmPasswordVisibility"
                        >
                            <span class="sr-only">
                                {{ showConfirmPassword ? 'Hide password' : 'Show password' }}
                            </span>
                            <svg 
                                v-if="showConfirmPassword" 
                                class="h-4 w-4" 
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L7.757 7.757M9.878 9.878l2.121-2.121" />
                            </svg>
                            <svg 
                                v-else 
                                class="h-4 w-4" 
                                fill="none" 
                                stroke="currentColor" 
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <!-- Terms Agreement - FIXED CHECKBOX -->
                <div class="flex items-start gap-3">
                    <!-- ✅ GUNAKAN NATIVE HTML CHECKBOX -->
                    <input
                        id="terms"
                        type="checkbox"
                        class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        v-model="form.terms"
                    />
                    <Label for="terms" class="text-sm leading-relaxed cursor-pointer">
                        I agree to the 
                        <a href="/terms-of-service" class="text-blue-600 hover:text-blue-800 underline" target="_blank">
                            Terms of Service
                        </a> 
                        and 
                        <a href="/privacy-policy" class="text-blue-600 hover:text-blue-800 underline" target="_blank">
                            Privacy Policy
                        </a>
                    </Label>
                </div>
                <InputError :message="form.errors.terms" />

                <!-- Submit Button -->
                <Button
                    type="submit"
                    class="w-full mt-2"
                    :disabled="form.processing"
                    size="lg"
                >
                    <Spinner v-if="form.processing" class="mr-2 h-4 w-4" />
                    {{ form.processing ? 'Creating Account...' : 'Create Account' }}
                </Button>
            </div>

            <!-- Login Link -->
            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink
                    href="/login"
                    class="underline underline-offset-4 font-medium"
                >
                    Log in here
                </TextLink>
            </div>
        </form>
    </AuthLayout>
</template>