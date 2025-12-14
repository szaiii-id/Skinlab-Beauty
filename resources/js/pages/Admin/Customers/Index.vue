<script setup>
import { ref, watch, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import axios from 'axios'; 
import { 
    Search, Filter, Gift, Mail, User, 
    ChevronDown, AlertCircle, Loader2, 
    Crown, Bed, ShoppingBag, TrendingUp, 
    ShieldAlert, ShieldCheck, AlertTriangle, CheckCircle2
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

// IMPORT THE SEPARATED COMPONENT
import GiftVoucherModal from '@/components/GiftVoucherModal.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    users: Object,
    filters: Object,
    giftRewards: Array,
    currentAdmin: Object
});

// State
const search = ref(props.filters?.search || '');
const filter = ref(props.filters?.filter ?? 'all'); 

const selectedUsers = ref(new Set());
const isSelectAllPage = ref(false);
const searchDebounce = ref(null);

// Gift Modal State (Boolean only, logic is in component)
const showGiftModal = ref(false);

// Ban & Unban State
const showBanModal = ref(false);
const isSubmittingBan = ref(false);
const isSubmittingUnban = ref(false);
const banReason = ref('return_abuse');
const banDescription = ref('');
const banEvidence = ref('');

// Computed Filters
const filterInfo = computed(() => {
    const infos = {
        all: { label: 'All Customers', icon: TrendingUp, desc: 'Active customers sorted by last activity' },
        sleeping_beauty: { label: 'Sleeping Beauty', icon: Bed, desc: 'No order in 90+ days' },
        loyal_queen: { label: 'Loyal Queen', icon: Crown, desc: 'Spent ≥ 2M OR 5+ orders' },
        first_time_buyers: { label: 'First Time Buyers', icon: ShoppingBag, desc: 'First purchase within 30 days' },
        banned: { label: 'Banned Users', icon: ShieldAlert, desc: 'Users blocked from access' }
    };
    return infos[filter.value] || infos.all;
});

const isSuperAdmin = computed(() => {
    return props.currentAdmin?.is_super_admin || props.currentAdmin?.role === 'super_admin' || false;
});

const adminRole = computed(() => props.currentAdmin?.role || 'marketing');

const canRequestBan = computed(() => props.currentAdmin?.can_request_ban || props.currentAdmin?.is_active || true);

// Formatter
const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(val) || 0);

const formatDate = (dateString) => {
    if (!dateString) return 'Never';
    const date = new Date(dateString);
    const now = new Date();
    const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24));
    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Yesterday';
    if (diffDays < 7) return `${diffDays} days ago`;
    if (diffDays < 30) return `${Math.floor(diffDays/7)} weeks ago`;
    return date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
};

// Watchers
watch([search, filter], () => {
    clearTimeout(searchDebounce.value);
    searchDebounce.value = setTimeout(() => {
        router.get(route('admin.customers.index'), { search: search.value, filter: filter.value }, {
            preserveState: true, replace: true,
            onBefore: () => { selectedUsers.value.clear(); isSelectAllPage.value = false; }
        });
    }, 300);
});

// Selection Logic
const toggleSelectUser = (id) => {
    if (selectedUsers.value.has(id)) selectedUsers.value.delete(id);
    else selectedUsers.value.add(id);
    updateSelectAllState();
};

const toggleSelectAllPage = () => {
    if (isSelectAllPage.value) selectedUsers.value.clear();
    else props.users.data.forEach(u => selectedUsers.value.add(u.id));
    isSelectAllPage.value = !isSelectAllPage.value;
};

const updateSelectAllState = () => {
    if (props.users.data.length === 0) { isSelectAllPage.value = false; return; }
    isSelectAllPage.value = props.users.data.every(u => selectedUsers.value.has(u.id));
};

// ============ BAN LOGIC ============
const openBanModal = () => {
    if (selectedUsers.value.size === 0) {
        Swal.fire({ icon: 'warning', title: 'No Users Selected', text: 'Please select at least one user.' });
        return;
    }
    banReason.value = 'return_abuse';
    banDescription.value = '';
    banEvidence.value = '';
    showBanModal.value = true;
};

