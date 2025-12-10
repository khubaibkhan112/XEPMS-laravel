<template>
    <div class="flex border-b border-slate-200 bg-white/70">
        <div class="w-48 shrink-0 border-r border-slate-200 px-4 py-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Availability</p>
            <p class="text-xs text-slate-400">Rooms remaining</p>
        </div>
        <div class="flex-1 overflow-x-auto">
            <div class="grid text-center text-xs font-medium text-slate-600" :style="gridTemplate">
                <div
                    v-for="day in availability"
                    :key="day.iso"
                    class="flex min-h-[40px] flex-col items-center justify-center gap-1 border-r border-slate-200 px-2 py-2 last:border-r-0"
                >
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                        {{ day.available }}/{{ day.total }}
                    </span>
                    <span class="text-[11px] text-slate-400">{{ day.occupancy }}% occ</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    availability: {
        type: Array,
        default: () => [],
    },
    dayWidth: {
        type: Number,
        default: 140,
    },
    days: {
        type: Number,
        default: 7,
    },
});

const gridTemplate = computed(() => ({
    display: 'grid',
    gridTemplateColumns: `repeat(${props.days}, ${props.dayWidth}px)`,
}));
</script>







