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
                            {{ room ? 'Edit Room' : 'Create New Room' }}
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
                                    Room Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="Room 101"
                                    required
                                    type="text"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="room_number">
                                    Room Number
                                </label>
                                <input
                                    id="room_number"
                                    v-model="form.room_number"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="101"
                                    type="text"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="room_type_id">
                                    Room Type
                                </label>
                                <select
                                    id="room_type_id"
                                    v-model="form.room_type_id"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option :value="null">Select Room Type</option>
                                    <option v-for="roomType in roomTypes" :key="roomType.id" :value="roomType.id">
                                        {{ roomType.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="status">
                                    Status
                                </label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="available">Available</option>
                                    <option value="occupied">Occupied</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="out_of_order">Out of Order</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="floor">
                                    Floor
                                </label>
                                <input
                                    id="floor"
                                    v-model="form.floor"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="1"
                                    type="text"
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
                            {{ room ? 'Update Room' : 'Create Room' }}
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
    room: {
        type: Object,
        default: null,
    },
    propertyId: {
        type: [String, Number],
        required: true,
    },
    roomTypes: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'submit']);

const form = reactive({
    property_id: props.propertyId,
    room_type_id: null,
    name: '',
    room_number: '',
    status: 'available',
    floor: '',
    max_occupancy: null,
    is_active: true,
});

watch(
    () => props.room,
    (newRoom) => {
        if (newRoom) {
            form.property_id = newRoom.property_id || props.propertyId;
            form.room_type_id = newRoom.room_type_id || null;
            form.name = newRoom.name || '';
            form.room_number = newRoom.room_number || '';
            form.status = newRoom.status || 'available';
            form.floor = newRoom.floor || '';
            form.max_occupancy = newRoom.max_occupancy || null;
            form.is_active = newRoom.is_active !== undefined ? newRoom.is_active : true;
        } else {
            // Reset form for new room
            form.property_id = props.propertyId;
            form.room_type_id = null;
            form.name = '';
            form.room_number = '';
            form.status = 'available';
            form.floor = '';
            form.max_occupancy = null;
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
        room_type_id: form.room_type_id || null,
        max_occupancy: form.max_occupancy || null,
    };
    emit('submit', submitData);
}
</script>

