import { computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useRoutine(props) {
    
    const groupedRoutines = computed(() => {
        if (!props.routines || !Array.isArray(props.routines)) return [];

        const groups = {};

        props.routines.forEach(routine => {
            // Key Grouping
            const safeCustomName = routine.custom_product_name || 'Unknown Product';
            const key = routine.product_id 
                ? `prod-${routine.product_id}` 
                : `manual-${safeCustomName.toLowerCase().trim()}`;

            if (!groups[key]) {
                groups[key] = {
                    // Data Utama (Langsung dari Controller yang sudah di format)
                    name: routine.name,
                    image_url: routine.image_url,
                    
                    // Data Tambahan (Pastikan nama key sama dengan controller)
                    brand_name: routine.brand_name,
                    category_name: routine.category_name,
                    tags: routine.tags || [],
                    
                    repeat_frequency: routine.repeat_frequency,
                    note: routine.note,
                    is_manual: !routine.product_id,
                    slots: [] 
                };
            }

            groups[key].slots.push({
                id: routine.id,
                reminder_time: routine.reminder_time,
                is_completed_today: !!routine.is_completed_today,
                is_reminder_active: !!routine.is_reminder_active,
                original_data: routine 
            });
        });

        // Sort Waktu
        return Object.values(groups).map(group => {
            group.slots.sort((a, b) => {
                if (!a.reminder_time) return 1;
                if (!b.reminder_time) return -1;
                return a.reminder_time.localeCompare(b.reminder_time);
            });
            return group;
        });
    });

    const progress = computed(() => {
        if (!props.routines || props.routines.length === 0) return 0;
        const completed = props.routines.filter(r => r.is_completed_today).length;
        return Math.round((completed / props.routines.length) * 100);
    });

    const toggleCheck = (id) => {
        router.post(`/routine/${id}/toggle`, {}, { preserveScroll: true });
    };

    const deleteSingle = (id, onSuccess) => {
        router.delete(`/routine/${id}`, { preserveScroll: true, onSuccess });
    };

    const deleteAll = (id, onSuccess) => {
        router.delete(`/routine/${id}?scope=all`, { preserveScroll: true, onSuccess });
    };

    return { groupedRoutines, progress, toggleCheck, deleteSingle, deleteAll };
}