<template>
    <div class="flex min-h-screen bg-slate-100">
        <SidebarNavigation />
        <div class="flex min-h-screen flex-1 flex-col">
            <TopNavbar />
            <header class="bg-white shadow-sm">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-4">
                    <div class="space-y-1">
                        <h1 class="text-xl font-semibold text-slate-900">Property Management</h1>
                        <p class="text-sm text-slate-500">Manage your properties and their settings.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus:visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                            type="button"
                            @click="showCreateModal = true"
                        >
                            + Add Property
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

                <div v-else-if="properties.length === 0" class="rounded-lg bg-white p-12 text-center shadow-sm">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-slate-900">No properties found</h3>
                    <p class="mt-2 text-sm text-slate-500">Get started by creating your first property.</p>
                    <button
                        class="mt-4 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                        type="button"
                        @click="showCreateModal = true"
                    >
                        + Add Property
                    </button>
                </div>

                <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="property in properties"
                        :key="property.id"
                        class="group relative rounded-lg border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-slate-900">{{ property.name }}</h3>
                                <p class="mt-1 text-sm text-slate-500">Code: {{ property.code }}</p>
                            </div>
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                    property.is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800',
                                ]"
                            >
                                {{ property.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="mt-4 space-y-2 text-sm text-slate-600">
                            <div class="flex items-center">
                                <svg class="mr-2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span>{{ property.timezone || 'UTC' }}</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="mr-2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span>{{ property.currency || 'GBP' }}</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="mr-2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span>{{ property.rooms_count || 0 }} Rooms</span>
                            </div>
                            <div class="flex items-center">
                                <svg class="mr-2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span>{{ property.reservations_count || 0 }} Reservations</span>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-2 border-t border-slate-200 pt-4">
                            <a
                                :href="`/admin/rooms?property_id=${property.id}`"
                                class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Manage Rooms
                            </a>
                            <button
                                class="rounded-md text-blue-600 px-3 py-1.5 text-sm font-medium transition hover:text-blue-900"
                                type="button"
                                @click="handleEdit(property)"
                            >
                                Edit
                            </button>
                            <button
                                class="rounded-md text-red-600 px-3 py-1.5 text-sm font-medium transition hover:text-red-900"
                                type="button"
                                @click="handleDelete(property)"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Create/Edit Property Modal -->
        <PropertyFormModal
            :property="selectedProperty"
            :show="showCreateModal || showEditModal"
            @close="handleCloseModal"
            @submit="handleSubmitProperty"
        />
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import SidebarNavigation from '../shared/components/SidebarNavigation.vue';
import TopNavbar from '../shared/components/TopNavbar.vue';
import PropertyFormModal from './components/PropertyFormModal.vue';

const properties = ref([]);
const selectedProperty = ref(null);
const showCreateModal = ref(false);
const showEditModal = ref(false);
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
        alert('Failed to load properties. Please try again.');
    } finally {
        loading.value = false;
    }
}

function handleEdit(property) {
    selectedProperty.value = property;
    showEditModal.value = true;
}

async function handleDelete(property) {
    if (!confirm(`Are you sure you want to delete property "${property.name}"? This action cannot be undone.`)) {
        return;
    }

    try {
        const response = await fetch(`/api/properties/${property.id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const result = await response.json();
        if (result.success) {
            alert('Property deleted successfully');
            await loadProperties();
        } else {
            alert(result.message || 'Failed to delete property');
        }
    } catch (error) {
        console.error('Error deleting property:', error);
        alert('Failed to delete property. Please try again.');
    }
}

function handleCloseModal() {
    showCreateModal.value = false;
    showEditModal.value = false;
    selectedProperty.value = null;
}

async function handleSubmitProperty(propertyData) {
    try {
        const url = selectedProperty.value ? `/api/properties/${selectedProperty.value.id}` : '/api/properties';
        const method = selectedProperty.value ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(propertyData),
        });

        const result = await response.json();
        if (result.success) {
            alert(selectedProperty.value ? 'Property updated successfully' : 'Property created successfully');
            handleCloseModal();
            await loadProperties();
        } else {
            alert(result.message || 'Failed to save property');
            if (result.errors) {
                console.error('Validation errors:', result.errors);
            }
        }
    } catch (error) {
        console.error('Error saving property:', error);
        alert('Failed to save property. Please try again.');
    }
}

onMounted(() => {
    loadProperties();
});
</script>


