<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

// 1. Initialize Form
const form = useForm({
    password: '',
});

// [NEW] Client-side Error State
const clientErrors = ref({
    password: ''
});

// 2. Toggle Visibility State
const showPassword = ref(false);

// 3. Validation Logic
const validateForm = () => {
    let isValid = true;
    clientErrors.value.password = '';

    if (!form.password) {
        clientErrors.value.password = 'Password is required to continue.';
        isValid = false;
    }

    return isValid;
};

// 4. Submit Handler
const submit = () => {
    if (!validateForm()) {
        return;
    }

    form.post('/user/confirm-password', {
        onFinish: () => {
            form.reset();
        },
    });
};

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};
</script>

<template>
    <AuthLayout
        title="Confirm your password"
        description="This is a secure area of the application. Please confirm your password before continuing."
    >
        <Head title="Confirm password" />

        <form @submit.prevent="submit" class="flex flex-col gap-6" novalidate>
            <div class="space-y-6">
                
                <div class="grid gap-2">
                    <Label for="password" class="text-rose-700 font-medium text-base">
                        Password
                    </Label>
                    
                    <div class="relative">
                        <Input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            class="transition-all duration-200 pr-10"
                            :class="{ 
                                'border-red-500 focus:border-red-500 focus:ring-red-500 bg-red-50/10': form.errors.password || clientErrors.password,
                                'border-gray-300 focus:border-rose-500 focus:ring-rose-500': !form.errors.password && !clientErrors.password
                            }"
                            autocomplete="current-password"
                            autofocus
                            v-model="form.password"
                            placeholder="Enter your password"
                        />
                        
                        <button
                            type="button"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-rose-400 hover:text-rose-600 transition-colors duration-200 focus:outline-none"
                            @click="togglePasswordVisibility"
                            tabindex="-1"
                        >
                            <span class="sr-only">
                                {{ showPassword ? 'Hide password' : 'Show password' }}
                            </span>
                             <svg v-if="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L7.757 7.757M9.878 9.878l2.121-2.121" /></svg>
                             <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        </button>
                    </div>

                    <InputError :message="form.errors.password || clientErrors.password" />
                </div>

                <div class="flex items-center">
                    <Button
                        class="w-full bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white font-medium py-3 px-4 rounded-xl transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl border-0 disabled:opacity-70 disabled:cursor-not-allowed"
                        :disabled="form.processing"
                        type="submit"
                        data-test="confirm-password-button"
                    >
                        <Spinner v-if="form.processing" class="mr-2 h-5 w-5" />
                        {{ form.processing ? 'Confirming...' : 'Confirm Password' }}
                    </Button>
                </div>
            </div>
        </form>
    </AuthLayout>
</template>