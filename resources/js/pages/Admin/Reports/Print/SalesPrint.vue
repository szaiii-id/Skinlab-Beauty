<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { 
    Printer, Download, ArrowLeft, CalendarDays, User, 
    Package, Users, Heart, ShieldAlert, AlertTriangle, TrendingUp, MapPin 
} from 'lucide-vue-next';

const props = defineProps({
    data: Object,
    filters: Object,
    user: Object,
    current_time: String
});

const activeTab = computed(() => props.filters.tab || 'sales');

// JUDUL DINAMIS SESUAI KONTEKS
const reportTitle = computed(() => {
    switch (activeTab.value) {
        case 'sales': return 'Sales Performance Report';
        case 'inventory': return 'Inventory Asset Valuation';
        case 'customers': return 'Customer Demographics';
        case 'loyalty': return 'Top Customer (Sultan) List';
        case 'operations': return 'Operational Loss Analysis';
        default: return 'General Report';
    }
});

// SUBJUDUL DINAMIS
const reportSubtitle = computed(() => {
    switch (activeTab.value) {
        case 'sales': return 'Revenue, Transaction Count, and Product Performance.';
        case 'inventory': return 'Real-time asset value, dead stock, and movements.';
        case 'customers': return 'Skin profile analysis and geographical distribution.';
        case 'loyalty': return 'High-value customer tracking for retention.';
        case 'operations': return 'Cancellation analysis and opportunity loss.';
        default: return 'Official record.';
    }
});

const formatIDR = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);
const formatDate = (dateString) => dateString ? new Date(dateString).toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '-';
const goBack = () => window.history.length > 1 ? window.history.back() : router.visit(route('admin.reports.index', { tab: activeTab.value }));
const printPage = () => window.print();

