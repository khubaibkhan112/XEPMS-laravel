<template>
    <div
        v-if="show"
        ref="tooltipRef"
        class="fixed z-[9998] rounded-lg bg-slate-900 px-4 py-3 text-xs text-white shadow-xl pointer-events-auto"
        :style="tooltipStyle"
    >
        <div class="space-y-2">
            <div class="flex items-center justify-between gap-4 border-b border-slate-700 pb-2">
                <span class="font-semibold">{{ reservation.guestName }}</span>
                <span
                    class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                    :class="statusBadgeClass"
                >
                    {{ reservation.status }}
                </span>
            </div>
            <div class="space-y-1.5 text-slate-300">
                <div class="flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span>{{ formatDateRange }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span>Room {{ reservation.roomNumber || reservation.roomId }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span>{{ reservation.adultCount }} Adult{{ reservation.adultCount !== 1 ? 's' : '' }}{{ reservation.childCount > 0 ? `, ${reservation.childCount} Child${reservation.childCount !== 1 ? 'ren' : ''}` : '' }}</span>
                </div>
                <div v-if="reservation.guestEmail" class="flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path
                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span class="truncate">{{ reservation.guestEmail }}</span>
                </div>
                <div v-if="reservation.guestPhone" class="flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path
                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span>{{ reservation.guestPhone }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span>{{ formatCurrency(reservation.totalAmount) }} {{ reservation.currency || 'GBP' }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <svg class="h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span class="uppercase">{{ reservation.source }}</span>
                </div>
                <div v-if="reservation.notes" class="border-t border-slate-700 pt-2 text-slate-400">
                    <p class="line-clamp-2">{{ reservation.notes }}</p>
                </div>
                <div v-if="reservation.status !== 'cancelled'" class="border-t border-slate-700 pt-2 space-y-2">
                    <button
                        class="w-full rounded bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-blue-700"
                        type="button"
                        @click="handleEdit"
                    >
                        Edit Reservation
                    </button>
                    <button
                        class="w-full rounded bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-red-700"
                        type="button"
                        @click="handleCancel"
                    >
                        Cancel Reservation
                    </button>
                </div>
            </div>
        </div>
        <div class="absolute -bottom-1 left-1/2 h-2 w-2 -translate-x-1/2 rotate-45 bg-slate-900"></div>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { format } from 'date-fns';
import { STATUS_CONFIG } from '../constants/statuses';

const props = defineProps({
    reservation: {
        type: Object,
        required: true,
    },
    show: {
        type: Boolean,
        default: false,
    },
    position: {
        type: Object,
        default: () => ({ x: 0, y: 0 }),
    },
});

const emit = defineEmits(['cancel', 'edit']);

const tooltipRef = ref(null);

watch(() => props.show, (newValue) => {
    console.log('ReservationTooltip: show prop changed to', newValue, 'for reservation', props.reservation?.id);
}, { immediate: true });

const tooltipStyle = computed(() => {
    return {
        left: `${props.position.x}px`,
        top: `${props.position.y}px`,
        transform: 'translateX(-50%) translateY(-100%)',
        marginTop: '-8px',
    };
});

const statusBadgeClass = computed(() => {
    const config = STATUS_CONFIG[props.reservation.status];
    return config?.badgeClass ?? 'bg-slate-600 text-white';
});

const formatDateRange = computed(() => {
    if (!props.reservation.checkIn || !props.reservation.checkOut) {
        return 'Dates TBD';
    }
    const checkIn = props.reservation.checkIn instanceof Date
        ? props.reservation.checkIn
        : new Date(props.reservation.checkIn);
    const checkOut = props.reservation.checkOut instanceof Date
        ? props.reservation.checkOut
        : new Date(props.reservation.checkOut);
    return `${format(checkIn, 'MMM d')} - ${format(checkOut, 'MMM d, yyyy')} (${props.reservation.nights || 1} night${props.reservation.nights !== 1 ? 's' : ''})`;
});

function formatCurrency(amount) {
    if (!amount) return '0.00';
    return new Intl.NumberFormat('en-GB', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

function handleEdit() {
    console.log('ReservationTooltip: Edit button clicked', props.reservation.id);
    emit('edit', props.reservation);
}

function handleCancel() {
    console.log('ReservationTooltip: Cancel button clicked', props.reservation.id);
    emit('cancel', props.reservation);
}
</script>

