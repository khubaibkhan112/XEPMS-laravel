<template>
    <aside class="flex w-64 flex-col border-r border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <p class="text-2xl font-bold text-blue-600">XEPMS</p>
        </div>
        <nav class="flex-1 overflow-y-auto px-2 py-4 text-sm font-medium text-slate-600">
            <div class="px-2 text-xs uppercase tracking-wide text-slate-400">Main</div>
            <a
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                :class="isActive('/admin/dashboard') ? 'bg-blue-50 text-blue-700' : ''"
                href="/admin/dashboard"
            >
                <span>Dashboard</span>
            </a>
            <a
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                :class="isActive('/admin/calendar') ? 'bg-blue-50 text-blue-700' : ''"
                href="/admin/calendar"
            >
                <span>Calendar</span>
                <span class="rounded-full bg-blue-600 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">
                    Live
                </span>
            </a>
            <a
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                :class="isActive('/admin/reservations') ? 'bg-blue-50 text-blue-700' : ''"
                href="/admin/calendar"
            >
                <span>Reservations</span>
            </a>
            <a
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                :class="isActive('/admin/availability') ? 'bg-blue-50 text-blue-700' : ''"
                href="/admin/calendar"
            >
                <span>Availability</span>
            </a>
            <a
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                :class="isActive('/admin/channels') ? 'bg-blue-50 text-blue-700' : ''"
                href="/admin/calendar"
            >
                <span>Channel Manager</span>
            </a>
            <a
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                :class="isActive('/admin/properties') ? 'bg-blue-50 text-blue-700' : ''"
                href="/admin/properties"
            >
                <span>Properties</span>
                <span class="rounded-full bg-green-600 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">
                    New
                </span>
            </a>
            <a
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                :class="isActive('/admin/staff') ? 'bg-blue-50 text-blue-700' : ''"
                href="/admin/staff"
            >
                <span>Staff Management</span>
            </a>
            
            <!-- Catalog / Room Management with submenu -->
            <div class="mt-1">
                <button
                    @click="toggleCatalogMenu"
                    class="flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                    :class="isCatalogActive() ? 'bg-blue-50 text-blue-700' : ''"
                    type="button"
                >
                    <span>Catalog</span>
                    <svg 
                        class="h-4 w-4 transition-transform"
                        :class="catalogMenuOpen ? 'rotate-90' : ''"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
                <div v-show="catalogMenuOpen" class="ml-4 mt-1 space-y-1 border-l-2 border-slate-200 pl-2">
                    <a
                        class="flex w-full items-center rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                        :class="isActive('/admin/room-types') ? 'bg-blue-50 text-blue-700' : ''"
                        href="/admin/room-types"
                    >
                        <span>Manage Room Types</span>
                    </a>
                    <a
                        class="flex w-full items-center rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                        :class="isActive('/admin/rooms') ? 'bg-blue-50 text-blue-700' : ''"
                        href="/admin/rooms"
                    >
                        <span>Manage Rooms</span>
                    </a>
                    <a
                        class="flex w-full items-center rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                        :class="isActive('/admin/room-features') ? 'bg-blue-50 text-blue-700' : ''"
                        href="/admin/room-features"
                    >
                        <span>Features</span>
                    </a>
                </div>
            </div>

            <div class="px-2 pt-6 text-xs uppercase tracking-wide text-slate-400">Reports</div>
            <a
                class="mt-1 flex w-full items-center justify-between rounded-lg px-4 py-2 text-left transition hover:bg-slate-50"
                :class="isActive('/admin/reports') ? 'bg-blue-50 text-blue-700' : ''"
                href="/admin/reports"
            >
                <span>Reports & Analytics</span>
            </a>
        </nav>
        <div class="border-t border-slate-200 px-4 py-4 text-sm">
            <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-3">
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Property</p>
                        <p v-if="currentProperty" class="mt-1 text-sm font-semibold text-slate-800 truncate">
                            {{ currentProperty.name }}
                        </p>
                        <p v-else class="mt-1 text-sm font-semibold text-slate-500">No property selected</p>
                        <p v-if="currentProperty" class="mt-0.5 text-xs text-slate-500">
                            {{ currentProperty.rooms_count || 0 }} rooms online
                        </p>
                    </div>
                    <button
                        class="ml-2 flex-shrink-0 rounded p-1 text-slate-400 transition hover:bg-slate-200 hover:text-slate-600"
                        type="button"
                        title="Change Property"
                        @click="showPropertyModal = true"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <PropertySelectorModal
            :show="showPropertyModal"
            @close="showPropertyModal = false"
            @selected="handlePropertySelected"
        />
    </aside>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { usePropertySelection } from '../composables/usePropertySelection';
import PropertySelectorModal from './PropertySelectorModal.vue';

const { selectedProperty, loadPropertyDetails } = usePropertySelection();
const currentProperty = ref(null);
const showPropertyModal = ref(false);
const catalogMenuOpen = ref(false);

function isActive(path) {
    return window.location.pathname === path;
}

function isCatalogActive() {
    return isActive('/admin/room-types') || isActive('/admin/rooms') || isActive('/admin/room-features');
}

function toggleCatalogMenu() {
    catalogMenuOpen.value = !catalogMenuOpen.value;
}

// Auto-open catalog menu if on a catalog page
onMounted(() => {
    if (isCatalogActive()) {
        catalogMenuOpen.value = true;
    }
});

function handlePropertySelected(property) {
    currentProperty.value = property;
    // Emit event for other components to listen
    window.dispatchEvent(new CustomEvent('property-changed', { detail: property }));
    // Reload page if on rooms page to refresh data
    if (window.location.pathname === '/admin/rooms') {
        window.location.reload();
    }
}

// Watch for property changes
watch(selectedProperty, (newProperty) => {
    currentProperty.value = newProperty;
}, { immediate: true });

// Load property details on mount
onMounted(async () => {
    await loadPropertyDetails();
    currentProperty.value = selectedProperty.value;
    
    // Auto-open catalog menu if on a catalog page
    if (isCatalogActive()) {
        catalogMenuOpen.value = true;
    }
});
</script>
