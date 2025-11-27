<template>
    <div class="flex min-h-screen bg-slate-100">
        <SidebarNavigation />
        <div class="flex min-h-screen flex-1 flex-col">
            <TopNavbar />
            <header class="bg-white shadow-sm">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-4">
                    <div class="space-y-1">
                        <h1 class="text-xl font-semibold text-slate-900">
                            Room Type Management
                            <span v-if="selectedProperty" class="ml-2 text-base font-normal text-slate-500">
                                - {{ selectedProperty.name }}
                            </span>
                        </h1>
                        <p class="text-sm text-slate-500">Manage room types and their configurations for your property.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-if="selectedPropertyId"
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus:visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                            type="button"
                            @click="showCreateModal = true"
                        >
                            + Add Room Type
                        </button>
                        <button
                            class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                            type="button"
                            @click="loadRoomTypes"
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
                    <h3 class="mt-4 text-lg font-medium text-slate-900">No Property Selected</h3>
                    <p class="mt-2 text-sm text-slate-500">Please select a property from the sidebar to view and manage its room types.</p>
                    <p class="mt-1 text-xs text-slate-400">Click the edit icon next to "Property" in the sidebar to select a property.</p>
                </div>

                <div v-else class="space-y-6">
                    <!-- Room Types List -->
                    <div class="rounded-lg bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-4">
                            <h2 class="text-lg font-semibold text-slate-900">
                                Room Types ({{ roomTypes.length }})
                            </h2>
                        </div>
                        <div v-if="roomTypes.length === 0" class="p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <h3 class="mt-4 text-lg font-medium text-slate-900">No room types found</h3>
                            <p class="mt-2 text-sm text-slate-500">Get started by creating a new room type.</p>
                            <button
                                class="mt-4 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                                type="button"
                                @click="showCreateModal = true"
                            >
                                + Add Room Type
                            </button>
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Code</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Occupancy</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Base Rate</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Rooms</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200 bg-white">
                                    <tr v-for="roomType in roomTypes" :key="roomType.id" class="hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">
                                            {{ roomType.name }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                            {{ roomType.code || '-' }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                            {{ roomType.base_occupancy || 0 }} - {{ roomType.max_occupancy || 0 }} guests
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                            <span v-if="roomType.base_rate">
                                                {{ roomType.currency || 'GBP' }} {{ parseFloat(roomType.base_rate).toFixed(2) }}
                                            </span>
                                            <span v-else>-</span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                                            {{ roomType.rooms_count || 0 }} rooms
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span
                                                :class="[
                                                    'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                                    roomType.is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800',
                                                ]"
                                            >
                                                {{ roomType.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <button
                                                class="mr-2 text-blue-600 hover:text-blue-900"
                                                type="button"
                                                @click="handleEdit(roomType)"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                class="text-red-600 hover:text-red-900"
                                                type="button"
                                                @click="handleDelete(roomType)"
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

        <!-- Create/Edit Room Type Modal -->
        <RoomTypeFormModal
            :property-id="selectedPropertyId"
            :room-type="selectedRoomType"
            :show="showCreateModal || showEditModal"
            @close="handleCloseModal"
            @submit="handleSubmitRoomType"
        />
    </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import SidebarNavigation from '../shared/components/SidebarNavigation.vue';
import TopNavbar from '../shared/components/TopNavbar.vue';
import { usePropertySelection } from '../shared/composables/usePropertySelection';
import RoomTypeFormModal from './components/RoomTypeFormModal.vue';

const { selectedPropertyId, selectedProperty, loadPropertyDetails } = usePropertySelection();
const roomTypes = ref([]);
const selectedRoomType = ref(null);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const loading = ref(false);

// Listen for property change events from sidebar
function handlePropertyChanged(event) {
    if (event.detail) {
        loadRoomTypes();
    }
}

async function loadRoomTypes() {
    if (!selectedPropertyId.value) return;

    try {
        loading.value = true;
        const response = await fetch(`/api/room-types?property_id=${selectedPropertyId.value}`);
        const result = await response.json();
        if (result.success) {
            roomTypes.value = result.data;
        } else {
            console.error('Failed to load room types:', result.message);
        }
    } catch (error) {
        console.error('Error loading room types:', error);
        alert('Failed to load room types. Please try again.');
    } finally {
        loading.value = false;
    }
}

function handleEdit(roomType) {
    selectedRoomType.value = roomType;
    showEditModal.value = true;
}

async function handleDelete(roomType) {
    if (!confirm(`Are you sure you want to delete room type "${roomType.name}"? This action cannot be undone.`)) {
        return;
    }

    try {
        const response = await fetch(`/api/room-types/${roomType.id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const result = await response.json();
        if (result.success) {
            alert('Room type deleted successfully');
            await loadRoomTypes();
        } else {
            alert(result.message || 'Failed to delete room type');
        }
    } catch (error) {
        console.error('Error deleting room type:', error);
        alert('Failed to delete room type. Please try again.');
    }
}

function handleCloseModal() {
    showCreateModal.value = false;
    showEditModal.value = false;
    selectedRoomType.value = null;
}

async function handleSubmitRoomType(roomTypeData) {
    try {
        const url = selectedRoomType.value ? `/api/room-types/${selectedRoomType.value.id}` : '/api/room-types';
        const method = selectedRoomType.value ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(roomTypeData),
        });

        const result = await response.json();
        if (result.success) {
            alert(selectedRoomType.value ? 'Room type updated successfully' : 'Room type created successfully');
            handleCloseModal();
            await loadRoomTypes();
        } else {
            alert(result.message || 'Failed to save room type');
            if (result.errors) {
                console.error('Validation errors:', result.errors);
            }
        }
    } catch (error) {
        console.error('Error saving room type:', error);
        alert('Failed to save room type. Please try again.');
    }
}

onMounted(async () => {
    // Load property details if we have a selected property ID
    await loadPropertyDetails();

    // If we have a selected property, load room types
    if (selectedPropertyId.value) {
        await loadRoomTypes();
    }

    // Listen for property changes
    window.addEventListener('property-changed', handlePropertyChanged);
});

// Watch for property ID changes
watch(selectedPropertyId, async (newId) => {
    if (newId) {
        await loadPropertyDetails();
        await loadRoomTypes();
    } else {
        roomTypes.value = [];
    }
});
</script>


