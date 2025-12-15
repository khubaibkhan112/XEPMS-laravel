<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 overflow-y-auto"
        >
            <div class="w-full max-w-3xl rounded-lg bg-white shadow-xl my-8 max-h-[90vh] overflow-y-auto">
                <div class="border-b border-slate-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-slate-900">
                            {{ staff ? 'Edit Staff Member' : 'Create New Staff Member' }}
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
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="John Doe"
                                    required
                                    type="text"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="email">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="john@example.com"
                                    required
                                    type="email"
                                />
                            </div>
                        </div>

                        <div v-if="!staff" class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="password">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="••••••••"
                                    required
                                    type="password"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="password_confirmation">
                                    Confirm Password <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="••••••••"
                                    required
                                    type="password"
                                />
                            </div>
                        </div>

                        <div v-if="staff" class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="password">
                                    New Password (leave blank to keep current)
                                </label>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="••••••••"
                                    type="password"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="password_confirmation">
                                    Confirm New Password
                                </label>
                                <input
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="••••••••"
                                    type="password"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="phone">
                                    Phone
                                </label>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="+44 20 1234 5678"
                                    type="tel"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="position">
                                    Position
                                </label>
                                <input
                                    id="position"
                                    v-model="form.position"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="Manager, Receptionist, etc."
                                    type="text"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="department">
                                    Department
                                </label>
                                <select
                                    id="department"
                                    v-model="form.department"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="">Select Department</option>
                                    <option value="Front Desk">Front Desk</option>
                                    <option value="Housekeeping">Housekeeping</option>
                                    <option value="Maintenance">Maintenance</option>
                                    <option value="Management">Management</option>
                                    <option value="Sales">Sales</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="property_id">
                                    Default Property
                                </label>
                                <select
                                    id="property_id"
                                    v-model="form.property_id"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option :value="null">No Default Property</option>
                                    <option
                                        v-for="property in properties"
                                        :key="property.id"
                                        :value="property.id"
                                    >
                                        {{ property.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="roles">
                                Roles
                            </label>
                            <div class="mt-2 space-y-2">
                                <label
                                    v-for="role in roles"
                                    :key="role.id"
                                    class="flex items-center"
                                >
                                    <input
                                        :value="role.id"
                                        v-model="form.roles"
                                        class="mr-2 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        type="checkbox"
                                    />
                                    <span class="text-sm text-slate-700">{{ role.name }}</span>
                                    <span v-if="role.description" class="ml-2 text-xs text-slate-500">- {{ role.description }}</span>
                                </label>
                                <p v-if="roles.length === 0" class="text-sm text-slate-500">No roles available. Please create roles first.</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="notes">
                                Notes
                            </label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="Additional notes about this staff member..."
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
                                Active Status
                            </label>
                            <p class="mt-1 text-xs text-slate-500">Inactive staff members cannot log in to the system</p>
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
                            {{ staff ? 'Update Staff' : 'Create Staff' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { reactive, watch, ref, onMounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    staff: {
        type: Object,
        default: null,
    },
    roles: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'submit']);

const properties = ref([]);

const form = reactive({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    phone: '',
    position: '',
    department: '',
    property_id: null,
    notes: '',
    is_active: true,
    roles: [],
});

async function loadProperties() {
    try {
        const response = await fetch('/api/properties');
        const result = await response.json();
        if (result.success) {
            properties.value = result.data.data || result.data;
        }
    } catch (error) {
        console.error('Error loading properties:', error);
    }
}

watch(
    () => props.staff,
    (newStaff) => {
        if (newStaff) {
            form.name = newStaff.name || '';
            form.email = newStaff.email || '';
            form.password = '';
            form.password_confirmation = '';
            form.phone = newStaff.phone || '';
            form.position = newStaff.position || '';
            form.department = newStaff.department || '';
            form.property_id = newStaff.property_id || null;
            form.notes = newStaff.notes || '';
            form.is_active = newStaff.is_active !== undefined ? newStaff.is_active : true;
            form.roles = newStaff.roles ? newStaff.roles.map(r => r.id) : [];
        } else {
            // Reset form for new staff
            form.name = '';
            form.email = '';
            form.password = '';
            form.password_confirmation = '';
            form.phone = '';
            form.position = '';
            form.department = '';
            form.property_id = null;
            form.notes = '';
            form.is_active = true;
            form.roles = [];
        }
    },
    { immediate: true }
);

function handleClose() {
    emit('close');
}

function handleSubmit() {
    const submitData = { ...form };
    
    // Remove password fields if they're empty during edit
    if (props.staff && !submitData.password) {
        delete submitData.password;
        delete submitData.password_confirmation;
    }
    
    emit('submit', submitData);
}

onMounted(() => {
    loadProperties();
});
</script>


