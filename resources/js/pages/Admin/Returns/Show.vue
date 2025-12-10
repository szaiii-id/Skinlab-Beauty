<script setup>
import { ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import Swal from 'sweetalert2';
import { 
    ArrowLeft, Package, User, CheckCircle, XCircle, 
    Video, Image as ImageIcon, Download, FileText, Info 
} from 'lucide-vue-next';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    returnRequest: Object
});

const isProcessing = ref(false);

// Helper: Status Color
const getStatusColor = (status) => {
    switch(status) {
        case 'pending': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'approved': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'rejected': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800';
    }
};

// Helper: Format Date
const formatDate = (date) => new Date(date).toLocaleDateString('en-GB', { 
    day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute:'2-digit' 
});

// Helper: Detect File Type
const isVideo = (path) => {
    if (!path) return false;
    const ext = path.split('.').pop().toLowerCase();
    return ['mp4', 'mov', 'avi', 'webm'].includes(ext);
};

// Action Handler (Approve/Reject) with Manual Restock Checkbox
const handleAction = (action) => {
    const isApprove = action === 'approve';
    
    // HTML for Approve (With Restock Checkbox)
    const approveHtml = `
        <div class="text-left">
            <p class="mb-4 text-sm text-gray-600">
                Please verify the warehouse inspection result.
            </p>
            
            <div class="flex items-center gap-3 mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-emerald-300 transition-colors">
                <input type="checkbox" id="swal-restock" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500 border-gray-300 cursor-pointer">
                <label for="swal-restock" class="text-sm font-bold text-gray-800 cursor-pointer select-none flex-1">
                    Return to Stock?
                    <span class="block text-xs font-normal text-gray-500 mt-0.5">Check this ONLY if item is in good condition & resellable.</span>
                </label>
            </div>

            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1 block">Admin Note (Optional)</label>
            <textarea id="swal-note" class="w-full border-gray-300 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500 p-2.5" rows="2" placeholder="e.g. Item sealed, good condition..."></textarea>
        </div>
    `;

    // HTML for Reject
    const rejectHtml = `
        <div class="text-left">
            <p class="mb-3 text-sm text-red-600 font-medium bg-red-50 p-2 rounded">
                This action will deny the refund/exchange and complete the order.
            </p>
            <label class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-1 block">Rejection Reason (Required)</label>
            <textarea id="swal-note" class="w-full border-gray-300 rounded-lg text-sm focus:ring-red-500 focus:border-red-500 p-2.5" rows="3" placeholder="Explain why it's rejected (e.g. No video proof)..."></textarea>
        </div>
    `;

    Swal.fire({
        title: isApprove ? 'Approve Return Request' : 'Reject Return Request',
        html: isApprove ? approveHtml : rejectHtml,
        icon: isApprove ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: isApprove ? '#10b981' : '#ef4444',
        confirmButtonText: isApprove ? 'Confirm Approval' : 'Confirm Rejection',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusConfirm: false,
        preConfirm: () => {
            const note = document.getElementById('swal-note').value;
            
            // Validate Note for Rejection
            if (!isApprove && !note) {
                Swal.showValidationMessage('Please provide a reason for rejection.');
                return false;
            }

            return {
                note: note,
                restock: document.getElementById('swal-restock') 
                    ? document.getElementById('swal-restock').checked 
                    : false
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const data = result.value;
            isProcessing.value = true;
            
            router.put(route('admin.returns.update', props.returnRequest.id), {
                action: action,
                admin_note: data.note,
                restock: data.restock // Send checkbox status
            }, {
                onFinish: () => isProcessing.value = false
            });
        }
    });
};
</script>

<template>
    <Head title="Return Detail" />

    <div class="max-w-6xl mx-auto">
        <div class="flex items-center gap-3 mb-6">
            <Link :href="route('admin.returns.index')" class="p-2 rounded-full bg-white border border-gray-200 text-gray-500 hover:text-orange-600 hover:border-orange-200 transition-all shadow-sm">
                <ArrowLeft class="w-5 h-5" />
            </Link>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-900">Request #{{ returnRequest.id }}</h1>
                    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase border tracking-wide" :class="getStatusColor(returnRequest.status)">
                        {{ returnRequest.status }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-0.5">Submitted on {{ formatDate(returnRequest.created_at) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 space-y-6">
                
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2 text-lg">
                        <ImageIcon v-if="!isVideo(returnRequest.evidence_file)" class="w-5 h-5 text-orange-600" />
                        <Video v-else class="w-5 h-5 text-orange-600" />
                        Evidence Proof
                    </h3>
                    
                    <div class="bg-gray-50 rounded-xl border border-gray-200 overflow-hidden flex items-center justify-center min-h-[300px] relative group">
                        <div v-if="isVideo(returnRequest.evidence_file)" class="w-full">
                            <video controls class="w-full max-h-[500px] rounded-lg">
                                <source :src="`/storage/${returnRequest.evidence_file}`" type="video/mp4">
                                Your browser does not support the video tag.
                            </video>
                        </div>
                        
                        <div v-else class="w-full p-2">
                            <img :src="`/storage/${returnRequest.evidence_file}`" class="w-full h-auto object-contain max-h-[500px] rounded-lg" />
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-end">
                        <a :href="`/storage/${returnRequest.evidence_file}`" download target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-bold hover:bg-gray-200 transition-colors">
                            <Download class="w-4 h-4" /> Download Original
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2 text-lg">
                        <FileText class="w-5 h-5 text-blue-600" />
                        Details
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="bg-orange-50 p-4 rounded-xl border border-orange-100">
                            <span class="text-xs font-bold text-orange-600 uppercase tracking-wider">Reason Category</span>
                            <p class="font-bold text-gray-900 mt-1">{{ returnRequest.reason }}</p>
                        </div>
                        <div class="bg-blue-50 p-4 rounded-xl border border-blue-100">
                            <span class="text-xs font-bold text-blue-600 uppercase tracking-wider">Requested Solution</span>
                            <p class="font-bold text-gray-900 mt-1 uppercase">{{ returnRequest.solution }}</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">Customer Description</span>
                        <p class="text-gray-800 mt-2 leading-relaxed whitespace-pre-wrap font-medium">{{ returnRequest.description }}</p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                
                <div v-if="returnRequest.status === 'pending'" class="bg-white rounded-2xl border border-orange-200 shadow-xl shadow-orange-100/50 p-6 sticky top-6">
                    <div class="flex items-center gap-2 mb-3">
                        <Info class="w-5 h-5 text-orange-500" />
                        <h3 class="font-bold text-gray-900 text-lg">Take Action</h3>
                    </div>
                    <p class="text-sm text-gray-500 mb-6 border-l-4 border-orange-200 pl-3">
                        Please verify the evidence before making a decision. Once processed, it cannot be undone.
                    </p>
                    
                    <div class="space-y-3">
                        <button 
                            @click="handleAction('approve')" 
                            :disabled="isProcessing"
                            class="w-full py-3.5 bg-emerald-600 text-white font-bold rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-200 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                        >
                            <CheckCircle class="w-5 h-5" /> Approve Request
                        </button>
                        
                        <button 
                            @click="handleAction('reject')" 
                            :disabled="isProcessing"
                            class="w-full py-3.5 bg-white border-2 border-red-100 text-red-600 font-bold rounded-xl hover:bg-red-50 hover:border-red-200 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                        >
                            <XCircle class="w-5 h-5" /> Reject Request
                        </button>
                    </div>
                </div>

                <div v-else class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-bold text-gray-900 mb-3 text-lg">Admin Decision</h3>
                    <div class="p-4 rounded-xl text-sm border" 
                        :class="returnRequest.status === 'approved' ? 'bg-emerald-50 text-emerald-800 border-emerald-100' : 'bg-red-50 text-red-800 border-red-100'">
                        <div class="flex justify-between items-center mb-2">
                            <p class="font-bold flex items-center gap-2">
                                <span v-if="returnRequest.status === 'approved'">✅ Approved</span>
                                <span v-else>❌ Rejected</span>
                            </p>
                            <span v-if="returnRequest.status === 'approved'" 
                                class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border bg-white"
                                :class="returnRequest.is_restocked ? 'text-emerald-600 border-emerald-200' : 'text-orange-600 border-orange-200'">
                                {{ returnRequest.is_restocked ? 'Restocked' : 'Written Off' }}
                            </span>
                        </div>
                        
                        <hr class="border-black/10 my-2">
                        
                        <p class="text-xs font-bold uppercase opacity-60 mb-1">Admin Note:</p>
                        <p v-if="returnRequest.admin_note" class="italic">"{{ returnRequest.admin_note }}"</p>
                        <p v-else class="italic opacity-70">No note provided.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <Package class="w-5 h-5 text-gray-400" />
                        Order Summary
                    </h3>
                    
                    <div class="flex items-center gap-3 mb-5 pb-5 border-b border-gray-100">
                        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 font-bold border border-gray-200">
                            <User class="w-6 h-6" />
                        </div>
                        <div class="overflow-hidden">
                            <p class="text-sm font-bold text-gray-900 truncate">{{ returnRequest.order.user.name }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ returnRequest.order.user.email }}</p>
                        </div>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Order ID</span>
                            <Link :href="route('admin.orders.show', returnRequest.order_id)" class="font-mono font-bold text-orange-600 hover:underline">
                                #{{ returnRequest.order.order_number }}
                            </Link>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Total Amount</span>
                            <span class="font-bold text-gray-900">Rp {{ returnRequest.order.total_amount.toLocaleString('id-ID') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Payment</span>
                            <span class="font-medium text-gray-700 capitalize">{{ returnRequest.order.payment_method }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>