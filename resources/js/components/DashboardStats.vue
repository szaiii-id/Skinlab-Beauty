<script setup>
import { Link } from '@inertiajs/vue3';
import { ScanFace, CheckCircle2, Ticket, ChevronRight } from 'lucide-vue-next';

defineProps({
    stats: Object // { skin_type, routine_progress, routine_count, voucher_count }
});
</script>

<template>
    <div class="flex md:grid md:grid-cols-3 overflow-x-auto md:overflow-visible gap-4 md:gap-6 mb-6 md:mb-8 pb-4 md:pb-0 snap-x snap-mandatory scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0">
        
        <div class="min-w-[85%] sm:min-w-[300px] md:min-w-0 snap-center bg-white p-5 md:p-6 rounded-2xl shadow-[0_2px_8px_-2px_rgba(0,0,0,0.05)] border border-gray-100 hover:border-blue-100 hover:shadow-lg hover:shadow-blue-100/50 transition-all duration-300 group flex flex-col justify-between h-full">
            <div>
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl group-hover:scale-110 transition-transform duration-300 shadow-sm">
                        <ScanFace class="w-6 h-6" />
                    </div>
                    <span v-if="stats.skin_type" class="text-[10px] font-bold bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full uppercase tracking-wide">
                        Detected
                    </span>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] md:text-xs uppercase font-bold tracking-wider mb-1">Skin Condition</p>
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 truncate tracking-tight" :title="stats.skin_type || 'Not Analyzed'">
                        {{ stats.skin_type || 'Not Analyzed' }}
                    </h3>
                </div>
            </div>
            
            <Link href="/skin-analysis" class="mt-4 md:mt-6 inline-flex items-center text-sm text-blue-600 font-bold hover:text-blue-700 transition-colors group-hover:translate-x-1 duration-200">
                {{ stats.skin_type ? 'Details & Products' : 'Start Analysis' }} 
                <ChevronRight class="w-4 h-4 ml-1 stroke-[3]"/>
            </Link>
        </div>

        <div class="min-w-[85%] sm:min-w-[300px] md:min-w-0 snap-center bg-white p-5 md:p-6 rounded-2xl shadow-[0_2px_8px_-2px_rgba(0,0,0,0.05)] border border-gray-100 hover:border-green-100 hover:shadow-lg hover:shadow-green-100/50 transition-all duration-300 group flex flex-col justify-between h-full">
            <div>
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-green-50 text-green-600 rounded-xl group-hover:scale-110 transition-transform duration-300 shadow-sm">
                        <CheckCircle2 class="w-6 h-6" />
                    </div>
                    <div class="text-right">
                        <span class="text-xl md:text-2xl font-bold text-gray-900 tracking-tight">{{ stats.routine_progress }}%</span>
                    </div>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] md:text-xs uppercase font-bold tracking-wider mb-2">Daily Progress</p>
                    <div class="w-full bg-gray-100 rounded-full h-2.5 mb-1 overflow-hidden">
                        <div 
                            class="bg-gradient-to-r from-green-400 to-emerald-500 h-full rounded-full transition-all duration-1000 ease-out shadow-[0_0_10px_rgba(16,185,129,0.4)]" 
                            :style="{ width: stats.routine_progress + '%' }"
                        ></div>
                    </div>
                </div>
            </div>

            <Link href="/my-routine" class="mt-4 md:mt-6 inline-flex items-center text-sm text-green-600 font-bold hover:text-green-700 transition-colors group-hover:translate-x-1 duration-200">
                Continue Checklist ({{ stats.routine_count }}) 
                <ChevronRight class="w-4 h-4 ml-1 stroke-[3]"/>
            </Link>
        </div>

        <div class="min-w-[85%] sm:min-w-[300px] md:min-w-0 snap-center bg-white p-5 md:p-6 rounded-2xl shadow-[0_2px_8px_-2px_rgba(0,0,0,0.05)] border border-gray-100 hover:border-purple-100 hover:shadow-lg hover:shadow-purple-100/50 transition-all duration-300 group flex flex-col justify-between h-full">
            <div>
                <div class="flex justify-between items-start mb-4">
                    <div class="p-3 bg-purple-50 text-purple-600 rounded-xl group-hover:scale-110 transition-transform duration-300 shadow-sm">
                        <Ticket class="w-6 h-6" />
                    </div>
                    <span class="text-[10px] font-bold bg-purple-100 text-purple-700 px-2.5 py-1 rounded-full uppercase tracking-wide">
                        {{ stats.voucher_count }} Active
                    </span>
                </div>
                <div>
                    <p class="text-gray-400 text-[10px] md:text-xs uppercase font-bold tracking-wider mb-1">Voucher Wallet</p>
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 tracking-tight">
                        {{ stats.voucher_count }} Vouchers Available
                    </h3>
                </div>
            </div>

            <Link href="/rewards" class="mt-4 md:mt-6 inline-flex items-center text-sm text-purple-600 font-bold hover:text-purple-700 transition-colors group-hover:translate-x-1 duration-200">
                View Reward Catalog 
                <ChevronRight class="w-4 h-4 ml-1 stroke-[3]"/>
            </Link>
        </div>

    </div>
</template>

<style scoped>
/* Utility untuk menyembunyikan scrollbar tapi tetap bisa di-scroll */
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>