<template>
    <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
        <span class="font-semibold uppercase tracking-wide text-slate-400">Statuses:</span>
        <div
            v-for="status in statusEntries"
            :key="status.value"
            class="flex items-center gap-2"
        >
            <span :class="['block h-2.5 w-2.5 rounded-full', status.colorClass]"></span>
            <span>{{ status.label }}</span>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { STATUS_CONFIG } from '../constants/statuses';

const statusEntries = computed(() =>
    Object.entries(STATUS_CONFIG).map(([value, config]) => ({
        value,
        label: config.label,
        colorClass: config.colorClass,
    }))
);
</script>







