<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { store } from '@/routes/password/confirm';
import { Form, Head } from '@inertiajs/vue3';
</script>

<template>
    <AuthLayout
        title="Confirm your password"
        description="This is a secure area of the application. Please confirm your password before continuing."
    >
        <Head title="Confirm password" />

        <Form
            v-bind="store.form()"
            reset-on-success
            v-slot="{ errors, processing }"
        >
            <div class="space-y-6">
                <div class="grid gap-2">
                    <Label htmlFor="password" class="text-gray-700 font-medium">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        class="mt-1 block w-full border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                        required
                        autocomplete="current-password"
                        autofocus
                    />

                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center">
                    <Button
                        class="w-full bg-rose-600 hover:bg-rose-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 shadow-lg"
                        :disabled="processing"
                        data-test="confirm-password-button"
                    >
                        <Spinner v-if="processing" />
                        Confirm Password
                    </Button>
                </div>
            </div>
        </Form>
    </AuthLayout>
</template>