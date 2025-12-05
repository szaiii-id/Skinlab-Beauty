<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import axios from 'axios'; 
import { 
    AlertTriangle, CheckCircle2, XCircle, Clock, 
    Search, Filter, Eye, User, ShieldAlert, 
    FileText, Calendar, ChevronRight, MessageSquare, Loader2
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    requests: Object,
    filters: Object,
    currentAdmin: Object
});

// State
const activeTab = ref(props.filters.status || 'pending');
const showRejectModal = ref(false);
const showDetailModal = ref(false);
const selectedRequest = ref(null);
const rejectReason = ref('');
const isProcessing = ref(false);

// Formatters
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: 'numeric', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
};

const getReasonLabel = (reason) => {
    const labels = {
        'return_abuse': 'Return Abuse',
        'fraud': 'Fraud / Scam',
        'toxic_behavior': 'Toxic Behavior',
        'payment_issue': 'Payment Issue',
        'policy_violation': 'Policy Violation',
        'other': 'Other'
    };
    return labels[reason] || reason;
};

// Filter Logic
const tabs = [
    { id: 'pending', label: 'Pending Review', icon: Clock },
    { id: 'approved', label: 'Approved (Banned)', icon: CheckCircle2 },
    { id: 'rejected', label: 'Rejected', icon: XCircle },
];

