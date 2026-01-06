<script setup>
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/layouts/DashboardLayout.vue';
import { ref } from 'vue';
import { Plus, Trash2 } from 'lucide-vue-next';
import { useRoutine } from '@/composables/useRoutine';

// Components
import RoutineCard from '@/components/RoutineCard.vue';
import RoutineFormModal from '@/components/RoutineFormModal.vue';

defineOptions({ layout: DashboardLayout });

const props = defineProps({
    routines: Array,
    storeProducts: Array
});

const { groupedRoutines, progress, toggleCheck, deleteSingle, deleteAll } = useRoutine(props);

// State
const showFormModal = ref(false);
const showDeleteModal = ref(false);
const isEditMode = ref(false);
const editingId = ref(null);
const editingData = ref(null);

const deleteData = ref({ type: 'single', id: null, name: '' });

// Actions
const openAdd = () => {
    isEditMode.value = false;
    editingId.value = null;
    editingData.value = null;
    showFormModal.value = true;
};

const openEdit = (slot, groupName) => {
    isEditMode.value = true;
    editingId.value = slot.id;
    editingData.value = { ...slot.original_data, name: groupName };
    showFormModal.value = true;
};

const confirmDelete = (slot, groupName, type = 'single') => {
    deleteData.value = { type, id: slot.id, name: groupName };
    showDeleteModal.value = true;
};

const handleDelete = () => {
    const onSuccess = () => { showDeleteModal.value = false; showFormModal.value = false; };
    if (deleteData.value.type === 'single') {
        deleteSingle(deleteData.value.id, onSuccess);
    } else {
        deleteAll(deleteData.value.id, onSuccess);
    }
};
</script>

<template>
    <Head title="Skincare Routine" />
    
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8 pb-32">
        
        <div class="bg-gradient-to-r from-rose-500 to-pink-600 rounded-3xl p-6 sm:p-8 text-white shadow-lg mb-8 relative overflow-hidden">
            <div class="relative z-10 flex justify-between items-center">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold">My Routine</h1>
                    <p class="text-rose-100 text-sm sm:text-base mt-1">Consistency = Glowing Skin ✨</p>
                </div>
                <div class="text-center bg-white/20 backdrop-blur-sm rounded-2xl p-3 sm:p-4 min-w-[90px] border border-white/20">
                    <span class="block text-2xl sm:text-3xl font-bold">{{ progress }}%</span>
                    <span class="text-[10px] sm:text-xs uppercase tracking-wider opacity-90 font-bold">Today</span>
                </div>
            </div>
            <div class="absolute top-0 right-0 w-48 h-48 bg-white opacity-10 rounded-full blur-3xl -mr-10 -mt-10 pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 w-32 h-32 bg-rose-900 opacity-20 rounded-full blur-2xl -ml-10 -mb-10 pointer-events-none"></div>
        </div>

        <button 
            @click="openAdd"
            class="w-full bg-white border-2 border-dashed border-rose-300 text-rose-600 font-bold py-4 sm:py-5 rounded-2xl hover:bg-rose-50 hover:border-rose-400 hover:shadow-md transition-all flex items-center justify-center gap-2 mb-8 group"
        >
            <div class="bg-rose-100 p-1.5 rounded-full group-hover:bg-rose-200 transition-colors">
                <Plus class="w-5 h-5" />
            </div>
            <span class="text-base sm:text-lg">Add New Routine</span>
        </button>

        <div class="space-y-4 md:space-y-0 md:grid md:grid-cols-2 gap-6">
            <div v-if="groupedRoutines.length === 0" class="col-span-full text-center py-16 bg-gray-50 rounded-3xl border border-dashed border-gray-200">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-3 shadow-sm">
                    <Plus class="w-8 h-8 text-gray-300" />
                </div>
                <p class="text-gray-900 font-bold">No routines yet</p>
                <p class="text-gray-400 text-sm">Start your journey by adding a routine!</p>
            </div>

            <RoutineCard 
                v-for="(group, index) in groupedRoutines" 
                :key="index"
                :group="group"
                @toggle="toggleCheck"
                @edit="openEdit"
                @delete-single="(slot, name) => confirmDelete(slot, name, 'single')"
                @delete-all="(slot, name) => confirmDelete(slot, name, 'all')"
            />
        </div>

        <RoutineFormModal 
            :show="showFormModal"
            :is-edit="isEditMode"
            :editing-id="editingId"
            :initial-data="editingData"
            :store-products="storeProducts"
            @close="showFormModal = false"
            @delete="() => confirmDelete({id: editingId}, editingData.name, 'single')"
        />

        <div v-if="showDeleteModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="showDeleteModal = false"></div>
            
            <div class="relative bg-white rounded-2xl w-full max-w-sm p-6 text-center shadow-2xl animate-in zoom-in-95 duration-200">
                <div class="w-14 h-14 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Trash2 class="w-7 h-7" />
                </div>
                <h3 class="font-bold text-xl text-gray-900 mb-2">Delete Routine?</h3>
                <p class="text-sm text-gray-500 mb-6 leading-relaxed">
                    Are you sure you want to delete {{ deleteData.type === 'all' ? 'ALL schedules for' : 'this schedule for' }} 
                    <strong class="text-gray-900 block mt-1">{{ deleteData.name }}</strong>
                </p>
                <div class="space-y-3">
                    <button @click="handleDelete" class="w-full bg-red-600 text-white font-bold py-3.5 rounded-xl hover:bg-red-700 transition-colors shadow-lg shadow-red-200">
                        Yes, Delete It
                    </button>
                    <button @click="showDeleteModal = false" class="w-full bg-gray-100 text-gray-700 font-bold py-3.5 rounded-xl hover:bg-gray-200 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>

    </div>
</template>