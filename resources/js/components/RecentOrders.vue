<script setup>
import { Link } from '@inertiajs/vue3';
import { ShoppingBag, Truck, CreditCard, Calendar, ChevronRight } from 'lucide-vue-next';

defineProps({
    orders: Array
});

const emit = defineEmits(['track', 'pay']);

// Helpers
const formatCurrency = (val) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(val);

const formatDate = (date) => new Date(date).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });

const getStatusColor = (status) => {
    const colors = {
        'pending': 'bg-amber-100 text-amber-700 border-amber-200',
        'paid': 'bg-blue-100 text-blue-700 border-blue-200',
        'processing': 'bg-indigo-100 text-indigo-700 border-indigo-200',
        'shipped': 'bg-purple-100 text-purple-700 border-purple-200',
        'completed': 'bg-green-100 text-green-700 border-green-200',
        'canceled': 'bg-red-100 text-red-700 border-red-200'
    };
    return colors[status] || 'bg-gray-100 text-gray-600 border-gray-200';
};
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-5 md:p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/30">
            <div>
                <h2 class="text-base md:text-lg font-bold text-gray-900">Recent Orders</h2>
                <p class="text-gray-500 text-xs md:text-sm mt-0.5">Track your shopping status.</p>
            </div>
            <Link href="/orders" class="text-xs md:text-sm font-bold text-rose-600 hover:bg-rose-50 px-3 py-2 rounded-lg transition-colors flex items-center">
                View All <ChevronRight class="w-3 h-3 md:hidden ml-1"/>
            </Link>
        </div>

        <div v-if="orders.length > 0">
            
            <div class="md:hidden divide-y divide-gray-100">
                <div v-for="order in orders" :key="order.id" class="p-5 flex flex-col gap-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Order ID</p>
                            <span class="font-mono text-gray-900 font-bold text-sm">#{{ order.order_number }}</span>
                        </div>
                        <span :class="getStatusColor(order.order_status)" class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wide border">
                            {{ order.order_status }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 bg-gray-50 p-3 rounded-xl border border-gray-100">
                        <div>
                            <div class="flex items-center gap-1.5 text-gray-400 mb-1">
                                <Calendar class="w-3 h-3" />
                                <span class="text-[10px] font-bold uppercase">Date</span>
                            </div>
                            <p class="text-xs font-semibold text-gray-700">{{ formatDate(order.created_at) }}</p>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 text-gray-400 mb-1">
                                <ShoppingBag class="w-3 h-3" />
                                <span class="text-[10px] font-bold uppercase">Total</span>
                            </div>
                            <p class="text-sm font-bold text-gray-900">{{ formatCurrency(order.total_amount) }}</p>
                        </div>
                    </div>

                    <div v-if="['shipped', 'completed', 'pending'].includes(order.order_status)">
                        <button 
                            v-if="['shipped', 'completed'].includes(order.order_status)"
                            @click="$emit('track', order)"
                            class="w-full flex justify-center items-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl text-sm font-bold hover:bg-gray-50 active:scale-[0.98] transition-all shadow-sm"
                        >
                            <Truck class="w-4 h-4" /> Track Package
                        </button>
                        
                        <button 
                            v-else-if="order.order_status === 'pending' && order.payment_method === 'online_payment' && order.snap_token"
                            @click="$emit('pay', order.snap_token)"
                            class="w-full flex justify-center items-center gap-2 bg-rose-600 text-white px-4 py-2.5 rounded-xl text-sm font-bold hover:bg-rose-700 active:scale-[0.98] transition-all shadow-md shadow-rose-200"
                        >
                            <CreditCard class="w-4 h-4" /> Pay Now
                        </button>
                    </div>
                </div>
            </div>

            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4 font-bold">Order ID</th>
                            <th class="px-6 py-4 font-bold">Date</th>
                            <th class="px-6 py-4 font-bold">Total</th>
                            <th class="px-6 py-4 font-bold">Status</th>
                            <th class="px-6 py-4 font-bold text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <tr v-for="order in orders" :key="order.id" class="hover:bg-rose-50/20 transition-colors group">
                            <td class="px-6 py-4">
                                <span class="font-mono text-gray-700 font-bold group-hover:text-rose-600 transition-colors">#{{ order.order_number }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(order.created_at) }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ formatCurrency(order.total_amount) }}</td>
                            <td class="px-6 py-4">
                                <span :class="getStatusColor(order.order_status)" class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide border">
                                    {{ order.order_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button 
                                    v-if="['shipped', 'completed'].includes(order.order_status)"
                                    @click="$emit('track', order)"
                                    class="inline-flex items-center gap-1.5 bg-white border border-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-gray-50 hover:text-rose-600 hover:border-rose-200 transition-all shadow-sm"
                                >
                                    <Truck class="w-3.5 h-3.5" /> Track
                                </button>
                                
                                <button 
                                    v-else-if="order.order_status === 'pending' && order.payment_method === 'online_payment' && order.snap_token"
                                    @click="$emit('pay', order.snap_token)"
                                    class="inline-flex items-center gap-1.5 bg-rose-600 text-white px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-rose-700 transition-all shadow-sm shadow-rose-200"
                                >
                                    <CreditCard class="w-3.5 h-3.5" /> Pay Now
                                </button>

                                <span v-else class="text-gray-300 text-xs font-medium italic">No Action</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        <div v-else class="px-6 py-16 text-center">
            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <ShoppingBag class="w-8 h-8 text-gray-300" />
            </div>
            <p class="text-gray-900 font-bold mb-1">No orders yet</p>
            <p class="text-gray-500 text-sm mb-4">Start shopping for your favorite products!</p>
            <Link href="/catalog" class="text-rose-600 font-bold hover:underline">Start Shopping</Link>
        </div>

    </div>
</template>