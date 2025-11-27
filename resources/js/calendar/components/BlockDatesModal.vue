<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 overflow-y-auto"
            @click.self="handleClose"
        >
            <div class="w-full max-w-2xl rounded-lg bg-white shadow-xl my-8 max-h-[90vh] overflow-y-auto">
                <div class="border-b border-slate-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-slate-900">Block Dates</h2>
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

                <form @submit.prevent="handleSubmit" class="px-6 py-4">
                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="blockStartDate">
                                    Start Date <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="blockStartDate"
                                    v-model="form.startDate"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    required
                                    type="date"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="blockEndDate">
                                    End Date <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="blockEndDate"
                                    v-model="form.endDate"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    required
                                    type="date"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="blockRoom">
                                Room
                            </label>
                            <select
                                id="blockRoom"
                                v-model="form.roomId"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            >
                                <option value="">All Rooms</option>
                                <option v-for="room in rooms" :key="room.id" :value="room.id">
                                    {{ room.name }} - {{ room.type }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="blockReason">
                                Reason
                            </label>
                            <input
                                id="blockReason"
                                v-model="form.reason"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="e.g., Maintenance, Renovation, etc."
                                type="text"
                            />
                        </div>

                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                            <p class="text-sm text-amber-800">
                                <svg class="mr-2 inline h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                                Existing reservations will not be affected. Only future availability will be blocked.
                            </p>
                        </div>

                        <div v-if="error" class="rounded-lg bg-red-50 p-3 text-sm text-red-600">
                            {{ error }}
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4">
                        <button
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            type="button"
                            @click="handleClose"
                        >
                            Cancel
                        </button>
                        <button
                            :disabled="loading"
                            class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            type="submit"
                        >
                            <span v-if="!loading">Block Dates</span>
                            <span v-else class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle
                                        class="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                    ></circle>
                                    <path
                                        class="opacity-75"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                        fill="currentColor"
                                    ></path>
                                </svg>
                                Blocking...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import { availabilityService } from '../services/availabilityService';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    rooms: {
        type: Array,
        default: () => [],
    },
    propertyId: {
        type: Number,
        default: 1,
    },
});

const emit = defineEmits(['close', 'submit']);

const form = ref({
    startDate: '',
    endDate: '',
    roomId: '',
    reason: 'Maintenance',
});

const loading = ref(false);
const error = ref('');

watch(
    () => props.show,
    (isOpen) => {
        if (!isOpen) {
            form.value = {
                startDate: '',
                endDate: '',
                roomId: '',
                reason: 'Maintenance',
            };
            error.value = '';
        }
    }
);

function handleClose() {
    emit('close');
}

async function handleSubmit() {
    error.value = '';

    if (new Date(form.value.startDate) >= new Date(form.value.endDate)) {
        error.value = 'End date must be after start date.';
        return;
    }

    loading.value = true;

    try {
        const result = await availabilityService.blockDates({
            property_id: props.propertyId,
            start_date: form.value.startDate,
            end_date: form.value.endDate,
            room_id: form.value.roomId || null,
            reason: form.value.reason,
        });

        emit('submit', result.data);
        handleClose();
    } catch (err) {
        error.value = err.response?.data?.message || 'Failed to block dates. Please try again.';
        console.error('Block dates error:', err);
    } finally {
        loading.value = false;
    }
}
</script>



