<script setup>
import { ref, onMounted, computed } from 'vue';
import { MapPin, Plus, Check, X, Edit, Trash2 } from 'lucide-vue-next';
import axios from 'axios';
import Swal from 'sweetalert2';
import AddressFormModal from './AddressFormModal.vue';

const emit = defineEmits(['address-selected']);

// State
const addresses = ref([]);
const selectedId = ref(null);
const isSelectorOpen = ref(false); 
const isFormOpen = ref(false);     
const editingAddress = ref(null);  
const isLoading = ref(false);

// Load Data
const loadAddresses = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/api/addresses');
        addresses.value = response.data.data;
        
        // Auto-select logic
        if (!selectedId.value && addresses.value.length > 0) {
            const defaultAddr = addresses.value.find(a => a.is_default);
            selectedId.value = defaultAddr ? defaultAddr.id : addresses.value[0].id;
            emitSelection();
        }
    } catch (error) {
        console.error("Load error:", error);
    } finally {
        isLoading.value = false;
    }
};

const emitSelection = () => {
    const addr = addresses.value.find(a => a.id === selectedId.value);
    if (addr) emit('address-selected', addr);
};

const selectAddress = (id) => {
    selectedId.value = id;
    emitSelection();
    isSelectorOpen.value = false;
};

// Form Handlers
const openCreateForm = () => {
    editingAddress.value = null;
    isFormOpen.value = true;
};

const openEditForm = (addr) => {
    editingAddress.value = addr;
    isFormOpen.value = true;
};

const handleSaved = () => {
    loadAddresses(); 
    isFormOpen.value = false;
};

// Delete Handler
const deleteAddress = (id) => {
    Swal.fire({
        title: 'Delete Address?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete it!'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(`/api/addresses/${id}`);
                await loadAddresses();
                Swal.fire('Deleted!', 'Address has been deleted.', 'success');
            } catch (e) {
                Swal.fire('Error', 'Failed to delete address.', 'error');
            }
        }
    });
};

const activeAddress = computed(() => addresses.value.find(a => a.id === selectedId.value));

onMounted(() => {
    loadAddresses();
});
</script>

