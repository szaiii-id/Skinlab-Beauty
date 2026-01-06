<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { 
    Calendar, TrendingUp, TrendingDown, Package, Users, 
    Heart, AlertTriangle, ShieldAlert, Printer, Crown 
} from 'lucide-vue-next';

const props = defineProps({
    tab: String,
    data: Object,
    filters: Object
});

// State
const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);

// Tabs Configuration
const tabs = [
    { id: 'sales', label: 'Sales & Revenue', icon: TrendingUp, color: 'text-emerald-600', bg: 'bg-emerald-50' },
    { id: 'inventory', label: 'Inventory Assets', icon: Package, color: 'text-blue-600', bg: 'bg-blue-50' },
    { id: 'customers', label: 'Skin Insights', icon: Users, color: 'text-rose-600', bg: 'bg-rose-50' },
    { id: 'loyalty', label: 'Loyalty Brand', icon: Heart, color: 'text-pink-600', bg: 'bg-pink-50' },
    { id: 'operations', label: 'Operations Quality', icon: AlertTriangle, color: 'text-amber-600', bg: 'bg-amber-50' },
    { id: 'audit', label: 'Audit Log', icon: ShieldAlert, color: 'text-slate-600', bg: 'bg-slate-50' },
];

// Letakkan di dalam <script setup>, di bawah const formatIDR

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Actions
const switchTab = (tabId) => {
    router.get(route('admin.reports.index'), { tab: tabId, start_date: startDate.value, end_date: endDate.value }, { preserveScroll: true });
};

const applyFilter = () => {
    switchTab(props.tab);
};

const formatIDR = (value) => {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
};

// ==========================================
// LOGIKA CHART (WARNA DINAMIS & SMOOTH)
// ==========================================

const isTrendingUp = computed(() => {
    const data = props.data.chartData || [];
    if (data.length < 2) return true;
    return data[data.length - 1].total >= data[0].total;
});

const trendColor = computed(() => isTrendingUp.value ? '#10b981' : '#f43f5e');

const line = (pointA, pointB) => {
    const lengthX = pointB.x - pointA.x;
    const lengthY = pointB.y - pointA.y;
    return {
        length: Math.sqrt(Math.pow(lengthX, 2) + Math.pow(lengthY, 2)),
        angle: Math.atan2(lengthY, lengthX)
    };
};

const controlPoint = (current, previous, next, reverse) => {
    const p = previous || current;
    const n = next || current;
    const smoothing = 0.2;
    const o = line(p, n);
    const angle = o.angle + (reverse ? Math.PI : 0);
    const length = o.length * smoothing;
    const x = current.x + Math.cos(angle) * length;
    const y = current.y + Math.sin(angle) * length;
    return [x, y];
};

const bezierCommand = (point, i, a) => {
    const [cpsX, cpsY] = controlPoint(a[i - 1], a[i - 2], point);
    const [cpeX, cpeY] = controlPoint(point, a[i - 1], a[i + 1], true);
    return `C ${cpsX},${cpsY} ${cpeX},${cpeY} ${point.x},${point.y}`;
};

const chartPoints = computed(() => {
    const data = props.data.chartData || []; // Chart Data hanya ada di Sales Tab
    if (data.length === 0) return [];
    
    const maxVal = Math.max(...data.map(d => d.total)) || 1;
    const width = 500; 
    const height = 150; 
    const paddingX = 10;
    
    return data.map((d, i) => {
        const x = (i / (data.length - 1)) * (width - (paddingX * 2)) + paddingX;
        const y = height - 20 - ((d.total / maxVal) * (height - 40)); 
        return { x, y, ...d };
    });
});

const chartPath = computed(() => {
    const points = chartPoints.value;
    if (points.length === 0) return '';
    return points.reduce((acc, point, i, a) => {
        if (i === 0) return `M ${point.x},${point.y}`;
        return `${acc} ${bezierCommand(point, i, a)}`;
    }, '');
});

const areaPath = computed(() => {
    const d = chartPath.value;
    if (!d) return '';
    const height = 150;
    return `${d} L ${chartPoints.value[chartPoints.value.length - 1].x},${height} L ${chartPoints.value[0].x},${height} Z`;
});
</script>