const changeTab = (tabId) => {
    activeTab.value = tabId;
    router.get(route('admin.ban-requests.index'), { status: tabId }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

// Actions
const openDetail = (req) => {
    selectedRequest.value = req;
    showDetailModal.value = true;
};

// --- FUNGSI APPROVE ---
const approveRequest = async (req) => {
    const result = await Swal.fire({
        title: 'Approve & Ban User?',
        html: `
            <div class="text-left">
                <p class="text-gray-900 font-medium mb-2">You are about to ban <strong>${req.user?.name}</strong>.</p>
                <p class="text-sm text-red-600 font-bold bg-red-50 p-2 rounded border border-red-100">
                    ⚠️ This user will be blocked immediately.
                </p>
            </div>
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Ban User',
    });

    if (result.isConfirmed) {
        isProcessing.value = true;
        try {
            await axios.post(route('admin.ban-requests.approve', req.id));
            showDetailModal.value = false;
            
            Swal.fire({
                title: 'Banned!',
                text: 'User has been banned successfully.',
                icon: 'success',
                confirmButtonColor: '#10b981'
            });
            
            router.reload({ only: ['requests'] });
        } catch (error) {
            console.error(error);
            Swal.fire('Error', 'Failed to approve request.', 'error');
        } finally {
            isProcessing.value = false;
        }
    }
};

// --- FUNGSI REJECT ---
const openRejectModal = (req) => {
    selectedRequest.value = req;
    rejectReason.value = '';
    showRejectModal.value = true;
};

const submitReject = async () => {
    if (!rejectReason.value.trim()) return;

    isProcessing.value = true;
    try {
        await axios.post(route('admin.ban-requests.reject', selectedRequest.value.id), {
            notes: rejectReason.value
        });
        showRejectModal.value = false;
        showDetailModal.value = false;
        Swal.fire('Rejected', 'The ban request has been rejected.', 'success');
        router.reload({ only: ['requests'] });
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'Failed to reject request.', 'error');
    } finally {
        isProcessing.value = false;
    }
};
</script>

<template>
    <Head title="Ban Requests" />

    <div class="max-w-7xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight flex items-center gap-3">
                <ShieldAlert class="w-8 h-8 text-rose-600" />
                Ban Requests Management
            </h1>
            <p class="text-gray-500 mt-2">
                Review and manage user ban requests submitted by admins.
            </p>
        </div>

        <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-100 pb-1">
            <button 
                v-for="tab in tabs" 
                :key="tab.id"
                @click="changeTab(tab.id)"
                class="px-4 py-2.5 rounded-t-lg font-medium text-sm flex items-center gap-2 transition-all relative top-px"
                :class="activeTab === tab.id 
                    ? 'text-rose-600 bg-white border-x border-t border-gray-100 shadow-sm border-b-white z-10' 
                    : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50 border-transparent'"
            >
                <component :is="tab.icon" class="w-4 h-4" />
                {{ tab.label }}
            </button>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-xl shadow-gray-100/50 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Target User</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Reason & Description</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Requested By</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="req in requests.data" :key="req.id" class="hover:bg-rose-50/30 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center text-rose-600 font-bold border border-rose-200">
                                    {{ req.user?.name?.charAt(0) || '?' }}
                                </div>
                                <div>
                                    <div class="font-bold text-gray-900 group-hover:text-rose-700 transition-colors">
                                        {{ req.user?.name || 'Unknown User' }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ req.user?.email }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 max-w-xs">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border mb-1 uppercase tracking-wider"
                                :class="{
                                    'bg-red-50 text-red-700 border-red-100': req.reason === 'fraud',
                                    'bg-orange-50 text-orange-700 border-orange-100': req.reason === 'return_abuse',
                                    'bg-gray-50 text-gray-700 border-gray-100': !['fraud', 'return_abuse'].includes(req.reason)
                                }">
                                {{ getReasonLabel(req.reason) }}
                            </span>
                            <p class="text-sm text-gray-600 line-clamp-2 mt-1" :title="req.description">
                                {{ req.description }}
                            </p>
                        </td>

                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-gray-900">{{ req.requester?.name }}</div>
                            <div class="text-xs text-gray-500 flex items-center gap-1 mt-0.5">
                                <span class="bg-gray-100 px-1.5 py-0.5 rounded text-[10px] uppercase font-bold text-gray-600">
                                    {{ req.requester?.role }}
                                </span>
                                <span>• {{ formatDate(req.created_at) }}</span>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <span v-if="req.status === 'pending'" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-yellow-100 text-yellow-700">
                                <Clock class="w-3 h-3" /> Pending
                            </span>
                            <span v-else-if="req.status === 'approved'" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">
                                <CheckCircle2 class="w-3 h-3" /> Approved
                            </span>
                            <span v-else class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600">
                                <XCircle class="w-3 h-3" /> Rejected
                            </span>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <button 
                                @click="openDetail(req)" 
                                class="inline-flex items-center justify-center gap-2 px-3 py-1.5 text-xs font-bold text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm group/btn"
                                title="Review Request"
                            >
                                <FileText class="w-3.5 h-3.5 text-gray-500 group-hover/btn:text-rose-600 transition-colors" />
                                Review
                            </button>
                        </td>
                    </tr>
                    
                    <tr v-if="requests.data.length === 0">
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                <Filter class="w-8 h-8 text-gray-300" />
                            </div>
                            <p>No requests found in this status.</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div v-if="requests.links.length > 3" class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                <div class="flex justify-end gap-1">
                    <Link v-for="(link, k) in requests.links" :key="k" 
                        :href="link.url || '#'" 
                        class="px-3 py-1 rounded text-sm"
                        :class="link.active ? 'bg-rose-600 text-white font-bold' : 'text-gray-500 hover:bg-white'"
                        v-html="link.label" 
                    />
                </div>
            </div>
        </div>
    </div>

    <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 animate-in fade-in">
        <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="text-lg font-bold text-gray-900">Request Details #{{ selectedRequest.id }}</h3>
                <button @click="showDetailModal = false" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>

            <div class="p-6 overflow-y-auto max-h-[70vh]">
                <div v-if="selectedRequest.status !== 'pending'" class="mb-6 p-4 rounded-xl border flex items-start gap-3"
                    :class="selectedRequest.status === 'approved' ? 'bg-red-50 border-red-100' : 'bg-gray-50 border-gray-200'">
                    <div class="mt-0.5">
                        <CheckCircle2 v-if="selectedRequest.status === 'approved'" class="w-5 h-5 text-red-600" />
                        <XCircle v-else class="w-5 h-5 text-gray-500" />
                    </div>
                    <div>
                        <h4 class="font-bold text-sm" :class="selectedRequest.status === 'approved' ? 'text-red-900' : 'text-gray-900'">
                            {{ selectedRequest.status === 'approved' ? 'Request Approved (User Banned)' : 'Request Rejected' }}
                        </h4>
                        
                        <p class="text-sm mt-1 text-gray-700">
                            Reviewed by <strong>{{ selectedRequest.reviewer?.name }}</strong> on {{ formatDate(selectedRequest.reviewed_at) }}
                        </p>
                        
                        <p v-if="selectedRequest.review_notes" class="text-sm mt-2 font-medium bg-white/60 p-3 rounded-lg border border-red-100 text-gray-900 whitespace-pre-wrap">
                            {{ selectedRequest.review_notes }}
                        </p>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Target User</label>
                            <div class="flex items-center gap-3 mt-1 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                <div class="w-10 h-10 rounded-full bg-rose-100 flex items-center justify-center border border-rose-200 text-sm font-bold text-rose-600">
                                    {{ selectedRequest.user?.name?.charAt(0) }}
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900">{{ selectedRequest.user?.name }}</p>
                                    <p class="text-xs text-gray-500">{{ selectedRequest.user?.email }}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Reason Category</label>
                            <p class="mt-1 text-sm font-medium text-gray-900">{{ getReasonLabel(selectedRequest.reason) }}</p>
                        </div>
                        
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Requested By</label>
                            <p class="mt-1 text-sm text-gray-900">
                                {{ selectedRequest.requester?.name }} <span class="text-gray-400">({{ selectedRequest.requester?.role }})</span>
                            </p>
                            <p class="text-xs text-gray-400">{{ formatDate(selectedRequest.created_at) }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-bold text-gray-400 uppercase">Description</label>
                            <div class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded-lg border border-gray-200 leading-relaxed font-medium">
                                {{ selectedRequest.description }}
                            </div>
                        </div>

                        <div v-if="selectedRequest.evidence">
                            <label class="text-xs font-bold text-gray-400 uppercase">Evidence / Notes</label>
                            <div class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded-lg border border-gray-200 whitespace-pre-wrap font-sans font-medium">
                                {{ selectedRequest.evidence?.notes || (typeof selectedRequest.evidence === 'string' ? selectedRequest.evidence : 'No notes provided.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="selectedRequest.status === 'pending' && currentAdmin.is_super_admin" class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50">
                <button @click="openRejectModal(selectedRequest)" class="px-4 py-2 text-sm font-bold text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors shadow-sm">
                    Reject Request
                </button>
                <button @click="approveRequest(selectedRequest)" class="px-4 py-2 text-sm font-bold text-white bg-gradient-to-r from-rose-600 to-pink-600 rounded-lg hover:shadow-lg hover:scale-105 transition-all shadow-md flex items-center gap-2">
                    <span v-if="isProcessing" class="flex items-center gap-2">
                        <Loader2 class="w-4 h-4 animate-spin" /> Processing...
                    </span>
                    <span v-else>Approve & Ban User</span>
                </button>
            </div>
            <div v-else class="px-6 py-4 border-t border-gray-100 flex justify-end bg-gray-50">
                <button 
                    @click="showDetailModal = false" 
                    class="px-5 py-2 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all shadow-sm"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    <div v-if="showRejectModal" class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-xl w-full max-w-md shadow-2xl animate-in zoom-in-95">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Reject Request</h3>
                <p class="text-sm text-gray-900 mb-4 font-medium">
                    Why are you rejecting this ban request for <strong>{{ selectedRequest?.user?.name }}</strong>?
                </p>
                
                <textarea 
                    v-model="rejectReason"
                    rows="3"
                    class="w-full rounded-lg border-gray-300 focus:border-rose-500 focus:ring-rose-500 text-sm text-gray-900 bg-white placeholder-gray-400"
                    placeholder="E.g. Insufficient evidence..."
                ></textarea>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button @click="showRejectModal = false" class="px-4 py-2 text-gray-500 hover:text-gray-700 font-medium">Cancel</button>
                    <button 
                        @click="submitReject" 
                        :disabled="!rejectReason || isProcessing"
                        class="px-4 py-2 bg-gray-800 text-white rounded-lg font-bold hover:bg-gray-900 disabled:opacity-50 flex items-center gap-2"
                    >
                        <span v-if="isProcessing">Processing...</span>
                        <span v-else>Confirm Rejection</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>