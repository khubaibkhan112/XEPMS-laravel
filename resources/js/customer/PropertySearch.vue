<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-bold text-gray-900">XEPMS</h1>
                    <a href="/login" class="text-blue-600 hover:text-blue-800">Admin Login</a>
                </div>
            </div>
        </header>

        <!-- Search Section -->
        <div class="bg-blue-600 py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-white mb-6">Find Your Perfect Stay</h2>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <form @submit.prevent="searchProperties" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input
                                v-model="searchForm.search"
                                type="text"
                                placeholder="Property name or location"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Location</label>
                            <input
                                v-model="searchForm.location"
                                type="text"
                                placeholder="City or address"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>
                        <div class="flex items-end">
                            <button
                                type="submit"
                                :disabled="loading"
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {{ loading ? 'Searching...' : 'Search' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Properties List -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div v-if="loading && properties.length === 0" class="text-center py-12">
                <p class="text-gray-500">Loading properties...</p>
            </div>

            <div v-else-if="properties.length === 0" class="text-center py-12">
                <p class="text-gray-500">No properties found. Try adjusting your search.</p>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div
                    v-for="property in properties"
                    :key="property.id"
                    class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-pointer"
                    @click="viewProperty(property.id)"
                >
                    <div class="h-48 bg-gray-200 flex items-center justify-center">
                        <span class="text-gray-400">No Image</span>
                    </div>
                    <div class="p-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ property.name }}</h3>
                        <p class="text-gray-600 mb-4">{{ property.address || 'Address not provided' }}</p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">
                                {{ property.rooms_count }} Room{{ property.rooms_count !== 1 ? 's' : '' }}
                            </span>
                            <span class="text-sm text-gray-500">
                                {{ property.room_types?.length || 0 }} Room Type{{ (property.room_types?.length || 0) !== 1 ? 's' : '' }}
                            </span>
                        </div>
                        <button
                            @click.stop="viewProperty(property.id)"
                            class="mt-4 w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700"
                        >
                            View Details
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const properties = ref([]);
const loading = ref(false);
const searchForm = ref({
    search: '',
    location: '',
});

const searchProperties = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams();
        if (searchForm.value.search) params.append('search', searchForm.value.search);
        if (searchForm.value.location) params.append('location', searchForm.value.location);

        const response = await fetch(`/public/api/properties/search?${params.toString()}`);
        const data = await response.json();

        if (data.success) {
            properties.value = data.data;
        }
    } catch (error) {
        console.error('Error searching properties:', error);
    } finally {
        loading.value = false;
    }
};

const loadAllProperties = async () => {
    loading.value = true;
    try {
        const response = await fetch('/public/api/properties');
        const data = await response.json();

        if (data.success) {
            properties.value = data.data;
        }
    } catch (error) {
        console.error('Error loading properties:', error);
    } finally {
        loading.value = false;
    }
};

const viewProperty = (propertyId) => {
    window.location.href = `/properties/${propertyId}`;
};

onMounted(() => {
    loadAllProperties();
});
</script>

