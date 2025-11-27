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
                        <h2 class="text-xl font-semibold text-slate-900">
                            {{ roomType ? 'Edit Room Type' : 'Create New Room Type' }}
                        </h2>
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
                                <label class="block text-sm font-medium text-slate-700" for="name">
                                    Room Type Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="Standard Double"
                                    required
                                    type="text"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="code">
                                    Room Type Code
                                </label>
                                <input
                                    id="code"
                                    v-model="form.code"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="STD-DBL"
                                    type="text"
                                />
                                <p class="mt-1 text-xs text-slate-500">Unique identifier for this room type</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="base_occupancy">
                                    Base Occupancy
                                </label>
                                <input
                                    id="base_occupancy"
                                    v-model.number="form.base_occupancy"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    min="1"
                                    max="20"
                                    type="number"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="max_occupancy">
                                    Max Occupancy
                                </label>
                                <input
                                    id="max_occupancy"
                                    v-model.number="form.max_occupancy"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    min="1"
                                    max="20"
                                    type="number"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="base_rate">
                                    Base Rate
                                </label>
                                <input
                                    id="base_rate"
                                    v-model.number="form.base_rate"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    min="0"
                                    step="0.01"
                                    type="number"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="currency">
                                    Currency
                                </label>
                                <select
                                    id="currency"
                                    v-model="form.currency"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="GBP">GBP (£)</option>
                                    <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                    <option value="JPY">JPY (¥)</option>
                                    <option value="AUD">AUD ($)</option>
                                    <option value="CAD">CAD ($)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="description">
                                Description
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="Room type description..."
                                rows="3"
                            ></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="is_active">
                                <input
                                    id="is_active"
                                    v-model="form.is_active"
                                    class="mr-2 h-4 w-4 rounded border-slate-300"
                                    type="checkbox"
                                />
                                Active
                            </label>
                            <p class="mt-1 text-xs text-slate-500">Inactive room types will not appear in availability searches</p>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                        <button
                            class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                            type="button"
                            @click="handleClose"
                        >
                            Cancel
                        </button>
                        <button
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus:visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                            type="submit"
                        >
                            {{ roomType ? 'Update Room Type' : 'Create Room Type' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    roomType: {
        type: Object,
        default: null,
    },
    propertyId: {
        type: [String, Number],
        required: true,
    },
});

const emit = defineEmits(['close', 'submit']);

const form = reactive({
    property_id: props.propertyId,
    name: '',
    code: '',
    base_occupancy: 2,
    max_occupancy: 2,
    base_rate: null,
    currency: 'GBP',
    description: '',
    is_active: true,
});

watch(
    () => props.roomType,
    (newRoomType) => {
        if (newRoomType) {
            form.property_id = newRoomType.property_id || props.propertyId;
            form.name = newRoomType.name || '';
            form.code = newRoomType.code || '';
            form.base_occupancy = newRoomType.base_occupancy || 2;
            form.max_occupancy = newRoomType.max_occupancy || 2;
            form.base_rate = newRoomType.base_rate || null;
            form.currency = newRoomType.currency || 'GBP';
            form.description = newRoomType.description || '';
            form.is_active = newRoomType.is_active !== undefined ? newRoomType.is_active : true;
        } else {
            // Reset form for new room type
            form.property_id = props.propertyId;
            form.name = '';
            form.code = '';
            form.base_occupancy = 2;
            form.max_occupancy = 2;
            form.base_rate = null;
            form.currency = 'GBP';
            form.description = '';
            form.is_active = true;
        }
    },
    { immediate: true }
);

watch(
    () => props.propertyId,
    (newPropertyId) => {
        form.property_id = newPropertyId;
    }
);

function handleClose() {
    emit('close');
}

function handleSubmit() {
    const submitData = {
        ...form,
        base_occupancy: form.base_occupancy || null,
        max_occupancy: form.max_occupancy || null,
        base_rate: form.base_rate || null,
    };
    emit('submit', submitData);
}
</script>