const downloadExcel = () => {
    window.location.href = route('admin.reports.export', { 
        tab: activeTab.value, 
        start_date: props.filters.start_date, 
        end_date: props.filters.end_date 
    });
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 font-sans print:bg-white print:min-h-0 text-slate-800">
        
        <div class="fixed top-0 left-0 right-0 bg-rose-600 text-white px-6 py-3 shadow-lg flex justify-between items-center z-50 print:hidden">
            <div class="flex items-center gap-4">
                <button @click="goBack" class="p-2 hover:bg-rose-700 rounded-full transition text-white/80 hover:text-white" title="Back">
                    <ArrowLeft class="w-5 h-5" />
                </button>
                <span class="font-medium text-sm tracking-wide">Preview: <span class="uppercase font-bold text-rose-100">{{ activeTab }}</span></span>
            </div>
            <div class="flex gap-3">
                <button @click="downloadExcel" class="flex items-center gap-2 px-4 py-2 bg-white text-emerald-600 hover:bg-emerald-50 rounded-lg text-xs font-bold uppercase tracking-wider transition shadow-sm border border-transparent">
                    <Download class="w-4 h-4" /> Export Excel
                </button>
                <button @click="printPage" class="flex items-center gap-2 px-4 py-2 bg-rose-800 hover:bg-rose-900 text-white rounded-lg text-xs font-bold uppercase tracking-wider transition shadow-sm border border-rose-700">
                    <Printer class="w-4 h-4" /> Print PDF
                </button>
            </div>
        </div>

        <div class="max-w-[297mm] mx-auto mt-20 mb-10 bg-white shadow-2xl print:shadow-none print:mt-0 print:mb-0 print:max-w-none">
            
            <div class="print:min-h-screen print:flex print:flex-col relative page-section">
                
                <div class="px-12 py-10 flex justify-between items-center border-b border-rose-100"
                    :class="activeTab === 'inventory' ? 'bg-slate-900 text-white' : 'bg-[#FFF0F5]'">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="px-2 py-1 text-[10px] font-bold uppercase tracking-[0.2em] rounded-sm"
                                :class="activeTab === 'inventory' ? 'bg-slate-700 text-slate-300' : 'bg-rose-600 text-white'">
                                OFFICIAL REPORT
                            </span>
                        </div>
                        <h1 class="text-4xl font-serif mb-3 tracking-tight"
                            :class="activeTab === 'inventory' ? 'text-white' : 'text-rose-950'">
                            {{ reportTitle }}
                        </h1>
                        <p class="text-sm italic mb-4 opacity-80">{{ reportSubtitle }}</p>
                        
                        <div class="flex items-center gap-6 text-sm opacity-70">
                            <div class="flex items-center gap-2"><CalendarDays class="w-4 h-4"/><span class="font-medium">{{ filters.start_date }} — {{ filters.end_date }}</span></div>
                            <div class="flex items-center gap-2"><User class="w-4 h-4"/><span>{{ user.name }}</span></div>
                        </div>
                    </div>
                    <div class="p-2 border-2 border-opacity-20 rounded-2xl bg-white/40 backdrop-blur-sm shadow-sm"
                         :class="activeTab === 'inventory' ? 'border-slate-500' : 'border-rose-900'">
                        <img src="/images/logo.png" alt="Skin Lab" class="h-20 w-auto object-contain mix-blend-multiply opacity-90 rounded-xl">
                    </div>
                </div>

                <div class="px-12 py-10 flex-1 flex flex-col">
                    
                    <div v-if="activeTab === 'sales'">
                        <div class="grid grid-cols-4 gap-6 mb-12">
                            <div class="p-6 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Net Revenue</p>
                                <p class="text-3xl font-serif text-emerald-600">{{ formatIDR(data.summary?.net_revenue || 0) }}</p>
                                <p class="text-[10px] text-slate-400 mt-1">*Excl. Shipping</p>
                            </div>
                            <div class="p-6 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Transactions</p>
                                <p class="text-3xl font-serif text-slate-700">{{ data.summary?.total_transactions || 0 }}</p>
                            </div>
                            <div class="p-6 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Avg. Order Value</p>
                                <p class="text-3xl font-serif text-indigo-600">{{ formatIDR(data.summary?.aov || 0) }}</p>
                            </div>
                            <div class="p-6 rounded-2xl bg-white border border-slate-100 shadow-sm">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Gross Revenue</p>
                                <p class="text-3xl font-serif text-slate-700">{{ formatIDR(data.summary?.gross_revenue || 0) }}</p>
                            </div>
                        </div>

                        <div class="mb-8">
                            <h3 class="text-lg font-bold text-slate-800 uppercase tracking-widest mb-6 border-b pb-4 border-slate-100">Top Revenue Generators</h3>
                            <table class="w-full text-sm">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="p-3 text-left w-16">Rank</th>
                                        <th class="p-3 text-left">Product Name</th>
                                        <th class="p-3 text-left">Variant</th>
                                        <th class="p-3 text-right">Qty Sold</th>
                                        <th class="p-3 text-right">Revenue Contrib.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(prod, i) in data.topProducts" :key="i" class="border-b border-slate-50">
                                        <td class="p-3 pl-4 font-serif text-lg text-slate-400 italic">#{{ i + 1 }}</td>
                                        <td class="p-3 font-bold text-slate-800">{{ prod.name }}</td>
                                        <td class="p-3 text-slate-500">{{ prod.volume }}</td>
                                        <td class="p-3 text-right font-bold">{{ prod.qty_sold }}</td>
                                        <td class="p-3 pr-4 text-right font-mono text-emerald-600">{{ formatIDR(prod.revenue) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'inventory'">
                        <div class="p-8 rounded-2xl bg-slate-800 text-white mb-8 shadow-lg">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-1">Total Asset Valuation (Gudang)</p>
                                    <h2 class="text-5xl font-serif tracking-tight">{{ formatIDR(data.assetValue || 0) }}</h2>
                                    <p class="mt-2 text-slate-400 text-sm">Accumulated value of {{ data.totalStockCount }} physical items.</p>
                                </div>
                                <Package class="w-16 h-16 text-slate-600 opacity-50"/>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-8 mb-12">
                            <div class="border border-slate-200 rounded-2xl overflow-hidden">
                                <div class="bg-slate-50 px-6 py-4 border-b border-slate-100">
                                    <h3 class="font-bold text-slate-700 text-xs uppercase tracking-wide flex items-center gap-2">
                                        <ShieldAlert class="w-4 h-4 text-slate-500"/> Dead Stock (Slow Moving > 50 Qty)
                                    </h3>
                                </div>
                                <div class="p-6 bg-white">
                                    <div v-if="data.deadStockItems?.length > 0">
                                        <table class="w-full text-xs">
                                            <tr v-for="item in data.deadStockItems.slice(0,5)" :key="item.sku" class="border-b border-slate-50 last:border-0">
                                                <td class="py-2 text-slate-600">{{ item.name }}</td>
                                                <td class="py-2 text-right font-bold">{{ item.stock }} pcs</td>
                                                <td class="py-2 text-right text-slate-400">{{ formatIDR(item.value) }}</td>
                                            </tr>
                                        </table>
                                        <p class="text-[10px] text-center text-slate-400 mt-2">Recommended Action: Discount / Bundle</p>
                                    </div>
                                    <p v-else class="text-center text-slate-400 italic text-sm py-4">Inventory turnover is healthy.</p>
                                </div>
                            </div>

                            <div class="border border-rose-200 rounded-2xl overflow-hidden">
                                <div class="bg-rose-50 px-6 py-4 border-b border-rose-100">
                                    <h3 class="font-bold text-rose-800 text-xs uppercase tracking-wide flex items-center gap-2">
                                        <AlertTriangle class="w-4 h-4"/> Urgent Restock (Low Stock &lt; 10)
                                    </h3>
                                </div>
                                <div class="p-6 bg-white">
                                    <div v-if="data.lowStockItems?.length > 0">
                                        <div class="flex flex-wrap gap-2">
                                            <span v-for="item in data.lowStockItems" :key="item.sku" 
                                                class="px-2 py-1 bg-white border border-rose-200 text-rose-600 text-[10px] font-bold rounded shadow-sm">
                                                {{ item.name }} ({{ item.stock }})
                                            </span>
                                        </div>
                                    </div>
                                    <p v-else class="text-center text-slate-400 italic text-sm py-4">Stock levels are sufficient.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else-if="activeTab === 'customers'">
                         <div class="grid grid-cols-2 gap-10 mb-8">
                            <div>
                                <h3 class="font-bold text-slate-800 uppercase tracking-widest mb-6 border-b pb-2">Skin Profile Analysis</h3>
                                <div class="space-y-4">
                                    <div v-for="stat in data.skinStats" :key="stat.skin_type">
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="font-bold text-slate-700">{{ stat.skin_type || 'Unspecified' }}</span>
                                            <span class="text-slate-500">{{ stat.count }} Users</span>
                                        </div>
                                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-rose-400" :style="{ width: `${(stat.count / (data.skinStats[0]?.count || 1)) * 100}%` }"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 uppercase tracking-widest mb-6 border-b pb-2">Marketing Insight: Top Concerns</h3>
                                <div class="flex flex-wrap gap-3">
                                    <div v-for="(count, concern) in data.topConcerns" :key="concern" 
                                        class="flex items-center gap-2 px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg">
                                        <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                                        <span class="text-sm text-slate-700 font-medium">{{ concern }}</span>
                                        <span class="text-xs font-bold text-slate-400 ml-1">({{ count }})</span>
                                    </div>
                                </div>
                            </div>
                         </div>
                         <div class="mt-8 pt-8 border-t border-slate-100">
                            <h3 class="font-bold text-slate-800 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <MapPin class="w-4 h-4"/> Top Cities Distribution
                            </h3>
                            <div class="grid grid-cols-4 gap-4">
                                <div v-for="city in data.topCities" :key="city.name" class="p-3 bg-white shadow-sm rounded border border-slate-100 flex justify-between items-center">
                                    <span class="text-xs text-slate-600 font-medium truncate">{{ city.name }}</span>
                                    <span class="text-sm font-bold text-slate-800">{{ city.total }}</span>
                                </div>
                            </div>
                         </div>
                    </div>

                    <div v-else-if="activeTab === 'loyalty'">
                        <div class="bg-gradient-to-r from-amber-200 to-yellow-400 p-8 rounded-2xl text-amber-900 shadow-lg mb-8">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm uppercase font-bold tracking-widest opacity-70">Exclusive Report</p>
                                    <h2 class="text-3xl font-serif font-bold mt-1">The Sultan List (Top Spenders)</h2>
                                    <p class="text-sm mt-2 font-medium">Focus on retaining these high-value customers.</p>
                                </div>
                                <Heart class="w-16 h-16 text-amber-900 opacity-20"/>
                            </div>
                        </div>

                        <table class="w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="p-4 text-left w-12 text-slate-500">Rank</th>
                                    <th class="p-4 text-left text-slate-500">Customer Details</th>
                                    <th class="p-4 text-center text-slate-500">Frequency</th>
                                    <th class="p-4 text-right text-slate-500">Lifetime Value (Period)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(user, i) in data.topSpenders" :key="user.id" class="border-b border-slate-50">
                                    <td class="p-4">
                                        <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-xs border border-amber-200">
                                            {{ i+1 }}
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <p class="font-bold text-slate-800 text-base">{{ user.name }}</p>
                                        <p class="text-slate-400 text-xs">{{ user.email }}</p>
                                    </td>
                                    <td class="p-4 text-center font-bold text-slate-600">{{ user.freq }}x Order</td>
                                    <td class="p-4 text-right font-bold text-emerald-600 text-lg">{{ formatIDR(user.total_spent) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div v-if="activeTab === 'operations'" class="space-y-8">
                        <div class="grid grid-cols-2 gap-8 mb-8">
                        <div class="p-6 bg-red-50 border border-red-100 rounded-2xl text-red-900">
                                <p class="uppercase font-bold text-xs tracking-widest mb-1 opacity-70">Opportunity Loss</p>
                                <h2 class="text-4xl font-serif">{{ formatIDR(data.potentialLoss || 0) }}</h2>
                                <p class="text-xs mt-2 opacity-70">Revenue lost due to cancellations.</p>
                        </div>
                        </div>
                        
                        <h3 class="font-bold text-slate-800 uppercase tracking-widest mb-4 border-b pb-2">Cancellation Reasons</h3>
                        <table class="w-full text-sm border border-slate-200 mb-8">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="p-3 text-left">Reason</th>
                                    <th class="p-3 text-right">Occurrence</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="c in data.cancellations" :key="c.reason" class="border-b border-slate-100">
                                    <td class="p-3 capitalize font-medium text-slate-700">{{ c.reason ? c.reason.replace(/_/g, ' ') : 'No Reason' }}</td>
                                    <td class="p-3 text-right font-bold">{{ c.total }}x</td>
                                </tr>
                            </tbody>
                        </table>

                        <h3 class="font-bold text-slate-800 uppercase tracking-widest mb-4 border-b pb-2">Product Returns</h3>
                        <table class="w-full text-sm border border-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="p-3 text-left">Date</th>
                                    <th class="p-3 text-left">Customer</th>
                                    <th class="p-3 text-left">Reason</th>
                                    <th class="p-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="ret in data.returns" :key="ret.id" class="border-b border-slate-100">
                                    <td class="p-3 text-slate-500">{{ formatDate(ret.created_at) }}</td>
                                    <td class="p-3 font-bold">{{ ret.order?.user?.name || 'Guest' }}</td>
                                    <td class="p-3">{{ ret.reason }}</td>
                                    <td class="p-3 text-center uppercase text-xs font-bold">{{ ret.status }}</td>
                                </tr>
                                <tr v-if="!data.returns || data.returns.length === 0">
                                    <td colspan="4" class="p-4 text-center text-slate-400 italic">No returns in this period.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="activeTab === 'audit'" class="space-y-8">
                        <div class="p-6 bg-slate-800 text-white rounded-2xl shadow-sm mb-8">
                            <p class="uppercase font-bold text-xs tracking-widest mb-1 opacity-70 text-slate-400">Inventory Loss (Opname)</p>
                            <h2 class="text-4xl font-serif text-white">{{ formatIDR(data.opnameLoss || 0) }}</h2>
                            <p class="text-xs mt-2 text-slate-400">Financial impact from missing stock (Physical < System).</p>
                        </div>

                        <h3 class="font-bold text-slate-800 uppercase tracking-widest mb-4 border-b pb-2 flex items-center gap-2">
                            <ShieldAlert class="w-4 h-4"/> Security Audit: Banned/Flagged Users
                        </h3>
                        <table class="w-full text-sm border border-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="p-3 text-left">Date</th>
                                    <th class="p-3 text-left">User Targeted</th>
                                    <th class="p-3 text-left">Reason</th>
                                    <th class="p-3 text-left">Requested By</th>
                                    <th class="p-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="ban in data.bannedUsers" :key="ban.id" class="border-b border-slate-100">
                                    <td class="p-3 text-slate-500 font-mono text-xs">{{ formatDate(ban.created_at) }}</td>
                                    <td class="p-3 font-bold text-slate-800">{{ ban.user?.name }}</td>
                                    <td class="p-3 text-rose-600">{{ ban.reason }}</td>
                                    <td class="p-3 text-xs">{{ ban.requester?.name || 'System' }}</td>
                                    <td class="p-3 text-center">
                                        <span class="px-2 py-1 rounded text-[10px] uppercase font-bold border"
                                            :class="ban.status === 'approved' ? 'bg-rose-100 text-rose-800 border-rose-200' : 'bg-slate-100 text-slate-600'">
                                            {{ ban.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!data.bannedUsers || data.bannedUsers.length === 0">
                                    <td colspan="5" class="p-8 text-center text-slate-400 italic">
                                        No security incidents or ban requests found in this period.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-auto pt-16 flex justify-end gap-16 text-slate-600 print:flex">
                        <div class="text-center w-48">
                            <p class="text-[10px] uppercase tracking-widest mb-16 text-slate-400">Approved By</p>
                            <div class="border-t border-slate-300 pt-2"><p class="font-bold text-sm uppercase">Operations Manager</p></div>
                        </div>
                    </div>

                </div>
            </div>

            <div v-if="activeTab === 'sales' || activeTab === 'inventory'" class="page-break"></div>

            <div v-if="activeTab === 'sales' || activeTab === 'inventory'" class="print:min-h-screen px-12 py-10 page-section">
                
                <div class="flex justify-between items-end border-b-2 border-slate-800 pb-4 mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800 uppercase tracking-tight">
                            {{ activeTab === 'sales' ? 'Transaction Audit Log' : 'Stock Mutation Log' }}
                        </h2>
                        <p class="text-xs text-slate-500 mt-1">Detailed chronological record for audit purposes.</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-mono text-slate-500">Page 2</p>
                    </div>
                </div>

                <table v-if="activeTab === 'sales'" class="w-full text-xs border-collapse border border-slate-300">
                    <thead class="bg-slate-100 print:bg-slate-200">
                        <tr>
                            <th class="border border-slate-300 px-3 py-2 text-left font-bold w-24">Date</th>
                            <th class="border border-slate-300 px-3 py-2 text-left font-bold">Invoice #</th>
                            <th class="border border-slate-300 px-3 py-2 text-left font-bold">Customer</th>
                            <th class="border border-slate-300 px-3 py-2 text-right font-bold">Total</th>
                            <th class="border border-slate-300 px-3 py-2 text-center font-bold">Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in data.detailedTransactions" :key="order.id" class="odd:bg-white even:bg-slate-50">
                            <td class="border border-slate-300 px-3 py-2 text-slate-600 font-mono">{{ formatDate(order.created_at) }}</td>
                            <td class="border border-slate-300 px-3 py-2 font-bold text-slate-800">{{ order.order_number }}</td>
                            <td class="border border-slate-300 px-3 py-2">{{ order.user?.name || 'Guest' }}</td>
                            <td class="border border-slate-300 px-3 py-2 text-right font-mono">{{ formatIDR(order.total_amount) }}</td>
                            <td class="border border-slate-300 px-3 py-2 text-center">
                                <span class="uppercase text-[10px] font-bold px-2 py-0.5 rounded-sm border"
                                    :class="['cod', 'cash'].includes(order.payment_method) ? 'bg-orange-100 border-orange-200 text-orange-800' : 'bg-blue-100 border-blue-200 text-blue-800'">
                                    {{ order.payment_method || 'Online' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <table v-if="activeTab === 'inventory'" class="w-full text-xs border-collapse border border-slate-300">
                    <thead class="bg-slate-100 print:bg-slate-200">
                        <tr>
                            <th class="border border-slate-300 px-3 py-2 text-left w-24">Date Time</th>
                            <th class="border border-slate-300 px-3 py-2 text-left w-32">Sku</th>
                            <th class="border border-slate-300 px-3 py-2 text-left">Item Details</th>
                            <th class="border border-slate-300 px-3 py-2 text-center w-20">Type</th>
                            <th class="border border-slate-300 px-3 py-2 text-right w-16">Change</th>
                            <th class="border border-slate-300 px-3 py-2 text-right w-16">Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(log, i) in data.mutations" :key="i" class="odd:bg-white even:bg-slate-50">
                            <td class="border border-slate-300 px-3 py-2 font-mono text-slate-500">{{ log.date }}</td>
                            <td class="border border-slate-300 px-3 py-2 font-bold text-slate-700">{{ log.ref }}</td>
                            <td class="border border-slate-300 px-3 py-2 text-slate-600">{{ log.item }}</td>
                            
                            <td class="border border-slate-300 px-3 py-2 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold border"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800 border-emerald-200': log.type === 'IN',
                                        'bg-rose-100 text-rose-800 border-rose-200': log.type === 'OUT',
                                        'bg-blue-100 text-blue-800 border-blue-200': log.type === 'MATCH'
                                    }">
                                    {{ log.type }}
                                </span>
                            </td>

                            <td class="border border-slate-300 px-3 py-2 text-right font-mono font-bold"
                                :class="{
                                    'text-emerald-600': log.type === 'IN',
                                    'text-rose-600': log.type === 'OUT',
                                    'text-blue-600': log.type === 'MATCH'
                                }">
                                {{ log.change }}
                            </td>

                            <td class="border border-slate-300 px-3 py-2 text-right font-mono text-slate-500">
                                {{ log.current_stock }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-4 text-[10px] text-slate-400 italic">* System generated report based on real-time database.</div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@media print {
    @page { size: landscape; margin: 0; }
    body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    .page-break { page-break-before: always; break-before: page; display: block; height: 0; }
    .page-section { min-height: 100vh; display: flex; flex-direction: column; }
    .bg-\[\#FFF0F5\] { background-color: #FFF0F5 !important; }
}
</style>