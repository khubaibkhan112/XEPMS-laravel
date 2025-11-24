<template>
    <div class="flex min-h-screen bg-slate-100">
        <SidebarNavigation />
        <div class="flex min-h-screen flex-1 flex-col">
            <TopNavbar />
            <header class="bg-white shadow-sm">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-4">
                    <div class="space-y-1">
                        <h1 class="text-xl font-semibold text-slate-900">Room Management</h1>
                        <p class="text-sm text-slate-500">Manage rooms, room types, and properties for your accommodation.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <select
                            v-model="selectedPropertyId"
                            class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            @change="loadRooms"
                        >
                            <option value="">Select Property</option>
                            <option v-for="property in properties" :key="property.id" :value="property.id">
                                {{ property.name }}
                            </option>
                        </select>
                        <button
                            v-if="selectedPropertyId"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus:visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                            type="button"
                            @click="showCreateModal = true"
                        >
                            + Add Room
                        </button>
                        <button
                            class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                            type="button"
                            @click="loadProperties"
                        >
                            <svg class="mr-1.5 inline h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            Refresh
                        </button>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl flex-1 px-6 py-6">
                <div v-if="loading" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <svg class="mx-auto h-8 w-8 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor"></path>
                        </svg>
                        <p class="mt-2 text-sm text-slate-600">Loading...</p>
                    </div>
                </div>

                <div v-else-if="!selectedPropertyId" class="rounded-lg bg-white p-12 text-center shadow-sm">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-slate-900">Select a Property</h3>
                    <p class="mt-2 text-sm text-slate-500">Please select a property from the dropdown above to view and manage its rooms.</p>
                </div>

                <div v-else class="space-y-6">
                    <!-- Rooms List -->
                    <div class="rounded-lg bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900">
                                Rooms ({{ rooms.length }})
                            </h2>
                        </div>
                        <div v-if="rooms.length === 0" class="p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-slate-900">No rooms found</h3>
                            <p class="mt-2 text-sm text-slate-500">Get started by creating a new room.</p>
                            <button
                                class="mt-4 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                                type="button"
                                @click="showCreateModal = true"
                            >
                                + Add Room
                            </button>
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Room Number</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Room Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Floor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Max Occupancy</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    <tr v-for="room in rooms" :key="room.id" class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                            {{ room.room_number || '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900">
                                            {{ room.name }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                            {{ room.room_type?.name || '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span
                                                :class="[
                                                    'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                                    getStatusClass(room.status),
                                                ]"
                                            >
                                                {{ room.status }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                            {{ room.floor || '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                            {{ room.max_occupancy || '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <button
                                                class="mr-2 text-blue-600 hover:text-blue-900"
                                                type="button"
                                                @click="handleEdit(room)"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                class="text-red-600 hover:text-red-900"
                                                type="button"
                                                @click="handleDelete(room)"
                                            >
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Create/Edit Room Modal -->
        <RoomFormModal
            :property-id="selectedPropertyId"
            :room="selectedRoom"
            :room-types="roomTypes"
            :show="showCreateModal || showEditModal"
            @close="handleCloseModal"
            @submit="handleSubmitRoom"
        />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import SidebarNavigation from '../shared/components/SidebarNavigation.vue';
import TopNavbar from '../shared/components/TopNavbar.vue';
import RoomFormModal from './components/RoomFormModal.vue';

const properties = ref([]);
const rooms = ref([]);
const roomTypes = ref([]);
const selectedPropertyId = ref('');
const selectedRoom = ref(null);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const loading = ref(false);

function getStatusClass(status) {
    const classes = {
        available: 'bg-green-100 text-green-800',
        occupied: 'bg-blue-100 text-blue-800',
        maintenance: 'bg-yellow-100 text-yellow-800',
        out_of_order: 'bg-red-100 text-red-800',
    };
    return classes[status] || 'bg-slate-100 text-slate-800';
}

async function loadProperties() {
    try {
        loading.value = true;
        const response = await fetch('/api/properties');
        const result = await response.json();
        if (result.success) {
            properties.value = result.data;
            if (properties.value.length > 0 && !selectedPropertyId.value) {
                selectedPropertyId.value = properties.value[0].id;
                await loadRooms();
            }
        }
    } catch (error) {
        console.error('Error loading properties:', error);
        alert('Failed to load properties. Please try again.');
    } finally {
        loading.value = false;
    }
}

async function loadRooms() {
    if (!selectedPropertyId.value) return;

    try {
        loading.value = true;
        const response = await fetch(`/api/rooms?property_id=${selectedPropertyId.value}`);
        const result = await response.json();
        if (result.success) {
            rooms.value = result.data;
        }

        // Load room types for the property
        const roomTypesResponse = await fetch(`/api/room-types?property_id=${selectedPropertyId.value}`);
        const roomTypesResult = await roomTypesResponse.json();
        if (roomTypesResult.success) {
            roomTypes.value = roomTypesResult.data;
        }
    } catch (error) {
        console.error('Error loading rooms:', error);
        alert('Failed to load rooms. Please try again.');
    } finally {
        loading.value = false;
    }
}

function handleEdit(room) {
    selectedRoom.value = room;
    showEditModal.value = true;
}

async function handleDelete(room) {
    if (!confirm(`Are you sure you want to delete room "${room.name}"? This action cannot be undone.`)) {
        return;
    }

    try {
        const response = await fetch(`/api/rooms/${room.id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const result = await response.json();
        if (result.success) {
            alert('Room deleted successfully');
            await loadRooms();
        } else {
            alert(result.message || 'Failed to delete room');
        }
    } catch (error) {
        console.error('Error deleting room:', error);
        alert('Failed to delete room. Please try again.');
    }
}

function handleCloseModal() {
    showCreateModal.value = false;
    showEditModal.value = false;
    selectedRoom.value = null;
}

async function handleSubmitRoom(roomData) {
    try {
        const url = selectedRoom.value ? `/api/rooms/${selectedRoom.value.id}` : '/api/rooms';
        const method = selectedRoom.value ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(roomData),
        });

        const result = await response.json();
        if (result.success) {
            alert(selectedRoom.value ? 'Room updated successfully' : 'Room created successfully');
            handleCloseModal();
            await loadRooms();
        } else {
            alert(result.message || 'Failed to save room');
        }
    } catch (error) {
        console.error('Error saving room:', error);
        alert('Failed to save room. Please try again.');
    }
}

onMounted(() => {
    loadProperties();
});
</script>

