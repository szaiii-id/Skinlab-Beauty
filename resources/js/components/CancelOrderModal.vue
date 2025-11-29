<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    order: Object
});

const emit = defineEmits(['close', 'success']);

const cancelOptions = [
    "Want to change shipping address",
    "Want to modify order details (color/size)",
    "Forgot to use voucher code",
    "Changed mind / Not buying anymore",
    "Other"
];

const selectedReason = ref(cancelOptions[0]);
const customReason = ref('');

const form = useForm({
    reason: ''
});

const submitCancel = () => {
    if (selectedReason.value === 'Other') {
        if (!customReason.value.trim()) {
            form.setError('reason', 'Please provide a reason.');
            return;
        }
        form.reason = customReason.value;
    } else {
        form.reason = selectedReason.value;
    }

    form.post(`/orders/${props.order.id}/cancel`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            emit('close');
            form.reset();
            customReason.value = '';
            Swal.fire('Canceled!', 'Order cancellation submitted.', 'success');
        }
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
            <div class="bg-red-50 border-b border-red-100 px-6 py-4">
                <h3 class="font-bold text-lg text-red-700">Cancel Order</h3>
            </div>

            <div class="p-6">
                <p class="text-gray-600 mb-4 text-sm">
                    Please select a reason for canceling order <span class="font-bold text-gray-900">#{{ order?.order_number }}</span>:
                    <span v-if="order?.order_status === 'paid'" class="block mt-2 text-orange-600 font-bold text-xs bg-orange-50 p-2 rounded border border-orange-200">
                        ⚠️ Payment received. Cancellation requires admin approval for refund.
                    </span>
                </p>

                <div class="space-y-3 mb-4">
                    <div v-for="(option, index) in cancelOptions" :key="index" class="flex items-center">
                        <input type="radio" :id="'cancel-' + index" :value="option" v-model="selectedReason" class="w-4 h-4 text-rose-600 border-gray-300 focus:ring-rose-500">
                        <label :for="'cancel-' + index" class="ml-2 text-sm text-gray-900 cursor-pointer">{{ option }}</label>
                    </div>
                </div>

                <div v-if="selectedReason === 'Other'" class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason Description</label>
                    <textarea v-model="customReason" rows="2" class="w-full border-gray-300 rounded-lg text-sm focus:ring-rose-500" placeholder="Explain why..."></textarea>
                    <p v-if="form.errors.reason" class="text-red-500 text-xs mt-1">{{ form.errors.reason }}</p>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button @click="$emit('close')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-bold">Close</button>
                    <button @click="submitCancel" :disabled="form.processing" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-bold disabled:opacity-50">
                        {{ form.processing ? 'Processing...' : 'Confirm Cancel' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>