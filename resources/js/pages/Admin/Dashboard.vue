<script setup>
import AdminLayout from '@/layouts/AdminLayout.vue';
import RevenueChart from '@/components/RevenueChart.vue'; // Import Chart Baru
import { Link } from '@inertiajs/vue3';
import { 
    TrendingUp, Users, Activity, ShieldAlert, Star, 
    Megaphone, RotateCcw, CheckCircle2, Package, Truck, 
    ArrowRight, Loader2
} from 'lucide-vue-next';

// Definisikan Props dengan Nilai Default (Anti-Blank)
const props = defineProps({
    summary: { 
        type: Object, 
        default: () => ({ revenue_total: 0, orders_total: 0, total_campaigns: 0, avg_rating: 0, total_customers: 0 }) 
    },
    actions: { 
        type: Object, 
        default: () => ({ active_opname: false, pending_returns: 0, pending_bans: 0, low_stock: 0 }) 
    },
    revenueChart: { type: Object, default: () => ({ labels: [], data: [], range: '1w' }) },
    skinStats: { type: Array, default: () => [] },
    stockLog: { type: Array, default: () => [] },
    orderStats: { type: Object, default: () => ({ processing: 0, pickup: 0, shipped: 0 }) }
});

const formatIDR = (value) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);
</script>

