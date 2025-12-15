<template>
    <div class="flex min-h-screen bg-slate-100">
        <SidebarNavigation />
        <div class="flex min-h-screen flex-1 flex-col">
            <TopNavbar />
            <header class="bg-white shadow-sm">
                <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-4">
                    <div class="space-y-1">
                        <h1 class="text-xl font-semibold text-slate-900">Staff Management</h1>
                        <p class="text-sm text-slate-500">Manage staff members, roles, and permissions.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 focus:visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                            type="button"
                            @click="showCreateModal = true"
                        >
                            + Add Staff
                        </button>
                        <button
                            class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                            type="button"
                            @click="loadStaff"
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
                <!-- Filters -->
                <div class="mb-4 rounded-lg bg-white p-4 shadow-sm">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Search</label>
                            <input
                                v-model="filters.search"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="Name, email, phone..."
                                type="text"
                                @input="debouncedLoadStaff"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Department</label>
                            <select
                                v-model="filters.department"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                @change="loadStaff"
                            >
                                <option value="">All Departments</option>
                                <option value="Front Desk">Front Desk</option>
                                <option value="Housekeeping">Housekeeping</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Management">Management</option>
                                <option value="Sales">Sales</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Status</label>
                            <select
                                v-model="filters.is_active"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                @change="loadStaff"
                            >
                                <option :value="null">All</option>
                                <option :value="true">Active</option>
                                <option :value="false">Inactive</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Role</label>
                            <select
                                v-model="filters.role"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                @change="loadStaff"
                            >
                                <option value="">All Roles</option>
                                <option
                                    v-for="role in roles"
                                    :key="role.id"
                                    :value="role.id"
                                >
                                    {{ role.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div v-if="loading" class="flex items-center justify-center py-12">
                    <div class="text-center">
                        <svg class="mx-auto h-8 w-8 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor"></path>
                        </svg>
                        <p class="mt-2 text-sm text-slate-600">Loading...</p>
                    </div>
                </div>

                <div v-else-if="staffList.length === 0" class="rounded-lg bg-white p-12 text-center shadow-sm">
                    <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-slate-900">No staff members found</h3>
                    <p class="mt-2 text-sm text-slate-500">Get started by adding your first staff member.</p>
                    <button
                        class="mt-4 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700"
                        type="button"
                        @click="showCreateModal = true"
                    >
                        + Add Staff
                    </button>
                </div>

                <div v-else class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Position</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Roles</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <tr v-for="member in staffList" :key="member.id" class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                                            <span class="text-sm font-medium">{{ member.name.charAt(0).toUpperCase() }}</span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-slate-900">{{ member.name }}</div>
                                            <div v-if="member.phone" class="text-sm text-slate-500">{{ member.phone }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ member.email }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ member.position || '-' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ member.department || '-' }}</td>
                                <td class="px-6 py-4 text-sm text-slate-500">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="role in member.roles"
                                            :key="role.id"
                                            class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-800"
                                        >
                                            {{ role.name }}
                                        </span>
                                        <span v-if="member.roles.length === 0" class="text-slate-400">-</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                            member.is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-800',
                                        ]"
                                    >
                                        {{ member.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            class="text-blue-600 transition hover:text-blue-900"
                                            type="button"
                                            @click="handleEdit(member)"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            class="text-red-600 transition hover:text-red-900"
                                            type="button"
                                            @click="handleDelete(member)"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="pagination && pagination.total > pagination.per_page" class="border-t border-slate-200 bg-white px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-slate-700">
                                Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
                            </div>
                            <div class="flex gap-2">
                                <button
                                    :disabled="!pagination.prev_page_url"
                                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50"
                                    type="button"
                                    @click="loadStaff(pagination.prev_page_url)"
                                >
                                    Previous
                                </button>
                                <button
                                    :disabled="!pagination.next_page_url"
                                    class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition disabled:opacity-50 disabled:cursor-not-allowed hover:bg-slate-50"
                                    type="button"
                                    @click="loadStaff(pagination.next_page_url)"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>

        <!-- Create/Edit Staff Modal -->
        <StaffFormModal
            :staff="selectedStaff"
            :roles="roles"
            :show="showCreateModal || showEditModal"
            @close="handleCloseModal"
            @submit="handleSubmitStaff"
        />
    </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import SidebarNavigation from '../shared/components/SidebarNavigation.vue';
import TopNavbar from '../shared/components/TopNavbar.vue';
import StaffFormModal from './components/StaffFormModal.vue';

const staffList = ref([]);
const roles = ref([]);
const selectedStaff = ref(null);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const loading = ref(false);
const pagination = ref(null);

const filters = ref({
    search: '',
    department: '',
    role: '',
    is_active: null,
});

let debounceTimer = null;

async function loadRoles() {
    try {
        const response = await fetch('/api/staff/roles');
        const result = await response.json();
        if (result.success) {
            roles.value = result.data;
        }
    } catch (error) {
        console.error('Error loading roles:', error);
    }
}

async function loadStaff(url = null) {
    try {
        loading.value = true;
        const queryParams = new URLSearchParams();
        
        if (filters.value.search) queryParams.append('search', filters.value.search);
        if (filters.value.department) queryParams.append('department', filters.value.department);
        if (filters.value.role) queryParams.append('role', filters.value.role);
        if (filters.value.is_active !== null) queryParams.append('is_active', filters.value.is_active);

        const baseUrl = url || '/api/staff';
        const fullUrl = url || `${baseUrl}?${queryParams.toString()}`;

        const response = await fetch(fullUrl);
        const result = await response.json();
        
        if (result.success) {
            staffList.value = result.data.data || result.data;
            
            if (result.data.current_page) {
                pagination.value = {
                    current_page: result.data.current_page,
                    from: result.data.from,
                    to: result.data.to,
                    total: result.data.total,
                    per_page: result.data.per_page,
                    prev_page_url: result.data.prev_page_url,
                    next_page_url: result.data.next_page_url,
                };
            } else {
                pagination.value = null;
            }
        }
    } catch (error) {
        console.error('Error loading staff:', error);
        window.toastr.error('Failed to load staff. Please try again.');
    } finally {
        loading.value = false;
    }
}

function debouncedLoadStaff() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadStaff();
    }, 300);
}

