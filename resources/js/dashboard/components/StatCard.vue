<template>
    <div class="rounded-lg bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-slate-600">{{ title }}</p>
                <p class="mt-2 text-2xl font-bold" :class="colorClass">{{ value }}</p>
                <div v-if="change !== null" class="mt-2 flex items-center text-sm" :class="changeClass">
                    <svg
                        class="mr-1 h-4 w-4"
                        :class="change > 0 ? 'rotate-0' : 'rotate-180'"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path d="M13 7l5 5m0 0l-5 5m5-5H6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span>{{ Math.abs(change) }}% {{ change > 0 ? 'increase' : 'decrease' }}</span>
                    <span class="ml-1 text-slate-500">vs last month</span>
                </div>
            </div>
            <div class="ml-4 flex h-12 w-12 items-center justify-center rounded-lg" :class="iconBgClass">
                <svg class="h-6 w-6" :class="iconColorClass" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path
                        v-if="icon === 'bed'"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        v-else-if="icon === 'currency'"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        v-else-if="icon === 'calendar'"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        v-else-if="icon === 'link'"
                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                    <path
                        v-else
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, required: true },
    value: { type: [String, Number], required: true },
    change: { type: Number, default: null },
    icon: { type: String, default: 'chart' },
    color: { type: String, default: 'blue' },
});

const colorClass = computed(() => {
    const classes = {
        blue: 'text-blue-600',
        green: 'text-green-600',
        purple: 'text-purple-600',
        orange: 'text-orange-600',
    };
    return classes[props.color] || 'text-slate-900';
});

const iconBgClass = computed(() => {
    const classes = {
        blue: 'bg-blue-50',
        green: 'bg-green-50',
        purple: 'bg-purple-50',
        orange: 'bg-orange-50',
    };
    return classes[props.color] || 'bg-slate-50';
});

const iconColorClass = computed(() => {
    const classes = {
        blue: 'text-blue-600',
        green: 'text-green-600',
        purple: 'text-purple-600',
        orange: 'text-orange-600',
    };
    return classes[props.color] || 'text-slate-600';
});

const changeClass = computed(() => {
    if (props.change === null) return 'text-slate-500';
    return props.change > 0 ? 'text-green-600' : 'text-red-600';
});
</script>

