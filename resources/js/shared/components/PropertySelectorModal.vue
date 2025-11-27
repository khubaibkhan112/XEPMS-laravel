<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 overflow-y-auto"
            @click.self="handleClose"
        >
            <div class="w-full max-w-lg rounded-lg bg-white shadow-xl my-8">
                <div class="border-b border-slate-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-slate-900">Select Property</h2>
                        <button
                            class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                            type="button"
                            @click="handleClose"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M6 18L18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="px-6 py-4">
                    <div v-if="loading" class="flex items-center justify-center py-8">
                        <svg class="h-6 w-6 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor"></path>
                        </svg>
                    </div>

                    <div v-else-if="properties.length === 0" class="py-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-slate-900">No properties found</h3>
                        <p class="mt-2 text-sm text-slate-500">Create your first property to get started.</p>
                        <a
                            class="mt-4 inline-block rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                            href="/admin/properties"
                        >
                            Go to Properties
                        </a>
                    </div>

                    <div v-else class="space-y-2 max-h-96 overflow-y-auto">
                        <button
                            v-for="property in properties"
                            :key="property.id"
                            :class="[
                                'w-full rounded-lg border p-4 text-left transition hover:bg-slate-50',
                                selectedPropertyId === property.id
                                    ? 'border-blue-500 bg-blue-50'
                                    : 'border-slate-200 bg-white',
                            ]"
                            type="button"
                            @click="handleSelect(property)"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-base font-semibold text-slate-900">{{ property.name }}</h3>
                                        <span
                                            v-if="selectedPropertyId === property.id"
                                            class="rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white"
                                        >
                                            Current
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-slate-500">Code: {{ property.code }}</p>
                                    <div class="mt-2 flex items-center gap-4 text-xs text-slate-600">
                                        <span>{{ property.rooms_count || 0 }} Rooms</span>
                                        <span>{{ property.reservations_count || 0 }} Reservations</span>
                                    </div>
                                </div>
                                <svg
                                    v-if="selectedPropertyId === property.id"
                                    class="h-5 w-5 text-blue-600"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    viewBox="0 0 24 24"
                                >
                                    <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </button>
                    </div>
                </div>

                <div class="border-t border-slate-200 px-6 py-4">
                    <div class="flex items-center justify-end gap-3">
                        <button
                            class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            type="button"
                            @click="handleClose"
                        >
                            Cancel
                        </button>
                        <a
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                            href="/admin/properties"
                        >
                            Manage Properties
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { usePropertySelection } from '../composables/usePropertySelection';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'selected']);

const { selectedPropertyId, setSelectedProperty } = usePropertySelection();
const properties = ref([]);
const loading = ref(false);

async function loadProperties() {
    try {
        loading.value = true;
        const response = await fetch('/api/properties');
        const result = await response.json();
        if (result.success) {
            properties.value = result.data;
        }
    } catch (error) {
        console.error('Error loading properties:', error);
    } finally {
        loading.value = false;
    }
}

function handleSelect(property) {
    setSelectedProperty(property);
    emit('selected', property);
    handleClose();
}

function handleClose() {
    emit('close');
}

watch(
    () => props.show,
    (newValue) => {
        if (newValue) {
            loadProperties();
        }
    }
);

onMounted(() => {
    if (props.show) {
        loadProperties();
    }
});
</script>


