<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Gift, AlertTriangle, ChevronDown, Loader2, Sparkles, Check, Package } from 'lucide-vue-next';
import Swal from 'sweetalert2';

// Props received from Parent
const props = defineProps({
    show: Boolean,           // Show status
    users: Array,              // Selected users (Set of IDs)
    rewards: Array,          // Reward data from DB
    submitUrl: String        // Target URL
});

const emit = defineEmits(['close', 'success']);

const selectedRewardId = ref('');
const isSending = ref(false);
const isDropdownOpen = ref(false);

// Helper to get the full object of the selected reward
const selectedReward = computed(() => {
    return props.rewards.find(r => r.id === selectedRewardId.value);
});

// Function to handle selection from custom dropdown
const selectReward = (id) => {
    selectedRewardId.value = id;
    isDropdownOpen.value = false;
};

const submitGift = () => {
    if (!selectedRewardId.value) return;

    Swal.fire({
        title: `Send Gift to ${props.users.size} Users?`,
        text: "Users will receive a notification and voucher code immediately.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Send Now!',
        confirmButtonColor: '#e11d48',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            isSending.value = true;
            
            router.post(props.submitUrl, {
                user_ids: Array.from(props.users),
                reward_id: selectedRewardId.value
            }, {
                onSuccess: (page) => {
                    isSending.value = false;
                    
                    // Access Flash Messages from Laravel
                    const flash = page.props.flash || {};

                    // CASE 1: Error (Flash Error)
                    if (flash.error) {
                        Swal.fire({
                            title: 'Failed!',
                            text: flash.error,
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                    } 
                    // CASE 2: Warning/Skipped (Flash Warning)
                    else if (flash.warning) {
                        emit('close');
                        emit('success');
                        Swal.fire({
                            title: 'Notice',
                            text: flash.warning,
                            icon: 'warning',
                            confirmButtonColor: '#f59e0b'
                        });
                    }
                    // CASE 3: Success
                    else {
                        selectedRewardId.value = '';
                        emit('close');
                        emit('success');
                        Swal.fire({
                            title: 'Success!',
                            text: flash.success || 'Gifts sent successfully.',
                            icon: 'success',
                            confirmButtonColor: '#10b981'
                        });
                    }
                },
                onError: (err) => {
                    isSending.value = false;
                    console.error(err);
                    Swal.fire('System Error', 'An error occurred while sending. Check console.', 'error');
                }
            });
        }
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

        <div class="relative bg-white w-full max-w-lg rounded-3xl shadow-2xl overflow-hidden animate-in zoom-in-95 duration-300">
            
            <div class="bg-gradient-to-r from-rose-50 to-pink-50 px-8 py-6 border-b border-rose-100 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-black text-rose-900 flex items-center gap-2">
                        <Gift class="w-6 h-6 text-rose-500" />
                        Send Surprise Gift
                    </h3>
                    <p class="text-sm text-rose-700/80 mt-1 font-medium">Target: {{ users.size }} Selected Users</p>
                </div>
                <button 
                    @click="$emit('close')" 
                    class="w-8 h-8 rounded-full bg-white/50 hover:bg-white text-rose-400 hover:text-rose-600 flex items-center justify-center transition-all shadow-sm"
                >
                    ✕
                </button>
            </div>
            
            <div class="p-8 space-y-6">
                
                <div v-if="!rewards || rewards.length === 0" class="flex flex-col items-center justify-center text-center py-8 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200">
                    <div class="w-12 h-12 bg-rose-100 rounded-full flex items-center justify-center mb-3 text-rose-500">
                        <AlertTriangle class="w-6 h-6" />
                    </div>
                    <h4 class="font-bold text-slate-900">No Rewards Available</h4>
                    <p class="text-xs text-slate-500 mt-1 max-w-xs">Please create a 'Claim Only' reward in the Marketing menu first.</p>
                </div>

                <div v-else>
                    
                    <div class="relative">
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 ml-1">Select Reward</label>
                        
                        <button 
                            @click="isDropdownOpen = !isDropdownOpen"
                            class="w-full flex items-center justify-between bg-white border border-slate-200 rounded-xl px-4 py-3.5 text-left focus:ring-4 focus:ring-rose-500/10 focus:border-rose-500 transition-all shadow-sm hover:border-slate-300"
                        >
                            <span v-if="selectedReward" class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center text-rose-500">
                                    <Gift class="w-4 h-4" />
                                </div>
                                <span class="block text-sm font-bold text-slate-800">{{ selectedReward.name }}</span>
                            </span>
                            <span v-else class="text-sm text-slate-400 font-medium flex items-center gap-2">
                                <Package class="w-4 h-4" /> Choose a gift...
                            </span>
                            <ChevronDown class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': isDropdownOpen }" />
                        </button>

                        <div v-if="isDropdownOpen" class="absolute z-50 mt-2 w-full bg-white rounded-xl shadow-xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in-95 duration-100 max-h-60 overflow-y-auto custom-scrollbar">
                            <div 
                                v-for="reward in rewards" 
                                :key="reward.id"
                                @click="selectReward(reward.id)"
                                class="flex items-center justify-between px-4 py-3 hover:bg-rose-50 cursor-pointer border-b border-slate-50 last:border-0 group transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 group-hover:bg-white group-hover:text-rose-500 transition-colors">
                                        <Gift class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 group-hover:text-rose-700">{{ reward.name }}</p>
                                        <p class="text-[10px] text-slate-400 font-medium">Stock: {{ reward.stock }} available</p>
                                    </div>
                                </div>
                                <Check v-if="selectedRewardId === reward.id" class="w-4 h-4 text-rose-600" />
                            </div>
                        </div>
                        
                        <div v-if="isDropdownOpen" @click="isDropdownOpen = false" class="fixed inset-0 z-40 cursor-default"></div>
                    </div>
                    
                    <div v-if="selectedReward" class="mt-4 p-4 bg-gradient-to-br from-slate-50 to-white rounded-xl border border-slate-200 flex gap-3 animate-in fade-in slide-in-from-top-1">
                        <Sparkles class="w-5 h-5 text-yellow-500 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-1">Gift Description</p>
                            <p class="text-sm text-slate-600 leading-relaxed italic">
                                "{{ selectedReward.description || 'Special gift for our loyal customers.' }}"
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Recipients List</span>
                        <span class="text-xs font-bold text-rose-600 bg-rose-50 px-2.5 py-1 rounded-full border border-rose-100">
                            {{ users.size }} Selected
                        </span>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <div class="flex -space-x-3 overflow-hidden py-1 pl-1">
                            <div v-for="uid in Array.from(users).slice(0, 5)" :key="uid" 
                                 class="inline-flex h-10 w-10 rounded-full ring-2 ring-white bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center shadow-md text-xs font-black text-slate-600">
                                 U{{ uid }}
                            </div>
                            <div v-if="users.size > 5" class="inline-flex h-10 w-10 rounded-full ring-2 ring-white bg-slate-800 flex items-center justify-center shadow-md z-10">
                                <span class="text-xs font-bold text-white">+{{ users.size - 5 }}</span>
                            </div>
                        </div>
                        
                        <div class="h-8 w-px bg-slate-200"></div>
                        
                        <p class="text-xs text-slate-500 leading-tight">
                            Sending to <strong class="text-slate-800">{{ users.size }} people</strong>. <br>
                            They will be notified instantly.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="px-8 py-5 bg-slate-50 border-t border-slate-100 flex gap-3">
                <button 
                    @click="$emit('close')" 
                    class="flex-1 py-3 border border-slate-200 bg-white rounded-xl text-slate-600 font-bold hover:bg-slate-100 hover:text-slate-800 transition-all text-sm"
                >
                    Cancel
                </button>
                <button 
                    @click="submitGift" 
                    :disabled="isSending || !selectedRewardId" 
                    class="flex-1 py-3 bg-gradient-to-r from-rose-500 to-pink-600 text-white rounded-xl font-bold hover:shadow-lg hover:shadow-rose-200 hover:-translate-y-0.5 active:translate-y-0 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 text-sm"
                >
                    <Loader2 v-if="isSending" class="w-4 h-4 animate-spin" />
                    <Gift v-else class="w-4 h-4" />
                    {{ isSending ? 'Sending Gifts...' : 'Send Gift Now' }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f5f9;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
</style>