<template>
    <div>
        <div v-if="activeAddress" 
             class="border border-rose-200 bg-rose-50/40 rounded-xl p-4 md:p-5 flex flex-col sm:flex-row justify-between items-start gap-4 group hover:border-rose-300 transition-all cursor-pointer shadow-sm hover:shadow-md"
             @click="isSelectorOpen = true">
            
            <div class="flex gap-3 md:gap-4 w-full">
                <div class="shrink-0 mt-1 w-8 h-8 md:w-10 md:h-10 rounded-full bg-white flex items-center justify-center text-rose-500 shadow-sm border border-rose-100">
                    <MapPin class="w-4 h-4 md:w-5 md:h-5" />
                </div>
                
                <div class="flex-1 min-w-0"> <div class="flex flex-wrap items-center gap-2 mb-1">
                        <p class="font-bold text-gray-900 text-sm md:text-base">{{ activeAddress.receiver_name }}</p>
                        <span v-if="activeAddress.is_default" class="bg-rose-100 text-rose-700 text-[9px] md:text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wide whitespace-nowrap">Default</span>
                        <span class="bg-gray-100 text-gray-600 text-[9px] md:text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wide whitespace-nowrap">{{ activeAddress.type }}</span>
                    </div>
                    <p class="text-gray-600 text-xs md:text-sm font-medium">{{ activeAddress.phone_number }}</p>
                    <p class="text-gray-500 text-xs md:text-sm mt-1 leading-relaxed line-clamp-2">
                        {{ activeAddress.full_address }}, {{ activeAddress.district?.name }}, {{ activeAddress.city?.name }}, {{ activeAddress.province?.name }} {{ activeAddress.postal_code }}
                    </p>
                </div>
            </div>
            
            <button class="w-full sm:w-auto text-center text-xs font-bold text-rose-600 bg-white px-4 py-2 rounded-lg border border-rose-200 shadow-sm hover:bg-rose-50 transition-colors">
                Change
            </button>
        </div>

        <div v-else class="text-center p-6 md:p-8 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 hover:bg-rose-50/30 hover:border-rose-300 transition-all cursor-pointer group" @click="openCreateForm">
            <div class="w-10 h-10 md:w-12 md:h-12 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm group-hover:scale-110 transition-transform">
                <Plus class="w-5 h-5 md:w-6 md:h-6 text-gray-400 group-hover:text-rose-500" />
            </div>
            <p class="text-gray-900 font-bold text-sm md:text-base">No Address Selected</p>
            <p class="text-gray-500 text-xs md:text-sm">Click here to add a new shipping address</p>
        </div>

        <div v-if="isSelectorOpen" class="fixed inset-0 z-[9999] flex items-center justify-center p-0 md:p-4">
            
            <div class="absolute inset-0 bg-white/80 backdrop-blur-sm transition-opacity" @click="isSelectorOpen = false"></div>

            <div class="relative w-full md:max-w-lg h-full md:h-auto md:max-h-[85vh] bg-white md:rounded-2xl shadow-2xl border-none md:border border-gray-100 flex flex-col animate-in slide-in-from-bottom duration-300 md:zoom-in-95">
                
                <div class="p-4 md:p-5 border-b border-gray-100 flex justify-between items-center bg-gradient-to-r from-gray-50 to-white md:rounded-t-2xl pt-safe-top">
                    <h3 class="text-lg font-bold text-gray-900">Select Address</h3>
                    <button @click="isSelectorOpen = false" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-4 md:p-5 space-y-3 bg-gray-50/50 custom-scrollbar pb-safe-bottom">
                    <div v-if="isLoading" class="text-center py-8">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-rose-600 mx-auto mb-2"></div>
                        <p class="text-gray-500 text-xs">Loading addresses...</p>
                    </div>

                    <div v-else v-for="addr in addresses" :key="addr.id" 
                         class="relative p-4 rounded-xl border-2 cursor-pointer transition-all duration-200 group bg-white"
                         :class="selectedId === addr.id 
                            ? 'border-rose-500 shadow-md ring-1 ring-rose-500' 
                            : 'border-transparent hover:border-rose-200 shadow-sm hover:shadow-md'"
                         @click="selectAddress(addr.id)">
                        
                        <div v-if="selectedId === addr.id" class="absolute top-4 right-4 text-rose-600 bg-rose-100 rounded-full p-1">
                            <Check class="w-4 h-4" />
                        </div>

                        <div class="absolute bottom-3 right-3 flex gap-2 md:opacity-0 md:group-hover:opacity-100 transition-opacity">
                            <button @click.stop="openEditForm(addr)" class="p-2 md:p-1.5 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg bg-gray-50 md:bg-transparent" title="Edit">
                                <Edit class="w-4 h-4" />
                            </button>
                            <button @click.stop="deleteAddress(addr.id)" class="p-2 md:p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg bg-gray-50 md:bg-transparent" title="Delete">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="pr-8">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <p class="font-bold text-gray-900 text-sm">{{ addr.receiver_name }}</p>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-gray-100 text-gray-600 uppercase">{{ addr.type }}</span>
                                <span v-if="addr.is_default" class="text-[10px] font-bold px-2 py-0.5 rounded bg-rose-100 text-rose-600 uppercase">Default</span>
                            </div>
                            <p class="text-gray-500 text-xs">{{ addr.phone_number }}</p>
                            <p class="text-gray-600 text-xs mt-2 leading-relaxed pb-6 md:pb-0">
                                {{ addr.full_address }}, {{ addr.district?.name }}, {{ addr.city?.name }}
                            </p>
                        </div>
                    </div>

                    <button @click="openCreateForm" class="w-full py-4 border-2 border-dashed border-rose-200 rounded-xl text-rose-500 font-bold hover:bg-rose-50 hover:border-rose-400 transition-all flex items-center justify-center gap-2 mt-4 active:scale-95">
                        <Plus class="w-5 h-5" /> Add New Address
                    </button>
                    
                    <div class="h-10 md:hidden"></div>
                </div>
            </div>
        </div>

        <AddressFormModal 
            :show="isFormOpen" 
            :address="editingAddress" 
            @close="isFormOpen = false" 
            @saved="handleSaved" 
        />
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

/* Safe Area Support for iPhone X+ */
.pt-safe-top { padding-top: env(safe-area-inset-top, 20px); }
.pb-safe-bottom { padding-bottom: env(safe-area-inset-bottom, 20px); }
</style>