<template>
    <div
        ref="blockRef"
        class="group relative"
        @mouseenter="showTooltip = true"
        @mouseleave="showTooltip = false"
        @mousemove="updateTooltipPosition"
    >
        <div
            class="absolute top-2 flex h-12 cursor-grab items-center overflow-hidden rounded-full text-xs text-white shadow-lg ring-1 ring-black/10 transition"
            :class="[statusClass, { 'z-20 cursor-grabbing shadow-xl ring-2 ring-blue-300': isDragging }]"
            :style="styleObject"
            draggable="true"
            @pointerdown.prevent="onPointerDown"
            @dragstart="handleDragStart"
            @dblclick="handleDoubleClick"
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
        />
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
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
const dragDelta = ref(0);
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

let startX = 0;
let initialOffset = 0;

function handleDragStart(event) {
    emit('drag-start', props.reservation);
    event.dataTransfer.effectAllowed = 'move';
}

function handleDoubleClick() {
    if (!isDragging.value) {
        emit('edit', props.reservation);
    }
}

function handleCancel(reservation) {
    emit('cancel', reservation);
}

function onPointerDown(event) {
    isDragging.value = true;
    startX = event.clientX;
    initialOffset = currentOffset.value;
    dragDelta.value = 0;
    emit('drag-start', props.reservation);

    window.addEventListener('pointermove', onPointerMove);
    window.addEventListener('pointerup', onPointerUp);
}

function onPointerMove(event) {
    if (!isDragging.value) {
        return;
    }

    const deltaX = event.clientX - startX;
    const deltaDays = Math.round(deltaX / props.dayWidth);
    dragDelta.value = deltaDays;
    const nextOffset = Math.min(Math.max(0, initialOffset + deltaDays), maxOffset.value);
    currentOffset.value = nextOffset;
}

function onPointerUp() {
    if (!isDragging.value) {
        return;
    }

    isDragging.value = false;
    window.removeEventListener('pointermove', onPointerMove);
    window.removeEventListener('pointerup', onPointerUp);

    const finalOffset = Math.min(Math.max(0, initialOffset + dragDelta.value), maxOffset.value);
    currentOffset.value = finalOffset;
    dragDelta.value = 0;

    if (finalOffset !== (props.reservation.offset || 0)) {
        emit('update', {
            id: props.reservation.id,
            roomId: props.reservation.roomId,
            offset: finalOffset,
            duration: props.reservation.duration || 1,
        });
    } else {
        currentOffset.value = props.reservation.offset || 0;
    }
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

onBeforeUnmount(() => {
    window.removeEventListener('pointermove', onPointerMove);
    window.removeEventListener('pointerup', onPointerUp);
});
</script>

