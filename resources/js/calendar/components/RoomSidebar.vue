<template>
    <aside class="w-48 shrink-0 overflow-y-auto border-r border-slate-200 bg-white">
        <div v-for="room in rooms" :key="room.id" class="border-b border-slate-100 px-4 py-3 last:border-b-0">
            <p class="text-sm font-medium text-slate-800">{{ room.name }}</p>
            <p class="text-xs text-slate-500">{{ room.type }}</p>
        </div>
    </aside>
</template>

<script setup>
defineProps({
    rooms: {
        type: Array,
        default: () => [],
    },
});
</script>







