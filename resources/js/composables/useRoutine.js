import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useRoutine(props) {
    
    // Grouping Logic
    const groupedRoutines = computed(() => {
        const groups = {};

        props.routines.forEach(routine => {
            const key = routine.product_id 
                ? `prod-${routine.product_id}` 
                : `manual-${routine.custom_product_name.toLowerCase().trim()}`;

            if (!groups[key]) {
                groups[key] = {
                    name: routine.name,
                    image_url: routine.image_url,
                    note: routine.note,
                    is_manual: !routine.product_id,
                    slots: [] 
                };
            }

            groups[key].slots.push({
                id: routine.id,
                reminder_time: routine.reminder_time,
                is_completed_today: routine.is_completed_today,
                is_reminder_active: routine.is_reminder_active,
                repeat_frequency: routine.repeat_frequency,
                step_order: routine.step_order,
                original_data: routine 
            });
        });

        // Sort slots by time
        Object.values(groups).forEach((group) => {
            group.slots.sort((a, b) => {
                if (!a.reminder_time) return 1;
                if (!b.reminder_time) return -1;
                return a.reminder_time.localeCompare(b.reminder_time);
            });
        });

        return Object.values(groups);
    });

    // Progress Logic
    const progress = computed(() => {
        const total = props.routines.length;
        if (total === 0) return 0;
        const completed = props.routines.filter(r => r.is_completed_today).length;
        return Math.round((completed / total) * 100);
    });

    // Actions
    const toggleCheck = (id) => {
        router.post(`/routine/${id}/toggle`, {}, { preserveScroll: true });
    };

    const deleteSingle = (id, onSuccess) => {
        router.delete(`/routine/${id}`, {
            onSuccess: onSuccess
        });
    };

    const deleteAll = (id, onSuccess) => {
        router.delete(`/routine/group/${id}`, {
            onSuccess: onSuccess
        });
    };

    return {
        groupedRoutines,
        progress,
        toggleCheck,
        deleteSingle,
        deleteAll
    };
}