function handleEdit(member) {
    selectedStaff.value = member;
    showEditModal.value = true;
}

async function handleDelete(member) {
    if (!confirm(`Are you sure you want to delete staff member "${member.name}"? This action cannot be undone.`)) {
        return;
    }

    try {
        const response = await fetch(`/api/staff/${member.id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
        });

        const result = await response.json();
        if (result.success) {
            window.toastr.success('Staff member deleted successfully');
            await loadStaff();
        } else {
            window.toastr.error(result.message || 'Failed to delete staff member');
        }
    } catch (error) {
        console.error('Error deleting staff:', error);
        window.toastr.error('Failed to delete staff member. Please try again.');
    }
}

function handleCloseModal() {
    showCreateModal.value = false;
    showEditModal.value = false;
    selectedStaff.value = null;
}

async function handleSubmitStaff(staffData) {
    try {
        const url = selectedStaff.value ? `/api/staff/${selectedStaff.value.id}` : '/api/staff';
        const method = selectedStaff.value ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify(staffData),
        });

        const result = await response.json();
        if (result.success) {
            window.toastr.success(selectedStaff.value ? 'Staff member updated successfully' : 'Staff member created successfully');
            handleCloseModal();
            await loadStaff();
        } else {
            window.toastr.error(result.message || 'Failed to save staff member');
            if (result.errors) {
                console.error('Validation errors:', result.errors);
            }
        }
    } catch (error) {
        console.error('Error saving staff:', error);
        window.toastr.error('Failed to save staff member. Please try again.');
    }
}

onMounted(() => {
    loadRoles();
    loadStaff();
});
</script>


