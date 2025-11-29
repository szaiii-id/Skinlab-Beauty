<script setup>
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    show: Boolean,
    order: Object
});

const emit = defineEmits(['close', 'success']);

const form = useForm({
    reason: 'Damaged / Not Working',
    description: '',
    solution: 'refund',
    evidence: null,
});

const submitReturn = () => {
    if (!form.evidence) {
        Swal.fire('Missing Evidence', 'Please upload a photo/video proof.', 'warning');
        return;
    }

    form.post(`/orders/${props.order.id}/return`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            emit('close');
            form.reset();
            Swal.fire('Submitted!', 'Return request sent for approval.', 'success');
        }
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
            <div class="bg-orange-50 border-b border-orange-100 px-6 py-4">
                <h3 class="font-bold text-lg text-orange-800">Request Return / Refund</h3>
            </div>

            <div class="p-6 max-h-[80vh] overflow-y-auto">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                    <select v-model="form.reason" class="w-full border-gray-300 rounded-lg text-sm focus:ring-orange-500">
                        <option>Damaged / Not Working</option>
                        <option>Product Mismatch (Wrong Item)</option>
                        <option>Incomplete Package</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Solution</label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="refund" v-model="form.solution" class="text-orange-600 focus:ring-orange-500">
                            <span class="text-sm text-gray-900">Refund</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" value="exchange" v-model="form.solution" class="text-orange-600 focus:ring-orange-500">
                            <span class="text-sm text-gray-900">Exchange Item</span>
                        </label>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg border border-dashed border-gray-300 mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload Proof (Photo/Video)</label>
                    <input type="file" @input="form.evidence = $event.target.files[0]" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100"/>
                    <p class="text-xs text-gray-500 mt-2">Max: 50MB. Format: MP4, JPG, PNG.</p>
                    <div v-if="form.progress" class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-orange-600 h-2 rounded-full" :style="{ width: form.progress.percentage + '%' }"></div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button @click="$emit('close')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-bold">Cancel</button>
                    <button @click="submitReturn" :disabled="form.processing" class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm font-bold disabled:opacity-50">
                        {{ form.processing ? 'Uploading...' : 'Submit Request' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>