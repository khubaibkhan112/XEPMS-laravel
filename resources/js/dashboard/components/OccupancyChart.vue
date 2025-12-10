<template>
    <div class="h-64">
        <div class="flex h-full items-end justify-between gap-2">
            <div
                v-for="(item, index) in data"
                :key="index"
                class="flex flex-1 flex-col items-center gap-2"
            >
                <div class="relative flex w-full flex-col items-center justify-end" style="height: 200px;">
                    <div
                        class="w-full rounded-t transition hover:opacity-80"
                        :class="getBarColor(item.occupancy)"
                        :style="{ height: `${(item.occupancy / 100) * 200}px` }"
                    ></div>
                    <div class="absolute -bottom-6 text-xs font-medium text-slate-600">{{ item.day }}</div>
                </div>
                <div class="mt-8 text-center">
                    <p class="text-sm font-semibold text-slate-900">{{ item.occupancy }}%</p>
                    <p class="text-xs text-slate-500">{{ formatCurrency(item.revenue) }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    data: {
        type: Array,
        default: () => [],
    },
});

function getBarColor(occupancy) {
    if (occupancy >= 90) return 'bg-green-500';
    if (occupancy >= 75) return 'bg-blue-500';
    if (occupancy >= 50) return 'bg-yellow-500';
    return 'bg-orange-500';
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-GB', {
        style: 'currency',
        currency: 'GBP',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
}
</script>




