<script setup>
import { Link } from '@inertiajs/vue3';
import { ScanFace, CheckCircle2, Ticket, ChevronRight } from 'lucide-vue-next';

defineProps({
    stats: Object // { skin_type, routine_progress, routine_count, voucher_count }
});
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition-colors">
                    <ScanFace class="w-6 h-6" />
                </div>
                <span v-if="stats.skin_type" class="text-[10px] font-bold bg-blue-100 text-blue-700 px-2 py-1 rounded-lg uppercase tracking-wide">Detected</span>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase font-bold tracking-wider mb-1">Skin Condition</p>
                <h3 class="text-xl font-bold text-gray-900 truncate" :title="stats.skin_type || 'Not Analyzed'">
                    {{ stats.skin_type || 'Not Analyzed' }}
                </h3>
                <Link href="/skin-analysis" class="mt-4 inline-flex items-center text-sm text-blue-600 font-medium hover:text-blue-700 transition-colors">
                    {{ stats.skin_type ? 'Check Details & Products' : 'Start Analysis Now' }} <ChevronRight class="w-4 h-4 ml-1"/>
                </Link>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-green-50 text-green-600 rounded-xl group-hover:bg-green-600 group-hover:text-white transition-colors">
                    <CheckCircle2 class="w-6 h-6" />
                </div>
                <div class="text-right">
                    <span class="text-2xl font-bold text-gray-900">{{ stats.routine_progress }}%</span>
                </div>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase font-bold tracking-wider mb-1">Daily Progress</p>
                <div class="w-full bg-gray-100 rounded-full h-2 mb-3">
                    <div class="bg-green-500 h-2 rounded-full transition-all duration-1000 ease-out" :style="{ width: stats.routine_progress + '%' }"></div>
                </div>
                <Link href="/my-routine" class="inline-flex items-center text-sm text-green-600 font-medium hover:text-green-700 transition-colors">
                    Continue Checklist ({{ stats.routine_count }}) <ChevronRight class="w-4 h-4 ml-1"/>
                </Link>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-purple-50 text-purple-600 rounded-xl group-hover:bg-purple-600 group-hover:text-white transition-colors">
                    <Ticket class="w-6 h-6" />
                </div>
                <span class="text-[10px] font-bold bg-purple-100 text-purple-700 px-2 py-1 rounded-lg uppercase tracking-wide">{{ stats.voucher_count }} Active</span>
            </div>
            <div>
                <p class="text-gray-400 text-xs uppercase font-bold tracking-wider mb-1">Voucher Wallet</p>
                <h3 class="text-xl font-bold text-gray-900">{{ stats.voucher_count }} Vouchers</h3>
                <Link href="/rewards" class="mt-4 inline-flex items-center text-sm text-purple-600 font-medium hover:text-purple-700 transition-colors">
                    View Reward Catalog <ChevronRight class="w-4 h-4 ml-1"/>
                </Link>
            </div>
        </div>
    </div>
</template>