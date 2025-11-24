<template>
    <section class="flex-1 overflow-auto">
        <div :style="{ minWidth: `${days * dayWidth}px` }">
            <div class="relative">
                <div class="grid" :style="gridTemplate">
                    <div
                        v-for="day in dateRange"
                        :key="day.iso"
                        class="min-h-full border-r border-slate-100 last:border-r-0"
                        :class="[
                            { 'bg-slate-50/60': day.date.getDay() === 0 || day.date.getDay() === 6 },
                            { 'bg-blue-50/60': day.isToday },
                        ]"
                    ></div>
                </div>

                <div class="absolute inset-0">
                    <div
                        v-for="room in rooms"
                        :key="room.id"
                        :data-room-id="room.id"
                        class="relative border-b border-slate-100"
                        @drop="handleDrop($event, room.id)"
                        @dragover.prevent
                        @dragenter.prevent
                    >
                        <ReservationBlock
                            v-for="reservation in reservationsByRoom.get(room.id) ?? []"
                            :key="reservation.id"
                            :reservation="reservation"
                            :day-width="dayWidth"
                            :visible-days="days"
                            @update="handleReservationUpdate"
                            @drag-start="handleDragStart"
                            @edit="handleEditReservation"
                            @cancel="handleCancelReservation"
                        />
                    </div>
                </div>

                <div class="relative">
                    <div
                        v-for="room in rooms"
                        :key="room.id"
                        class="h-16 border-b border-slate-100 last:border-b-0"
                    ></div>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { addDays } from 'date-fns';
import { computed } from 'vue';
import ReservationBlock from './ReservationBlock.vue';
import { buildDateRange, calculateOffset, daysBetween } from '../utils/calendar';

const props = defineProps({
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
});

const emit = defineEmits(['update-reservation', 'edit-reservation', 'cancel-reservation']);

let draggedReservation = null;

const dateRange = computed(() => buildDateRange(props.startDate, props.days));

const gridTemplate = computed(() => ({
    display: 'grid',
    gridTemplateColumns: `repeat(${props.days}, ${props.dayWidth}px)`,
}));

const reservationsByRoom = computed(() => {
    const lookup = new Map();

    props.reservations.forEach((reservation) => {
        const rawOffset = calculateOffset(props.startDate, reservation.checkIn);
        const duration = daysBetween(reservation.checkIn, reservation.checkOut);

        const visibleOffset = Math.max(0, rawOffset);
        const overflowBefore = rawOffset < 0 ? Math.abs(rawOffset) : 0;
        const visibleLength = Math.min(duration - overflowBefore, props.days - visibleOffset);

        if (visibleLength <= 0) {
            return;
        }

        const block = {
            ...reservation,
            offset: visibleOffset,
            length: visibleLength,
            duration,
            rawOffset,
        };

        if (!lookup.has(reservation.roomId)) {
            lookup.set(reservation.roomId, []);
        }

        lookup.get(reservation.roomId).push(block);
    });

    lookup.forEach((blocks) => {
        blocks.sort((a, b) => a.offset - b.offset);
    });

    return lookup;
});

function handleReservationUpdate(payload) {
    const { id, offset, duration } = payload;
    const newCheckIn = addDays(props.startDate, offset);
    const newCheckOut = addDays(newCheckIn, duration);

    emit('update-reservation', {
        id,
        roomId: payload.roomId,
        checkIn: newCheckIn,
        checkOut: newCheckOut,
    });
}

function handleDragStart(reservation) {
    draggedReservation = reservation;
}

function handleDrop(event, targetRoomId) {
    if (!draggedReservation) return;

    const rect = event.currentTarget.getBoundingClientRect();
    const x = event.clientX - rect.left;
    const offset = Math.max(0, Math.floor(x / props.dayWidth));

    const currentOffset = draggedReservation.offset || 0;
    if (draggedReservation.roomId !== targetRoomId || currentOffset !== offset) {
        const newCheckIn = addDays(props.startDate, offset);
        const duration = draggedReservation.duration || 1;
        const newCheckOut = addDays(newCheckIn, duration);

        emit('update-reservation', {
            id: draggedReservation.id,
            roomId: targetRoomId,
            checkIn: newCheckIn,
            checkOut: newCheckOut,
        });
    }

    draggedReservation = null;
}

function handleEditReservation(reservation) {
    emit('edit-reservation', reservation);
}

function handleCancelReservation(reservation) {
    emit('cancel-reservation', reservation);
}
</script>

<style scoped>
.relative > .relative {
    min-height: 64px;
}
</style>