<template>
    <AdminLayout title="Centralized Reports">
        <div class="font-sans space-y-8">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-[2rem] shadow-sm border border-slate-50">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Analytics Center</h1>
                    <p class="text-slate-500 text-sm mt-1">Real-time insights for SkinLab operations.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2 bg-slate-50 px-4 py-2 rounded-xl border border-slate-200">
                        <Calendar class="w-4 h-4 text-slate-400" />
                        <input v-model="startDate" type="date" class="bg-transparent border-none text-sm p-0 focus:ring-0 text-slate-600">
                        <span class="text-slate-300">-</span>
                        <input v-model="endDate" type="date" class="bg-transparent border-none text-sm p-0 focus:ring-0 text-slate-600">
                        <button @click="applyFilter" class="ml-2 text-xs font-bold text-rose-500 hover:text-rose-600">APPLY</button>
                    </div>

                    <Link 
                        :href="route('admin.reports.print', { tab: props.tab, start_date: startDate, end_date: endDate })" 
                        class="flex items-center gap-2 px-5 py-2.5 bg-slate-800 text-white rounded-xl text-sm font-bold hover:bg-slate-700 transition-colors shadow-lg shadow-slate-200"
                    >
                        <Printer class="w-4 h-4" />
                        Print Preview
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <button 
                    v-for="t in tabs" 
                    :key="t.id"
                    @click="switchTab(t.id)"
                    class="flex flex-col items-center justify-center gap-2 p-4 rounded-2xl border transition-all duration-300"
                    :class="props.tab === t.id 
                        ? 'bg-white border-rose-200 shadow-md scale-[1.02]' 
                        : 'bg-white border-transparent hover:bg-slate-50 text-slate-400'"
                >
                    <div class="p-2 rounded-full" :class="props.tab === t.id ? t.bg : 'bg-slate-100'">
                        <component :is="t.icon" class="w-5 h-5" :class="props.tab === t.id ? t.color : 'text-slate-400'" />
                    </div>
                    <span class="text-xs font-bold tracking-wide" :class="props.tab === t.id ? 'text-slate-800' : 'text-slate-400'">
                        {{ t.label }}
                    </span>
                </button>
            </div>

            <div class="space-y-6">
                
                <div v-if="tab === 'sales'" class="space-y-6 animate-in fade-in">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="bg-white p-6 rounded-3xl border border-slate-50 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Gross Revenue</p>
                            <h3 class="text-2xl font-black text-slate-800 mt-2">{{ formatIDR(data.summary?.gross_revenue || 0) }}</h3>
                        </div>
                        <div class="bg-white p-6 rounded-3xl border border-slate-50 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Net Revenue</p>
                            <h3 class="text-2xl font-black text-emerald-600 mt-2">{{ formatIDR(data.summary?.net_revenue || 0) }}</h3>
                            <p class="text-[10px] text-slate-400 mt-1">*Excl. Shipping</p>
                        </div>
                        <div class="bg-white p-6 rounded-3xl border border-slate-50 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Transactions</p>
                            <h3 class="text-2xl font-black text-indigo-600 mt-2">{{ data.summary?.total_transactions || 0 }}</h3>
                        </div>
                        <div class="bg-white p-6 rounded-3xl border border-slate-50 shadow-sm">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Avg Order Value</p>
                            <h3 class="text-2xl font-black text-slate-600 mt-2">{{ formatIDR(data.summary?.aov || 0) }}</h3>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div class="bg-white p-8 rounded-3xl border border-slate-50 shadow-sm">
                            <h3 class="font-bold text-slate-800 mb-6">Top Revenue Generators</h3>
                            <div class="space-y-4">
                                <div v-for="(prod, i) in data.topProducts" :key="i" class="flex items-center justify-between p-3 rounded-xl hover:bg-slate-50 transition-colors">
                                    <div class="flex items-center gap-4">
                                        <span class="font-mono text-slate-300 text-lg font-bold">0{{ i + 1 }}</span>
                                        <div>
                                            <p class="font-bold text-slate-700">{{ prod.name }}</p>
                                            <p class="text-xs text-slate-400">{{ prod.volume }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-emerald-600">{{ formatIDR(prod.revenue) }}</p>
                                        <p class="text-xs text-slate-400">{{ prod.qty_sold }} Sold</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-8 rounded-3xl border border-slate-50 shadow-sm flex flex-col">
                            <div class="flex justify-between items-center mb-6">
                                <h3 class="font-bold text-slate-800">Daily Revenue Trend</h3>
                                <div 
                                    class="flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider"
                                    :class="isTrendingUp ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'"
                                >
                                    <component :is="isTrendingUp ? TrendingUp : TrendingDown" class="w-4 h-4"/>
                                    {{ isTrendingUp ? 'Uptrend' : 'Downtrend' }}
                                </div>
                            </div>
                            
                            <div class="flex-1 relative w-full h-64 flex items-end"> 
                                <svg viewBox="0 0 500 150" class="w-full h-full overflow-visible" style="min-height: 200px;">
                                    
                                    <defs>
                                        <linearGradient id="gradientTrend" x1="0%" y1="0%" x2="0%" y2="100%">
                                            <stop offset="0%" :style="{ stopColor: trendColor, stopOpacity: 0.2 }" />
                                            <stop offset="100%" :style="{ stopColor: trendColor, stopOpacity: 0 }" />
                                        </linearGradient>
                                    </defs>

                                    <line x1="0" y1="130" x2="500" y2="130" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="4 4" />
                                    <line x1="0" y1="75" x2="500" y2="75" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="4 4" />
                                    <line x1="0" y1="20" x2="500" y2="20" stroke="#f1f5f9" stroke-width="1" stroke-dasharray="4 4" />

                                    <path :d="areaPath" fill="url(#gradientTrend)" stroke="none" class="transition-all duration-500" />
                                    <path :d="chartPath" fill="none" :stroke="trendColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="drop-shadow-sm transition-all duration-500" />

                                    <g v-for="(point, i) in chartPoints" :key="i" class="group cursor-pointer">
                                        <rect :x="point.x - 10" :y="0" width="20" height="150" fill="transparent" />
                                        
                                        <circle :cx="point.x" :cy="point.y" r="4" :fill="trendColor" stroke="white" stroke-width="2" />
                                        <circle :cx="point.x" :cy="point.y" r="6" :fill="trendColor" stroke="white" stroke-width="2" class="opacity-0 group-hover:opacity-100 transition-opacity" />
                                        
                                        <foreignObject :x="point.x - 50" :y="point.y - 60" width="100" height="50" class="opacity-0 group-hover:opacity-100 transition-all pointer-events-none">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="bg-slate-800 text-white text-[10px] py-1 px-2 rounded shadow-lg whitespace-nowrap z-50">
                                                    <span class="font-bold block text-center">{{ point.date }}</span>
                                                    <span class="font-mono text-center block" :style="{ color: isTrendingUp ? '#6ee7b7' : '#fda4af' }">
                                                        {{ formatIDR(point.total) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </foreignObject>
                                    </g>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="tab === 'inventory'" class="space-y-6 animate-in fade-in">
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 p-8 rounded-[2rem] text-white shadow-xl flex justify-between items-center">
                        <div>
                            <p class="text-slate-400 text-sm font-bold uppercase tracking-widest">Total Asset Valuation (Warehouse)</p>
                            <h2 class="text-5xl font-black mt-2 tracking-tight">{{ formatIDR(data.assetValue || 0) }}</h2>
                            <p class="text-slate-500 text-sm mt-4">Accumulated value of {{ data.totalStockCount }} physical items.</p>
                        </div>
                        <Package class="w-20 h-20 text-slate-700 opacity-50"/>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-8 rounded-[2rem] border border-slate-50 shadow-sm">
                            <h3 class="font-bold text-slate-700 mb-4 flex items-center gap-2">
                                <ShieldAlert class="w-5 h-5 text-slate-500"/> Dead Stock Analysis
                            </h3>
                            <p class="text-xs text-slate-500 mb-4">Items with >50 qty but no sales in this period.</p>
                            
                            <div v-if="data.deadStockItems?.length > 0" class="space-y-2">
                                <div v-for="item in data.deadStockItems.slice(0,5)" :key="item.sku" class="flex justify-between items-center p-2 rounded bg-slate-50 border border-slate-100">
                                    <div>
                                        <p class="text-sm font-bold text-slate-700">{{ item.name }}</p>
                                        <p class="text-[10px] text-slate-400">{{ item.sku }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold">{{ item.stock }} pcs</p>
                                        <p class="text-[10px] text-rose-500">{{ formatIDR(item.value) }}</p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8 text-slate-400 text-sm italic">
                                Inventory turnover is healthy. No dead stock detected.
                            </div>
                        </div>

                        <div class="bg-white p-8 rounded-[2rem] border border-slate-50 shadow-sm">
                            <h3 class="font-bold text-rose-600 mb-4 flex items-center gap-2">
                                <AlertTriangle class="w-5 h-5"/> Urgent Restock (Low Stock)
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="item in data.lowStockItems" :key="item.sku" class="px-3 py-1 bg-rose-50 text-rose-700 text-xs font-bold rounded-lg border border-rose-100">
                                    {{ item.name }}: {{ item.stock }} left
                                </span>
                                <span v-if="data.lowStockItems?.length === 0" class="text-slate-400 text-sm italic w-full text-center py-8">
                                    All stock levels are sufficient.
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-[2rem] border border-slate-50 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-50">
                            <h3 class="font-bold text-slate-800">Recent Stock Movements (Audit Trail)</h3>
                        </div>
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-xs">
                                <tr>
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4">SKU</th>
                                    <th class="px-6 py-4">Product</th>
                                    <th class="px-6 py-4">Type</th>
                                    <th class="px-6 py-4 text-right">Qty</th>
                                    <th class="px-6 py-4 text-right">Current Stock</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="(log, i) in data.mutations" :key="i" class="hover:bg-slate-50/50">
                                    <td class="px-6 py-4 text-slate-500 font-mono">{{ log.date }}</td>
                                    <td class="px-6 py-4 font-bold text-slate-700">{{ log.ref }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ log.item }}</td>
                                    
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wide border"
                                            :class="{
                                                'bg-emerald-100 text-emerald-700 border-emerald-200': log.type === 'IN',
                                                'bg-rose-100 text-rose-700 border-rose-200': log.type === 'OUT',
                                                'bg-blue-100 text-blue-700 border-blue-200': log.type === 'MATCH'
                                            }">
                                            {{ log.type }}
                                        </span>
                                    </td>
                                    
                                    <td class="px-6 py-4 text-right font-mono font-bold"
                                        :class="{
                                            'text-emerald-600': log.type === 'IN',
                                            'text-rose-600': log.type === 'OUT',
                                            'text-blue-600': log.type === 'MATCH'
                                        }">
                                        {{ log.change }}
                                    </td>
                                    <td class="px-6 py-4 text-right font-mono text-slate-500">{{ log.current_stock }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="tab === 'customers'" class="space-y-6 animate-in fade-in">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="bg-white p-8 rounded-[2rem] border border-slate-50 shadow-sm">
                            <h3 class="font-bold text-slate-800 mb-4">Skin Profile Distribution</h3>
                            <div class="space-y-4">
                                <div v-for="stat in data.skinStats" :key="stat.skin_type">
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-slate-600">{{ stat.skin_type || 'Unspecified' }}</span>
                                        <span class="font-bold text-slate-800">{{ stat.count }}</span>
                                    </div>
                                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-rose-400" :style="{ width: `${(stat.count / (data.skinStats[0]?.count || 1)) * 100}%` }"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-8 rounded-[2rem] border border-slate-50 shadow-sm">
                            <h3 class="font-bold text-slate-800 mb-4">Top Skin Concerns</h3>
                            <div class="flex flex-col gap-2">
                                <div v-for="(count, concern) in data.topConcerns" :key="concern" 
                                    class="flex justify-between items-center p-3 bg-slate-50 rounded-xl">
                                    <span class="text-sm font-medium text-slate-600">{{ concern }}</span>
                                    <span class="text-xs font-bold bg-white px-2 py-1 rounded shadow-sm text-slate-800">{{ count }} Cases</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-8 rounded-[2rem] border border-slate-50 shadow-sm">
                            <h3 class="font-bold text-slate-800 mb-4">Top Locations</h3>
                             <ul class="space-y-2">
                                <li v-for="city in data.topCities" :key="city.name" class="flex justify-between text-sm p-2 border-b border-slate-50 last:border-0">
                                    <span class="text-slate-600">{{ city.name }}</span>
                                    <span class="font-bold text-slate-800">{{ city.total }} Users</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div v-if="tab === 'loyalty'" class="space-y-6 animate-in fade-in">
                    <div class="bg-gradient-to-r from-rose-500 to-pink-600 p-8 rounded-[2rem] text-white shadow-lg">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-bold text-xl flex items-center gap-2"><Crown class="w-6 h-6"/> The Sultan List (Top Spenders)</h3>
                            <p class="text-sm opacity-80">High Value Customers Analysis</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="(user, i) in data.topSpenders" :key="user.id" class="bg-white/10 backdrop-blur-sm p-4 rounded-xl flex items-center justify-between border border-white/10 hover:bg-white/20 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center font-bold text-xs">{{ i + 1 }}</div>
                                    <div>
                                        <p class="font-bold text-sm">{{ user.name }}</p>
                                        <p class="text-xs opacity-70">{{ user.freq }}x Orders</p>
                                    </div>
                                </div>
                                <span class="font-bold text-amber-300">{{ formatIDR(user.total_spent) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="tab === 'operations'" class="space-y-6 animate-in fade-in">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <div class="bg-white border border-slate-200 p-8 rounded-[2rem] shadow-sm flex items-center gap-6">
                            <div class="p-4 bg-rose-50 rounded-full border border-rose-100">
                                <ShieldAlert class="w-8 h-8 text-rose-600"/>
                            </div>
                            <div>
                                <p class="font-bold uppercase tracking-wider text-xs text-slate-500 mb-1">
                                    Potential Revenue Loss (Cancelled)
                                </p>
                                <h2 class="text-4xl font-black tracking-tight text-rose-600">
                                    {{ formatIDR(data.potentialLoss || 0) }}
                                </h2>
                                <p class="text-sm text-slate-500 mt-1">
                                    Value of orders cancelled in this period.
                                </p>
                            </div>
                        </div>

                        <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-200">
                            <h3 class="font-bold text-slate-800 mb-6 flex items-center gap-2">
                                <AlertTriangle class="w-5 h-5 text-amber-500"/> Cancellation Reasons
                            </h3>
                            <div class="space-y-4">
                                <div v-for="c in data.cancellations" :key="c.reason" class="flex items-center gap-3">
                                    <div class="flex-1">
                                        <div class="flex justify-between text-sm mb-1">
                                            <span class="text-slate-700 font-bold capitalize">
                                                {{ c.reason ? c.reason.replace(/_/g, ' ') : 'No Reason' }}
                                            </span>
                                            <span class="font-bold text-slate-800">{{ c.total }}x</span>
                                        </div>
                                        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div class="h-full bg-rose-500" style="width: 50%"></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-if="!data.cancellations?.length" class="text-center text-slate-400 italic text-sm py-4">
                                    No cancellations recorded.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white p-8 rounded-[2rem] border border-slate-200 shadow-sm">
                        <h3 class="font-bold text-slate-800 mb-6">Recent Product Returns</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-slate-500 uppercase font-bold bg-slate-50 border-b border-slate-100">
                                    <tr>
                                        <th class="px-4 py-3">Date</th>
                                        <th class="px-4 py-3">Customer</th>
                                        <th class="px-4 py-3">Reason</th>
                                        <th class="px-4 py-3 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <tr v-for="ret in data.returns" :key="ret.id" class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-3 text-slate-600 font-mono">{{ formatDate(ret.created_at) }}</td>
                                        <td class="px-4 py-3 font-bold text-slate-800">{{ ret.order?.user?.name || 'Guest' }}</td>
                                        <td class="px-4 py-3 text-slate-700">{{ ret.reason }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide border"
                                                :class="ret.status === 'approved' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'">
                                                {{ ret.status }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="!data.returns?.length">
                                        <td colspan="4" class="p-8 text-center text-slate-400 italic">No returns found in this period.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div v-if="tab === 'audit'" class="space-y-6 animate-in fade-in">
                    <div class="bg-slate-800 p-8 rounded-[2rem] text-white shadow-xl flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">Inventory Loss Value (Opname)</p>
                            <h2 class="text-4xl font-black mt-2 tracking-tight">{{ formatIDR(data.opnameLoss || 0) }}</h2>
                            <p class="text-slate-500 text-sm mt-2">Discrepancy found during physical stock check.</p>
                        </div>
                        <Package class="w-16 h-16 text-slate-600 opacity-50"/>
                    </div>

                    <div class="bg-white rounded-[2rem] border border-slate-50 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-slate-50 flex items-center gap-2">
                            <ShieldAlert class="w-5 h-5 text-rose-500"/>
                            <h3 class="font-bold text-slate-800">Security Audit: Banned Users</h3>
                        </div>
                        <table class="w-full text-sm text-left">
                            <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-xs">
                                <tr>
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4">User</th>
                                    <th class="px-6 py-4">Reason</th>
                                    <th class="px-6 py-4">Requested By</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="ban in data.bannedUsers" :key="ban.id" class="hover:bg-slate-50">
                                    <td class="px-6 py-4 font-mono text-slate-500">{{ ban.created_at }}</td>
                                    <td class="px-6 py-4 font-bold text-slate-800">{{ ban.user?.name }}</td>
                                    <td class="px-6 py-4 text-rose-600">{{ ban.reason }}</td>
                                    <td class="px-6 py-4 text-slate-500">{{ ban.requester?.name || 'System' }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wide border"
                                            :class="ban.status === 'approved' ? 'bg-rose-100 text-rose-700 border-rose-200' : 'bg-slate-100 text-slate-600 border-slate-200'">
                                            {{ ban.status }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!data.bannedUsers || data.bannedUsers.length === 0">
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-400 italic">No security incidents found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AdminLayout>
</template>