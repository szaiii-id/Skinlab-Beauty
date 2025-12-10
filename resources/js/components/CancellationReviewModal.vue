<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Swal from 'sweetalert2';
import { X, User, FileText, CheckCircle, XCircle, AlertTriangle, MessageSquare } from 'lucide-vue-next';

const props = defineProps({
    show: Boolean,
    order: Object 
});

const emit = defineEmits(['close']);

// 1. DEFINISIKAN 'reason' DI SINI AGAR BISA DIKIRIM
const form = useForm({
    admin_note: '',
    reason: '' // <--- WAJIB ADA
});

// Reset form saat modal dibuka
watch(() => props.show, (val) => {
    if (val) form.reset();
});

// 1. APPROVE
const approveCancellation = () => {
    Swal.fire({
        title: 'Approve Cancellation?',
        text: "Order will be cancelled and stock will be restored.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444', 
        confirmButtonText: 'Yes, Approve Cancel',
        cancelButtonText: 'Close',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            
            // 2. ISI REASON SECARA MANUAL SEBELUM POST
            form.reason = 'Approved by Admin (Cancellation Request)'; 

            // 3. KIRIM (Pastikan route pakai 'admin.')
            form.post(route('admin.orders.cancel', props.order.id), {
                preserveScroll: true,
                onSuccess: () => {
                    emit('close');
                    Swal.fire('Approved', 'Request approved successfully.', 'success');
                },
                onError: (errors) => {
                    // Debugging jika masih gagal
                    console.error("Error Approve:", errors);
                    Swal.fire('Failed', 'Validation error. Check console.', 'error');
                }
            });
        }
    });
};

// 2. REJECT
const rejectCancellation = () => {
    if (!form.admin_note || form.admin_note.trim().length < 5) {
        Swal.fire('Required', 'Please provide a valid rejection reason (min 5 chars).', 'error');
        return;
    }

    Swal.fire({
        title: 'Reject Request?',
        text: "Order status will return to 'Processing'.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6', 
        confirmButtonText: 'Yes, Reject & Continue',
        cancelButtonText: 'Close',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            form.post(route('admin.orders.reject-cancellation', props.order.id), {
                preserveScroll: true,
                onSuccess: () => {
                    emit('close');
                    Swal.fire('Rejected', 'Request rejected. Order continued.', 'success');
                }
            });
        }
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center px-4 sm:px-0">
        
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

        <div class="relative bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden transform transition-all animate-in zoom-in-95 flex flex-col max-h-[90vh]">
            
            <div class="px-6 py-5 border-b border-gray-100 flex justify-between items-center bg-orange-50/80 shrink-0">
                <h3 class="text-lg font-black text-orange-900 flex items-center gap-2">
                    <div class="p-1.5 bg-orange-100 rounded-lg">
                        <AlertTriangle class="w-5 h-5 text-orange-600" />
                    </div>
                    Review Cancel Request
                </h3>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 hover:bg-white p-2 rounded-full transition-all">
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="p-6 overflow-y-auto custom-scrollbar">
                
                <div class="flex items-center justify-between mb-6 p-4 bg-white rounded-xl border border-gray-200 shadow-sm">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Order Number</p>
                        <p class="text-base font-mono font-bold text-gray-900 mt-0.5">#{{ order?.order_number }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Customer</p>
                        <div class="flex items-center gap-1.5 justify-end mt-0.5">
                            <div class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center">
                                <User class="w-3 h-3 text-gray-500" />
                            </div>
                            <p class="text-sm font-bold text-gray-800">{{ order?.user?.name }}</p>
                        </div>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="flex items-center gap-2 text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                        <MessageSquare class="w-3 h-3" /> Reason for Cancellation
                    </label>
                    <div class="bg-orange-50 border border-orange-100 p-4 rounded-xl relative">
                        <span class="absolute top-2 right-3 text-orange-200 text-4xl font-serif leading-none">”</span>
                        <p class="text-sm text-orange-900 font-medium leading-relaxed relative z-10 italic">
                            {{ order?.cancellation?.reason || 'No specific reason provided.' }}
                        </p>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                        Admin Response / Rejection Note
                    </label>
                    <div class="relative">
                        <FileText class="absolute top-3.5 left-3.5 w-4 h-4 text-gray-400" />
                        <textarea 
                            v-model="form.admin_note" 
                            rows="3" 
                            class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 bg-gray-50 focus:bg-white focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 text-sm text-gray-900 placeholder-gray-400 transition-all resize-none shadow-sm"
                            placeholder="Explain why you are rejecting this request..."
                        ></textarea>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <p v-if="form.errors.admin_note" class="text-xs text-red-500 font-bold">{{ form.errors.admin_note }}</p>
                        <p v-else class="text-[10px] text-gray-400 ml-auto">* Required only if rejecting</p>
                    </div>
                </div>

            </div>

            <div class="px-6 py-5 bg-gray-50 border-t border-gray-100 flex gap-3 shrink-0">
                <button 
                    @click="rejectCancellation" 
                    :disabled="form.processing"
                    class="flex-1 py-3 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-100 hover:text-gray-900 hover:border-gray-400 transition-all flex items-center justify-center gap-2 shadow-sm disabled:opacity-50"
                >
                    <XCircle class="w-4 h-4 text-gray-500" /> Reject Request
                </button>

                <button 
                    @click="approveCancellation" 
                    :disabled="form.processing"
                    class="flex-1 py-3 bg-gradient-to-r from-red-600 to-rose-600 text-white font-bold rounded-xl hover:shadow-lg hover:shadow-red-200 hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                >
                    <span v-if="form.processing" class="animate-spin">⏳</span>
                    <CheckCircle v-else class="w-4 h-4" /> 
                    {{ form.processing ? 'Processing...' : 'Approve Cancel' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f9fafb;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>