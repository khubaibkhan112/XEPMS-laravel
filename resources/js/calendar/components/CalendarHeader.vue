<template>
    <header class="flex border-b border-slate-200 bg-slate-50">
        <div class="w-48 shrink-0 border-r border-slate-200 px-4 py-3">
            <p class="text-sm font-semibold text-slate-700">Rooms</p>
            <p class="text-xs uppercase tracking-wide text-slate-400">{{ subtitle }}</p>
        </div>
        <div class="flex-1 overflow-x-auto">
            <div class="grid" :style="gridTemplate">
                <div
                    v-for="day in dateRange"
                    :key="day.iso"
                    class="border-r border-slate-200 px-3 py-3 text-center last:border-r-0"
                    :class="{ 'bg-blue-50/60': day.isToday }"
                >
                    <p class="text-sm font-semibold text-slate-800">{{ day.formatted }}</p>
                    <p class="text-xs uppercase tracking-wide text-slate-500">{{ day.weekday }}</p>
                </div>
            </div>
        </div>
    </header>
</template>

<script setup>
import { computed } from 'vue';
import { buildDateRange } from '../utils/calendar';

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
    viewMode: {
        type: String,
        default: 'week',
    },
});

const dateRange = computed(() => buildDateRange(props.startDate, props.days));

const gridTemplate = computed(() => ({
    display: 'grid',
    gridTemplateColumns: `repeat(${props.days}, ${props.dayWidth}px)`,
}));

const subtitle = computed(() => {
    switch (props.viewMode) {
        case 'day':
            return 'Daily view';
        case 'month':
            return 'Monthly view';
        default:
            return 'Weekly view';
    }
});
</script>

