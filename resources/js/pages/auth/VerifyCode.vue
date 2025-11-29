<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, nextTick, watch } from 'vue';

interface Props {
    email: string;
    status?: string;
}

const props = defineProps<Props>();
const page = usePage();

// 1. State Management
const code = ref<string[]>(new Array(6).fill(''));
const isSubmitting = ref(false);
const hasError = ref(false); // State for shake animation

const codeValue = computed(() => code.value.join(''));
const isCodeComplete = computed(() => codeValue.value.length === 6);

// 2. Watcher for Error (Shake Effect)
watch(() => page.props.errors, (newErrors) => {
    if (Object.keys(newErrors).length > 0) {
        triggerShake();
        // Reset input to allow user to retry
        code.value = new Array(6).fill('');
        document.getElementById('code-0')?.focus();
    }
}, { deep: true });

watch(() => page.props.flash, (newFlash) => {
    if (newFlash?.error) {
        triggerShake();
    }
}, { deep: true });

const triggerShake = () => {
    hasError.value = true;
    setTimeout(() => hasError.value = false, 500);
};

// 3. Submit Handler
const submitCode = async () => {
    if (!isCodeComplete.value || isSubmitting.value) return;
    
    isSubmitting.value = true;
    
    router.post('/email/verify', {
        code: codeValue.value,
        email: props.email
    }, {
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

// 4. Resend Handler
const resendCode = () => {
    isSubmitting.value = true;
    router.post('/email/verification-notification', {}, {
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

// 5. Input Logic (Numeric Only + Auto Focus)
const handleInput = (index: number, event: Event) => {
    const input = event.target as HTMLInputElement;
    const val = input.value;

    // Only allow numbers
    if (!/^\d*$/.test(val)) {
        code.value[index] = '';
        return;
    }

    // Move to next input
    if (val && index < 5) {
        nextTick(() => {
            document.getElementById(`code-${index + 1}`)?.focus();
        });
    }
    
    // Auto submit if complete
    if (isCodeComplete.value && index === 5) {
        submitCode();
    }
};

const handlePaste = (event: ClipboardEvent) => {
    const pasteData = event.clipboardData?.getData('text') || '';
    if (!/^\d{6}$/.test(pasteData)) return; // Only accept exactly 6 digits

    event.preventDefault();
    const digits = pasteData.split('');
    digits.forEach((digit, i) => {
        if (i < 6) code.value[i] = digit;
    });
    
    submitCode();
};

const handleKeyDown = (index: number, event: KeyboardEvent) => {
    if (event.key === 'Backspace' && !code.value[index] && index > 0) {
        // Move back if current input is empty
        nextTick(() => {
            const prev = document.getElementById(`code-${index - 1}`);
            prev?.focus();
        });
    }
};

onMounted(() => {
    document.getElementById('code-0')?.focus();
});
</script>

<template>
    <AuthLayout
        title="Verify Your Email"
        :description="`Please enter the 6-digit verification code sent to ${email}`"
    >
        <Head title="Email Verification" />

        <!-- Success Status (Resend Link) -->
        <div
            v-if="status"
            class="mb-6 rounded-xl bg-green-50 p-4 text-sm text-green-700 border border-green-200 shadow-sm flex items-center gap-2 animate-in fade-in slide-in-from-top-2"
        >
            <span class="text-lg">✅</span>
            <span class="font-medium">{{ status }}</span>
        </div>

        <!-- General Error (e.g. Expired Token / Flash Error) -->
        <div 
            v-if="$page.props.flash?.error" 
            class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-700 border border-red-200 shadow-sm flex items-center gap-2 animate-in fade-in slide-in-from-top-2"
        >
            <span class="text-lg">⚠️</span>
            <span class="font-medium">{{ $page.props.flash.error }}</span>
        </div>

        <div class="space-y-8">
            <!-- Code Input Grid -->
            <div class="flex flex-col items-center gap-4">
                <div 
                    class="flex justify-center gap-2 sm:gap-3 transition-transform duration-200"
                    :class="{ 'animate-shake': hasError }"
                    @paste="handlePaste"
                >
                    <input
                        v-for="(_, index) in 6"
                        :key="index"
                        :id="`code-${index}`"
                        v-model="code[index]"
                        type="text"
                        maxlength="1"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        autocomplete="one-time-code"
                        :disabled="isSubmitting"
                        class="h-12 w-12 sm:h-14 sm:w-14 rounded-xl border text-center text-xl font-bold transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-rose-100 shadow-sm"
                        :class="[
                            page.props.errors?.code || $page.props.flash?.error
                                ? 'border-red-300 bg-red-50 text-red-600 focus:border-red-500 focus:ring-red-100' 
                                : 'border-gray-300 text-gray-800 focus:border-rose-500'
                        ]"
                        @input="(e) => handleInput(index, e)"
                        @keydown="(e) => handleKeyDown(index, e)"
                    />
                </div>

                <!-- Validation Error Display -->
                <div v-if="page.props.errors?.code" class="text-center animate-in fade-in slide-in-from-top-1">
                    <p class="text-sm font-bold text-red-600 bg-red-50 px-4 py-2 rounded-full inline-flex items-center gap-2 border border-red-100">
                        <span>🚫</span> {{ page.props.errors.code }}
                    </p>
                </div>
                
                <!-- Helper Text -->
                <p v-else class="text-xs text-gray-400 font-medium">
                    Please check your inbox (and spam folder).
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-6">
                <!-- Verify Button -->
                <Button
                    @click="submitCode"
                    :disabled="!isCodeComplete || isSubmitting"
                    class="w-full bg-gradient-to-r from-rose-500 to-pink-500 hover:from-rose-600 hover:to-pink-600 text-white font-medium py-3 px-4 rounded-xl transition-all duration-300 transform hover:scale-[1.02] shadow-lg hover:shadow-xl border-0 disabled:opacity-70 disabled:cursor-not-allowed disabled:transform-none text-base disabled:shadow-none"
                    size="lg"
                >
                    <Spinner v-if="isSubmitting" class="mr-2 h-5 w-5" />
                    {{ isSubmitting ? 'Verifying...' : 'Verify Email' }}
                </Button>

                <!-- Resend Button -->
                <div class="text-center pt-2 border-t border-gray-100 mt-4">
                    <p class="text-sm text-gray-600 mb-3">Didn't receive the code?</p>
                    <button
                        @click="resendCode"
                        :disabled="isSubmitting"
                        class="text-sm text-rose-600 hover:text-rose-700 font-bold transition-colors duration-200 flex items-center justify-center gap-2 mx-auto group bg-white border border-rose-200 px-4 py-2 rounded-lg hover:bg-rose-50 hover:border-rose-300 disabled:opacity-50"
                    >
                        <svg class="w-4 h-4 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        Resend Verification Email
                    </button>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>

<style scoped>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-4px); }
    20%, 40%, 60%, 80% { transform: translateX(4px); }
}
.animate-shake {
    animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
}
</style>