<template>
    <AdminLayout title="Command Center">
        <div class="space-y-8 font-sans pb-10">
            
            <div class="flex flex-col md:flex-row justify-between md:items-end gap-4 animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight">Executive Dashboard</h1>
                    <p class="text-slate-500 mt-1 font-medium">Real-time overview of business performance.</p>
                </div>
                
                <div class="flex gap-3 flex-wrap">
                    <Link v-if="actions.active_opname" :href="route('admin.stock-opname.index')" 
                        class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold shadow-lg shadow-indigo-200 animate-pulse hover:bg-indigo-700 transition">
                        <Activity class="w-4 h-4"/> Live Opname
                    </Link>
                    
                    <Link v-if="actions.pending_returns > 0" :href="route('admin.returns.index')" 
                        class="flex items-center gap-2 px-4 py-2 bg-amber-50 text-amber-700 rounded-xl text-xs font-bold border border-amber-200 hover:bg-amber-100 transition">
                        <RotateCcw class="w-4 h-4"/> {{ actions.pending_returns }} Returns
                    </Link>

                    <Link v-if="actions.pending_bans > 0" :href="route('admin.ban-requests.index')" 
                        class="flex items-center gap-2 px-4 py-2 bg-rose-50 text-rose-700 rounded-xl text-xs font-bold border border-rose-200 hover:bg-rose-100 transition">
                        <ShieldAlert class="w-4 h-4"/> {{ actions.pending_bans }} Bans
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md hover:scale-[1.02] transition-all duration-300">
                    <div class="absolute right-0 top-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                        <TrendingUp class="w-24 h-24 text-emerald-600"/>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Revenue</p>
                        <h3 class="text-3xl font-black text-slate-800 mt-2 tracking-tight">{{ formatIDR(summary.revenue_total) }}</h3>
                        <p class="text-xs text-emerald-600 font-bold mt-2 flex items-center gap-1">
                            <CheckCircle2 class="w-3 h-3"/> {{ summary.orders_total }} Orders Completed
                        </p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md hover:scale-[1.02] transition-all duration-300">
                    <div class="absolute right-0 top-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                        <Users class="w-24 h-24 text-blue-600"/>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Customer Base</p>
                        <h3 class="text-3xl font-black text-slate-800 mt-2 tracking-tight">{{ summary.total_customers }}</h3>
                        <Link :href="route('admin.customers.index')" class="text-xs text-blue-600 font-bold mt-2 hover:underline block">
                            Manage Users &rarr;
                        </Link>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm relative overflow-hidden group hover:shadow-md hover:scale-[1.02] transition-all duration-300">
                    <div class="absolute right-0 top-0 p-6 opacity-5 group-hover:opacity-10 transition-opacity">
                        <Megaphone class="w-24 h-24 text-purple-600"/>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Active Promos</p>
                        <h3 class="text-3xl font-black text-slate-800 mt-2 tracking-tight">{{ summary.total_campaigns }}</h3>
                        <Link :href="route('admin.banners.index')" class="text-xs text-purple-600 font-bold mt-2 hover:underline block">
                            View Banners &rarr;
                        </Link>
                    </div>
                </div>

                <div class="p-6 rounded-[2rem] shadow-sm relative overflow-hidden text-white transition-all duration-300 hover:shadow-md hover:scale-[1.02]"
                     :class="actions.low_stock > 0 ? 'bg-gradient-to-br from-rose-500 to-rose-600' : 'bg-slate-800'">
                    <div>
                        <p class="text-[10px] font-bold opacity-70 uppercase tracking-widest">Inventory Status</p>
                        <h3 class="text-3xl font-black mt-2 tracking-tight">{{ actions.low_stock }} <span class="text-lg font-medium opacity-80">Low Items</span></h3>
                        <p class="text-xs opacity-80 mt-2">
                            {{ actions.low_stock > 0 ? 'Requires immediate restocking.' : 'Stock levels are healthy.' }}
                        </p>
                    </div>
                    <Link :href="route('admin.reports.index', { tab: 'inventory' })" class="absolute bottom-6 right-6 p-2 bg-white/20 rounded-full hover:bg-white/30 transition backdrop-blur-sm">
                        <ArrowRight class="w-4 h-4 text-white"/>
                    </Link>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 space-y-8">
                    
                    <RevenueChart :chartData="revenueChart" />

                    <div class="grid grid-cols-3 gap-4">
                        <Link :href="route('admin.orders.index', { status: 'processing' })" 
                            class="bg-blue-50/50 p-4 rounded-2xl border border-blue-100 text-center hover:bg-blue-100 transition group cursor-pointer">
                            <h4 class="text-2xl font-black text-blue-700 group-hover:scale-110 transition-transform">{{ orderStats.processing }}</h4>
                            <div class="flex items-center justify-center gap-1.5 text-[10px] font-bold uppercase text-blue-400 tracking-wider mt-1">
                                <Loader2 class="w-3 h-3 animate-spin"/> Processing
                            </div>
                        </Link>
                        
                        <Link :href="route('admin.orders.index', { status: 'schedule_pickup' })" 
                            class="bg-amber-50/50 p-4 rounded-2xl border border-amber-100 text-center hover:bg-amber-100 transition group cursor-pointer">
                            <h4 class="text-2xl font-black text-amber-700 group-hover:scale-110 transition-transform">{{ orderStats.pickup }}</h4>
                            <div class="flex items-center justify-center gap-1.5 text-[10px] font-bold uppercase text-amber-400 tracking-wider mt-1">
                                <Package class="w-3 h-3"/> Ready Pickup
                            </div>
                        </Link>
                        
                        <Link :href="route('admin.orders.index', { status: 'shipped' })" 
                            class="bg-emerald-50/50 p-4 rounded-2xl border border-emerald-100 text-center hover:bg-emerald-100 transition group cursor-pointer">
                            <h4 class="text-2xl font-black text-emerald-700 group-hover:scale-110 transition-transform">{{ orderStats.shipped }}</h4>
                            <div class="flex items-center justify-center gap-1.5 text-[10px] font-bold uppercase text-emerald-400 tracking-wider mt-1">
                                <Truck class="w-3 h-3"/> Shipped
                            </div>
                        </Link>
                    </div>

                    <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm">
                         <div class="flex justify-between items-center mb-6">
                             <h3 class="font-bold text-slate-800">Customer Skin Profiles</h3>
                             <Link :href="route('admin.skin-analysis.index')" class="text-xs font-bold text-slate-400 hover:text-slate-600 transition">
                                View Analysis
                            </Link>
                         </div>
                         <div class="space-y-5">
                             <div v-for="skin in skinStats" :key="skin.skin_type" class="group">
                                 <div class="flex justify-between text-sm mb-1.5">
                                     <span class="font-bold text-slate-700">{{ skin.skin_type }}</span>
                                     <span class="text-xs font-bold text-slate-400 group-hover:text-rose-500 transition">{{ skin.total }} Users</span>
                                 </div>
                                 <div class="w-full h-2.5 bg-slate-50 rounded-full overflow-hidden">
                                     <div class="h-full bg-rose-400 rounded-full group-hover:bg-rose-500 transition-all duration-500" 
                                          :style="{ width: `${(skin.total / (skinStats[0]?.total || 1)) * 100}%` }"></div>
                                 </div>
                             </div>
                             <div v-if="skinStats.length === 0" class="text-center py-4 text-slate-400 text-sm italic">
                                 No skin profile data collected yet.
                             </div>
                         </div>
                    </div>

                </div>

                <div class="space-y-8">
                    
                    <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm h-fit">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                                <Package class="w-4 h-4 text-slate-400"/> Live Stock Activity
                            </h3>
                            <span class="text-[10px] font-bold bg-slate-100 px-2 py-1 rounded text-slate-500">REALTIME</span>
                        </div>
                        <div class="space-y-0">
                            <div v-for="(log, i) in stockLog" :key="i" class="flex gap-4 pb-6 last:pb-0 relative group">
                                <div v-if="i !== stockLog.length - 1" class="absolute left-[19px] top-8 bottom-0 w-0.5 bg-slate-100 group-hover:bg-slate-200 transition"></div>
                                
                                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 border-2 z-10 bg-white transition-all group-hover:scale-110"
                                    :class="{
                                        'border-emerald-100 text-emerald-600': log.type === 'IN',
                                        'border-rose-100 text-rose-600': log.type === 'OUT',
                                        'border-blue-100 text-blue-600': log.type === 'MATCH'
                                    }">
                                    <span class="text-[10px] font-black">{{ log.type === 'MATCH' ? '=' : (log.type === 'IN' ? '+' : '-') }}</span>
                                </div>

                                <div>
                                    <p class="text-[10px] text-slate-400 font-mono">{{ log.time }}</p>
                                    <p class="text-sm font-bold text-slate-700 leading-tight mt-0.5">{{ log.item }}</p>
                                    <p class="text-[10px] mt-1 font-bold uppercase tracking-wide"
                                       :class="{
                                        'text-emerald-600': log.type === 'IN',
                                        'text-rose-600': log.type === 'OUT',
                                        'text-blue-600': log.type === 'MATCH'
                                       }">
                                       {{ log.type }} {{ log.change !== 0 ? Math.abs(log.change) + ' pcs' : '(Opname Verified)' }}
                                    </p>
                                </div>
                            </div>
                            <div v-if="stockLog.length === 0" class="text-center text-slate-400 text-sm italic py-8">
                                No stock movement recorded recently.
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-slate-800 to-slate-900 p-6 rounded-[2rem] shadow-lg text-white">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest opacity-60">Global Rating</p>
                                <h3 class="text-4xl font-black mt-2">{{ summary.avg_rating }}</h3>
                                <div class="flex text-yellow-400 gap-1 mt-2">
                                    <Star v-for="n in 5" :key="n" class="w-4 h-4" :class="n <= Math.round(summary.avg_rating) ? 'fill-current' : 'opacity-30'"/>
                                </div>
                            </div>
                            <div class="p-3 bg-white/10 rounded-full backdrop-blur-sm">
                                <Star class="w-6 h-6 text-yellow-400"/>
                            </div>
                        </div>
                        <Link :href="route('admin.reviews.index')" class="mt-6 block w-full py-2 bg-white/10 hover:bg-white/20 rounded-xl text-center text-xs font-bold transition">
                            Read Reviews
                        </Link>
                    </div>

                </div>
            </div>
        </div>
    </AdminLayout>
</template>