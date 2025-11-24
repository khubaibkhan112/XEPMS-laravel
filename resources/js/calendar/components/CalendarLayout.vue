<template>
    <section class="rounded-lg bg-white shadow-sm ring-1 ring-slate-200">
        <CalendarHeader :start-date="startDate" :days="days" :day-width="dayWidth" :view-mode="viewMode" />
        <AvailabilityRow
            v-if="showAvailability"
            :availability="availability"
            :day-width="dayWidth"
            :days="days"
        />
        <div class="flex max-h-[70vh] overflow-hidden">
            <RoomSidebar :rooms="rooms" />
            <CalendarGrid
                :rooms="rooms"
                :days="days"
                :start-date="startDate"
                :reservations="reservations"
                :day-width="dayWidth"
                :view-mode="viewMode"
                @update-reservation="emit('update-reservation', $event)"
                @edit-reservation="emit('edit-reservation', $event)"
                @cancel-reservation="emit('cancel-reservation', $event)"
            />
        </div>
    </section>
</template>

<script setup>
import CalendarGrid from './CalendarGrid.vue';
import CalendarHeader from './CalendarHeader.vue';
import RoomSidebar from './RoomSidebar.vue';
import AvailabilityRow from './AvailabilityRow.vue';

const emit = defineEmits(['update-reservation', 'edit-reservation', 'cancel-reservation']);

defineProps({
    startDate: {
        type: Date,
        required: true,
    },
    days: {
        type: Number,
        default: 14,
    },
    dayWidth: {
        type: Number,
        default: 140,
    },
    rooms: {
        type: Array,
        default: () => [],
    },
    reservations: {
        type: Array,
        default: () => [],
    },
    viewMode: {
        type: String,
        default: 'week',
    },
    availability: {
        type: Array,
        default: () => [],
    },
    showAvailability: {
        type: Boolean,
        default: true,
    },
});
</script>

