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
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-white mb-2">Find Your Perfect Stay</h2>
                <p class="text-blue-100 mb-8">Search thousands of properties worldwide</p>
                <div class="bg-white rounded-xl shadow-2xl p-6 md:p-8">
                    <form @submit.prevent="searchProperties" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Search
                                </span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <input
                                    v-model="searchForm.search"
                                    type="text"
                                    placeholder="Property name or location"
                                    class="w-full pl-10 pr-4 py-3 rounded-lg border-2 border-gray-200 bg-gray-50 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                />
                            </div>
                        </div>
                        <div class="relative">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    Location
                                </span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <input
                                    v-model="searchForm.location"
                                    type="text"
                                    placeholder="City or address"
                                    class="w-full pl-10 pr-4 py-3 rounded-lg border-2 border-gray-200 bg-gray-50 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-gray-300"
                                />
                            </div>
                        </div>
                        <div class="flex items-end">
                            <button
                                type="submit"
                                :disabled="loading"
                                class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white py-3 px-6 rounded-lg font-semibold shadow-lg hover:from-blue-700 hover:to-blue-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 transform hover:scale-105 active:scale-95 flex items-center justify-center gap-2"
                            >
                                <svg v-if="!loading" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <svg v-else class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
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