const submitBanRequest = async () => {
    if (!banDescription.value.trim() || banDescription.value.trim().length < 5) {
        Swal.fire({ icon: 'warning', title: 'Description Required', text: 'Please provide a valid description (min 5 chars).' });
        return;
    }
    
    const actionText = isSuperAdmin.value ? 'Ban' : 'Request Ban for';
    const confirmResult = await Swal.fire({
        title: `${actionText} ${selectedUsers.value.size} Users?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: isSuperAdmin.value ? 'Yes, Ban Them!' : 'Yes, Submit Request',
    });
    
    if (confirmResult.isConfirmed) {
        isSubmittingBan.value = true;
        try {
            const response = await axios.post(route('admin.customers.request-ban'), {
                user_ids: Array.from(selectedUsers.value),
                reason: banReason.value,
                description: banDescription.value.trim(),
                evidence_notes: banEvidence.value.trim() || null
            });
            
            if (response.data.success) {
                showBanModal.value = false;
                selectedUsers.value.clear();
                isSelectAllPage.value = false;
                Swal.fire({ icon: 'success', title: 'Success!', text: response.data.message, confirmButtonColor: '#10b981' });
                router.reload({ only: ['users'] });
            }
        } catch (error) {
            let errorMsg = 'Something went wrong';
            if (error.response?.data?.message) errorMsg = error.response.data.message;
            Swal.fire({ icon: 'error', title: 'Failed', text: errorMsg });
        } finally {
            isSubmittingBan.value = false;
        }
    }
};

// ============ UNBAN LOGIC ============
const submitUnban = async () => {
    if (selectedUsers.value.size === 0) return;

    const result = await Swal.fire({
        title: `Reactivate ${selectedUsers.value.size} Users?`,
        text: "These users will be able to login and order again.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, Reactivate!'
    });

    if (result.isConfirmed) {
        isSubmittingUnban.value = true;
        try {
            router.post(route('admin.customers.unban'), {
                user_ids: Array.from(selectedUsers.value)
            }, {
                onSuccess: () => {
                    selectedUsers.value.clear();
                    isSelectAllPage.value = false;
                    Swal.fire('Reactivated!', 'Users have been unbanned.', 'success');
                },
                onError: (err) => {
                    Swal.fire('Error', err.message || 'Failed to unban users.', 'error');
                },
                onFinish: () => isSubmittingUnban.value = false
            });
        } catch (e) {
            isSubmittingUnban.value = false;
        }
    }
};

// ============ GIFT LOGIC (Using Component) ============
const openGiftModal = () => {
    if (selectedUsers.value.size === 0) {
        Swal.fire({ icon: 'warning', title: 'No Users Selected', text: 'Please select users.' });
        return;
    }
    showGiftModal.value = true;
};
</script>

<template>
    <Head title="Customer Management" />
    
    <div class="max-w-7xl mx-auto">
        
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-6 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold bg-gradient-to-r from-pink-600 to-rose-600 text-transparent bg-clip-text tracking-tight">
                    Customer Management
                </h1>
                <div class="flex items-center gap-2 mt-2">
                    <component :is="filterInfo.icon" class="w-4 h-4 text-gray-500" />
                    <span class="text-sm font-medium text-gray-700">{{ filterInfo.label }}</span>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                        {{ users.total }} users
                    </span>
                    <span class="hidden sm:inline text-xs text-gray-500 font-medium">• {{ filterInfo.desc }}</span>
                    
                    <span v-if="currentAdmin" class="text-xs font-bold px-2 py-0.5 rounded-full ml-2 border"
                        :class="{
                            'bg-blue-50 text-blue-700 border-blue-100': adminRole === 'marketing',
                            'bg-green-50 text-green-700 border-green-100': adminRole === 'warehouse',
                            'bg-purple-50 text-purple-700 border-purple-100': adminRole === 'super_admin'
                        }">
                        <span v-if="isSuperAdmin">👑 </span>
                        {{ adminRole.replace('_', ' ') }}
                    </span>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full xl:w-auto">
                
                <div v-if="selectedUsers.size > 0" class="w-full sm:w-auto flex flex-wrap items-center gap-2 animate-in fade-in slide-in-from-bottom-2 bg-white p-1.5 rounded-xl border border-rose-100 shadow-lg shadow-rose-50/50">
                    
                    <span class="text-xs font-bold text-rose-700 px-3 whitespace-nowrap">{{ selectedUsers.size }} Selected</span>
                    <div class="h-5 w-px bg-rose-200 mx-1"></div>

                    <button 
                        v-if="isSuperAdmin && filter === 'banned'"
                        @click="submitUnban"
                        :disabled="isSubmittingUnban"
                        class="h-9 px-3 bg-emerald-50 border border-emerald-200 text-emerald-600 font-bold rounded-lg hover:bg-emerald-100 transition-all flex items-center gap-1.5 text-xs shadow-sm"
                    >
                        <CheckCircle2 class="w-3.5 h-3.5" />
                        {{ isSubmittingUnban ? 'Processing...' : 'Reactivate' }}
                    </button>

                    <button 
                        v-if="canRequestBan && filter !== 'banned'"
                        @click="openBanModal"
                        class="h-9 px-3 bg-white border border-red-200 text-red-600 font-bold rounded-lg hover:bg-red-50 transition-all flex items-center gap-1.5 text-xs shadow-sm"
                    >
                        <AlertTriangle class="w-3.5 h-3.5" />
                        {{ isSuperAdmin ? 'Quick Ban' : 'Request Ban' }}
                    </button>
                    
                    <button 
                        @click="openGiftModal"
                        class="h-9 px-4 bg-gradient-to-r from-rose-500 to-pink-600 text-white font-bold rounded-lg shadow-sm hover:shadow-md hover:scale-105 transition-all flex items-center gap-1.5 text-xs"
                    >
                        <Gift class="w-3.5 h-3.5" /> Send Gift
                    </button>
                </div>
                
                <div v-else class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    
                    <div class="relative group w-full sm:w-auto">
                        <div class="absolute left-3.5 top-1/2 -translate-y-1/2 z-10 pointer-events-none text-gray-500">
                            <Filter class="w-4 h-4" />
                        </div>
                        
                        <select 
                            v-model="filter" 
                            class="h-11 w-full sm:w-48 pl-10 pr-8 bg-white border border-gray-200 rounded-xl text-sm font-semibold text-gray-700 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 shadow-sm cursor-pointer appearance-none hover:border-gray-300 transition-all"
                        >
                            <option value="all">Filter: All Users</option>
                            <option value="sleeping_beauty">😴 Sleeping (>90d)</option>
                            <option value="loyal_queen">👑 Loyal Queens</option>
                            <option value="first_time_buyers">🌱 New Buyers</option>
                            <option value="banned">🚫 Banned Users</option>
                        </select>
                        
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 z-10 pointer-events-none text-gray-400">
                            <ChevronDown class="w-4 h-4" />
                        </div>
                    </div>
                    
                    <div class="relative group w-full sm:w-auto">
                        <div class="absolute left-3.5 top-1/2 -translate-y-1/2 z-10 pointer-events-none text-gray-500">
                            <Search class="w-4 h-4" />
                        </div>
                        <input 
                            v-model="search" 
                            type="text" 
                            placeholder="Search name/email..." 
                            class="h-11 w-full sm:w-64 pl-10 pr-4 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-rose-500/20 focus:border-rose-500 shadow-sm transition-all"
                        >
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-2xl border border-gray-100 shadow-xl shadow-gray-100/50 overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/80">
                    <tr>
                        <th class="px-6 py-4 text-left w-10">
                            <input 
                                type="checkbox" 
                                :checked="isSelectAllPage" 
                                @change="toggleSelectAllPage" 
                                class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer"
                                :disabled="filter === 'banned'"
                            >
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                            Customer Profile
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                            Activity & Orders
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                            Total Spend
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-extrabold text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr 
                        v-for="user in users.data" 
                        :key="user.id" 
                        class="transition-colors"
                        :class="user.is_banned ? 'bg-red-50/30 hover:bg-red-100/30' : 'hover:bg-rose-50/40'"
                    >
                        <td class="px-6 py-4">
                            <input 
                                type="checkbox" 
                                :checked="selectedUsers.has(user.id)" 
                                @change="toggleSelectUser(user.id)" 
                                class="rounded border-gray-300 text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer"
                                :disabled="user.is_banned && filter !== 'banned'"
                            >
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div 
                                    class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold border shadow-sm"
                                    :class="user.is_banned ? 
                                        'bg-red-100 text-red-600 border-red-200' : 
                                        'bg-gradient-to-br from-rose-100 to-pink-100 text-rose-600 border-rose-200'"
                                >
                                    {{ user.name?.charAt(0)?.toUpperCase() || 'U' }}
                                </div>
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm font-extrabold truncate max-w-[150px]" :title="user.name" :class="user.is_banned ? 'text-red-700' : 'text-gray-900'">
                                            {{ user.name }}
                                        </p>
                                        <span v-if="user.is_banned" 
                                              class="text-[10px] font-extrabold px-2 py-0.5 bg-red-50 text-red-600 border border-red-100 rounded-full flex items-center gap-1 uppercase tracking-wider">
                                            <ShieldAlert class="w-3 h-3" /> BANNED
                                        </span>
                                        <span v-else class="text-[10px] font-extrabold px-2 py-0.5 bg-emerald-50 text-emerald-600 border border-emerald-100 rounded-full flex items-center gap-1 uppercase tracking-wider">
                                            ACTIVE
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 flex items-center gap-1 font-medium truncate" :title="user.email">
                                        <Mail class="w-3 h-3 text-gray-400" /> 
                                        {{ user.email }}
                                    </p>
                                    <p class="text-[11px] text-gray-400 font-medium">
                                        Joined {{ formatDate(user.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4 text-sm">
                                <div class="text-center">
                                    <span class="block font-bold" :class="user.is_banned ? 'text-gray-500' : 'text-gray-900'">
                                        {{ user.orders_count || 0 }}
                                    </span>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Total</span>
                                </div>
                                <div class="h-8 w-px bg-gray-100"></div>
                                <div class="text-center">
                                    <span class="block font-bold text-emerald-500">{{ user.completed_orders_count || 0 }}</span>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Completed</span>
                                </div>
                                <div class="h-8 w-px bg-gray-100"></div>
                                <div class="text-center">
                                    <span class="block font-bold text-amber-500">{{ user.current_points || 0 }}</span>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase">Points</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span 
                                class="text-sm font-bold px-3 py-1.5 rounded-lg border inline-block shadow-sm"
                                :class="{
                                    'text-emerald-700 bg-emerald-50 border-emerald-100': (user.orders_sum_total_amount || 0) < 1000000,
                                    'text-amber-700 bg-amber-50 border-amber-100': (user.orders_sum_total_amount || 0) >= 1000000 && (user.orders_sum_total_amount || 0) < 2000000,
                                    'text-rose-700 bg-rose-50 border-rose-100': (user.orders_sum_total_amount || 0) >= 2000000,
                                    'opacity-60': user.is_banned
                                }"
                            >
                                {{ formatCurrency(user.orders_sum_total_amount) }}
                                <span v-if="(user.orders_sum_total_amount || 0) >= 2000000 && !user.is_banned" class="ml-1">👑</span>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div v-if="user.is_banned" class="text-sm">
                                <div class="flex items-center gap-2 text-red-600 font-bold">
                                    <ShieldAlert class="w-4 h-4" /> Banned
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ user.ban_reason ? `Reason: ${user.ban_reason}` : 'No reason provided' }}
                                </p>
                                <p class="text-xs text-gray-400 mt-0.5">
                                    Since {{ formatDate(user.banned_at) }}
                                </p>
                            </div>
                            <div v-else class="text-sm">
                                <div class="flex items-center gap-2 text-emerald-700 font-bold">
                                    <ShieldCheck class="w-4 h-4" /> Active
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    Last activity: {{ formatDate(user.last_order_date) || 'Never ordered' }}
                                </p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <div v-if="users.data.length === 0" class="text-center py-12">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <User class="w-8 h-8 text-gray-400" />
                </div>
                <h3 class="text-lg font-bold text-gray-700 mb-2">No customers found</h3>
                <p class="text-sm text-gray-500 max-w-sm mx-auto">
                    {{ search || filter !== 'all' ? 'Try changing your search or filter criteria.' : 'No customers registered yet.' }}
                </p>
            </div>
            
            <div v-if="users.links && users.links.length > 3" class="px-6 py-4 border-t border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Showing {{ users.from }} to {{ users.to }} of {{ users.total }} results
                    </div>
                    <div class="flex gap-1">
                        <a 
                            v-for="(link, index) in users.links" 
                            :key="index"
                            :href="link.url"
                            @click.prevent="link.url && router.visit(link.url)"
                            class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all"
                            :class="{
                                'bg-rose-500 text-white shadow-sm': link.active,
                                'text-gray-700 hover:bg-gray-100': !link.active && link.url,
                                'text-gray-400 cursor-not-allowed': !link.url
                            }"
                            v-html="link.label"
                        ></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div 
        v-if="showBanModal" 
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
    >
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl p-6 animate-in fade-in zoom-in">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                        <AlertTriangle class="w-5 h-5 text-red-600" />
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">
                            {{ isSuperAdmin ? 'Quick Ban Users' : 'Submit Ban Request' }}
                        </h3>
                        <p class="text-sm text-gray-500">
                            {{ isSuperAdmin ? 'Users will be banned immediately.' : 'Requires super admin approval' }}
                        </p>
                    </div>
                </div>
                <button @click="showBanModal = false" class="text-gray-400 hover:text-gray-600">
                    ✕
                </button>
            </div>
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Ban Reason *
                    </label>
                    <select 
                        v-model="banReason"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                    >
                        <option value="return_abuse">Return Abuse (≥3 invalid returns)</option>
                        <option value="fraud">Fraud / Fake Claims</option>
                        <option value="toxic_behavior">Toxic Behavior</option>
                        <option value="payment_issue">Payment Issue</option>
                        <option value="policy_violation">Policy Violation</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Description *
                    </label>
                    <textarea 
                        v-model="banDescription"
                        rows="4"
                        :placeholder="isSuperAdmin 
                            ? 'Provide reason for immediate ban...' 
                            : 'Provide detailed explanation for the ban request. Include specific incidents, dates, order numbers if available...'"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500 placeholder-gray-400"
                    ></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        Minimum 5 characters. {{ isSuperAdmin ? '' : 'This will be reviewed by super admin.' }}
                    </p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Evidence / Notes (Optional)
                    </label>
                    <textarea 
                        v-model="banEvidence"
                        rows="2"
                        placeholder="Screenshot references, chat logs, order IDs, etc..."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-red-500 focus:border-red-500 placeholder-gray-400"
                    ></textarea>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                    <p class="text-sm font-medium text-gray-700 mb-2">
                        Users to {{ isSuperAdmin ? 'ban' : 'request ban for' }} ({{ selectedUsers.size }})
                    </p>
                    <div class="text-xs text-gray-600 space-y-1 max-h-32 overflow-y-auto">
                        <div v-for="userId in Array.from(selectedUsers)" :key="userId" 
                             class="flex items-center justify-between py-1 px-2 hover:bg-gray-100 rounded">
                            <span>User ID: {{ userId }}</span>
                            <button @click="selectedUsers.delete(userId)" 
                                    class="text-red-500 hover:text-red-700 text-xs font-medium">
                                Remove
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3" v-if="!isSuperAdmin">
                    <p class="text-sm text-yellow-800 font-medium">
                        ⚠️ Note: Ban requests require approval from super admin.
                        Users will only be banned after review.
                    </p>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button 
                    @click="showBanModal = false"
                    class="flex-1 py-2.5 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition-colors"
                    :disabled="isSubmittingBan"
                >
                    Cancel
                </button>
                <button 
                    @click="submitBanRequest"
                    :disabled="isSubmittingBan || !banDescription.trim() || banDescription.trim().length < 5"
                    class="flex-1 py-2.5 bg-gradient-to-r from-red-500 to-rose-600 text-white rounded-lg font-medium hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                    <Loader2 v-if="isSubmittingBan" class="w-4 h-4 animate-spin" />
                    {{ isSubmittingBan ? 'Processing...' : (isSuperAdmin ? 'Ban Now' : 'Submit Request') }}
                </button>
            </div>
        </div>
    </div>

    <GiftVoucherModal 
        :show="showGiftModal"
        :users="selectedUsers"
        :rewards="giftRewards" 
        :submit-url="route('admin.customers.send-gift')" 
        @close="showGiftModal = false"
        @success="() => { selectedUsers.clear(); isSelectAllPage = false; }"
    />
</template>

<style scoped>
.animate-in {
    animation: animateIn 0.2s ease-out;
}

@keyframes animateIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-in {
    animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.zoom-in {
    animation: zoomIn 0.3s ease-out;
}

@keyframes zoomIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.slide-in-from-bottom-2 {
    animation: slideInFromBottom 0.3s ease-out;
}

@keyframes slideInFromBottom {
    from {
        transform: translateY(10px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}
</style>