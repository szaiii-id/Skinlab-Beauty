<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import {
  Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler
} from 'chart.js';
import { Line } from 'vue-chartjs';

// Registrasi modul Chart.js
ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    chartData: Object, // { labels: [], data: [], range: '1w' }
});

// Fungsi Ganti Filter (Tanpa Refresh Halaman Penuh)
const updateRange = (range) => {
    router.visit(route('admin.dashboard'), {
        method: 'get',
        data: { range: range },
        preserveState: true,
        preserveScroll: true,
        only: ['revenueChart'], // Hanya update chart, bagian lain diam
    });
};

// Konfigurasi Data & Warna
const computedChartData = computed(() => ({
  labels: props.chartData.labels,
  datasets: [
    {
      label: 'Revenue',
      data: props.chartData.data,
      borderColor: '#3b82f6', // Biru Utama
      backgroundColor: (context) => {
        const ctx = context.chart.ctx;
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)'); // Biru pekat di atas
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0.0)'); // Transparan di bawah
        return gradient;
      },
      borderWidth: 3,
      tension: 0.4, // Garis melengkung halus
      pointRadius: props.chartData.range === '1d' ? 4 : 0, // Titik muncul cuma di mode 1 Hari
      pointHoverRadius: 6,
      fill: true,
    }
  ]
}));

// Opsi Tampilan Grafik (Hilangkan Grid Kasar)
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      mode: 'index',
      intersect: false,
      backgroundColor: 'rgba(255, 255, 255, 0.95)',
      titleColor: '#1e293b',
      bodyColor: '#1e293b',
      borderColor: '#e2e8f0',
      borderWidth: 1,
      padding: 12,
      displayColors: false,
      callbacks: {
        label: (context) => `Rp ${new Intl.NumberFormat('id-ID').format(context.raw)}`
      }
    }
  },
  scales: {
    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 }, maxTicksLimit: 8 } },
    y: { border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 10 }, callback: (v) => v >= 1000 ? (v/1000) + 'k' : v } }
  },
  interaction: { mode: 'nearest', axis: 'x', intersect: false }
};

// Daftar Tombol Filter
const filters = [
    { id: '1d', label: '1 Day' },
    { id: '1w', label: '1 Week' },
    { id: '1m', label: '1 Month' },
    { id: '1y', label: '1 Year' }
];
</script>

<template>
  <div class="bg-white p-6 rounded-[2rem] border border-slate-50 shadow-sm w-full transition-all hover:shadow-md">
    
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
      
      <div class="flex bg-slate-100 p-1.5 rounded-xl">
        <button 
          v-for="filter in filters" 
          :key="filter.id"
          @click="updateRange(filter.id)" 
          class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all duration-300"
          :class="props.chartData.range === filter.id 
            ? 'bg-white text-blue-600 shadow-sm scale-105' 
            : 'text-slate-400 hover:text-slate-600'"
        >
          {{ filter.label }}
        </button>
      </div>

      <div class="flex items-center gap-2">
         <div class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></div>
         <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">
             Revenue Trend
         </h3>
      </div>
    </div>

    <div class="relative h-[320px] w-full">
      <div v-if="props.chartData.data.length === 0" class="absolute inset-0 flex flex-col items-center justify-center text-slate-400">
          <p class="text-sm font-medium">No sales data found.</p>
          <p class="text-xs opacity-70 mt-1">Try selecting a wider date range.</p>
      </div>
      <Line v-else :data="computedChartData" :options="chartOptions" />
    </div>

  </div>
</template>