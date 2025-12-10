<template>
    <div
        ref="blockRef"
        class="group relative pointer-events-none"
        :style="{ height: '64px' }"
    >
        <div
            class="absolute top-2 flex h-12 cursor-grab items-center overflow-hidden rounded-full text-xs text-white shadow-lg ring-1 ring-black/10 transition hover:shadow-xl pointer-events-auto"
            :class="[statusClass, { 'z-20 cursor-grabbing shadow-xl ring-2 ring-blue-300': isDragging }]"
            :style="styleObject"
            draggable="true"
            @mouseenter="handleMouseEnter"
            @mouseleave="handleMouseLeave"
            @mousemove="updateTooltipPosition"
            @dragstart="handleDragStart"
            @dragend="handleDragEnd"
            @dblclick="handleDoubleClick"
            @click="handleClick"
        >
            <div class="flex w-full items-center gap-2 px-4">
                <span class="inline-flex h-2 w-2 shrink-0 rounded-full bg-white/70"></span>
                <span class="truncate font-medium">{{ reservation.guestName }}</span>
            </div>
            <div class="hidden pr-4 text-[10px] uppercase tracking-wide text-white/80 sm:block">
                {{ reservation.source }}
            </div>
        </div>
        <ReservationTooltip
            v-if="showTooltip && !isDragging"
            :reservation="reservation"
            :show="showTooltip"
            :position="tooltipPosition"
            @cancel="handleCancel"
            @edit="handleEditFromTooltip"
        />
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { STATUS_CONFIG } from '../constants/statuses';
import ReservationTooltip from './ReservationTooltip.vue';

const props = defineProps({
    reservation: {
        type: Object,
        required: true,
    },
    dayWidth: {
        type: Number,
        default: 140,
    },
    visibleDays: {
        type: Number,
        default: 7,
    },
});

const emit = defineEmits(['update', 'drag-start', 'edit', 'cancel']);

const blockRef = ref(null);
const isDragging = ref(false);
const currentOffset = ref(props.reservation.offset || 0);
const showTooltip = ref(false);
const tooltipPosition = ref({ x: 0, y: 0 });

watch(
    () => props.reservation.offset,
    (newValue) => {
        if (!isDragging.value) {
            currentOffset.value = newValue || 0;
        }
    }
);

watch(() => showTooltip.value, (newValue) => {
    console.log('ReservationBlock: showTooltip changed to', newValue, 'for reservation', props.reservation.id);
});

const maxOffset = computed(() => Math.max(0, props.visibleDays - Math.max(props.reservation.length || 1, 1)));

const styleObject = computed(() => {
    const offsetPx = Math.max(0, (currentOffset.value || 0) * props.dayWidth);
    const widthPx = Math.max(props.dayWidth - 8, (props.reservation.length || 1) * props.dayWidth - 8);

    return {
        left: `${offsetPx}px`,
        width: `${widthPx}px`,
    };
});

const statusClass = computed(() => STATUS_CONFIG[props.reservation.status]?.colorClass ?? 'bg-slate-500');

function handleMouseEnter(event) {
    console.log('ReservationBlock: Mouse enter', {
        reservationId: props.reservation.id,
        guestName: props.reservation.guestName,
        event: event.type,
        target: event.target
    });
    showTooltip.value = true;
    console.log('ReservationBlock: showTooltip set to', showTooltip.value);
}

function handleMouseLeave(event) {
    console.log('ReservationBlock: Mouse leave', props.reservation.id);
    showTooltip.value = false;
}

function handleClick(event) {
    console.log('ReservationBlock: Click event', {
        reservationId: props.reservation.id,
        eventType: event.type,
        detail: event.detail,
        target: event.target
    });
    // Single click - don't do anything, let double-click handle edit
}

function handleDragStart(event) {
    console.log('ReservationBlock: Drag start', props.reservation.id);
    isDragging.value = true;
    showTooltip.value = false; // Hide tooltip when dragging
    emit('drag-start', props.reservation);
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', props.reservation.id.toString());
    
    // Create a custom drag image for better UX
    const dragImage = event.target.cloneNode(true);
    dragImage.style.opacity = '0.5';
    document.body.appendChild(dragImage);
    event.dataTransfer.setDragImage(dragImage, event.offsetX, event.offsetY);
    setTimeout(() => document.body.removeChild(dragImage), 0);
}

function handleDragEnd(event) {
    console.log('ReservationBlock: Drag end', props.reservation.id);
    isDragging.value = false;
}

function handleDoubleClick(event) {
    console.log('ReservationBlock: Double click', props.reservation.id, event);
    if (!isDragging.value) {
        console.log('ReservationBlock: Emitting edit event', props.reservation);
        emit('edit', props.reservation);
    } else {
        console.log('ReservationBlock: Ignoring edit - currently dragging');
    }
}

function handleEditFromTooltip(reservation) {
    console.log('ReservationBlock: Edit from tooltip', reservation.id);
    emit('edit', reservation);
}

function handleCancel(reservation) {
    console.log('ReservationBlock: Cancel', reservation.id);
    emit('cancel', reservation);
}

function updateTooltipPosition(event) {
    if (!blockRef.value) return;
    const rect = blockRef.value.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const spaceAbove = event.clientY;
    const spaceBelow = viewportHeight - event.clientY;
    
    tooltipPosition.value = {
        x: event.clientX,
        y: spaceAbove > spaceBelow ? event.clientY - 10 : event.clientY + 10,
    };
}

</script>

