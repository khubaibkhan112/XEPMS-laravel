<template>
    <div class="flex items-center justify-between rounded-lg border border-slate-200 p-3">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg" :class="statusBgClass">
                <div class="h-2 w-2 rounded-full" :class="statusDotClass"></div>
            </div>
            <div>
                <p class="text-sm font-medium text-slate-900">{{ name }}</p>
                <p class="text-xs text-slate-500">{{ lastSync }}</p>
            </div>
        </div>
        <div class="text-right">
            <p class="text-sm font-semibold text-slate-900">{{ reservations }}</p>
            <p class="text-xs text-slate-500">reservations</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, required: true },
    status: { type: String, required: true },
    lastSync: { type: String, required: true },
    reservations: { type: Number, default: 0 },
});

const statusBgClass = computed(() => {
    const classes = {
        connected: 'bg-green-50',
        pending: 'bg-yellow-50',
        disconnected: 'bg-red-50',
    };
    return classes[props.status] || 'bg-slate-50';
});

const statusDotClass = computed(() => {
    const classes = {
        connected: 'bg-green-500',
        pending: 'bg-yellow-500',
        disconnected: 'bg-red-500',
    };
    return classes[props.status] || 'bg-slate-500';
});
</script>


