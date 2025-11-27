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
                            {{ property ? 'Edit Property' : 'Create New Property' }}
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
                                    Property Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="London Suites"
                                    required
                                    type="text"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="code">
                                    Property Code <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="code"
                                    v-model="form.code"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="LON001"
                                    required
                                    type="text"
                                />
                                <p class="mt-1 text-xs text-slate-500">Unique identifier for this property</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="timezone">
                                    Timezone
                                </label>
                                <select
                                    id="timezone"
                                    v-model="form.timezone"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                >
                                    <option value="UTC">UTC</option>
                                    <option value="Europe/London">Europe/London (GMT/BST)</option>
                                    <option value="Europe/Paris">Europe/Paris (CET)</option>
                                    <option value="America/New_York">America/New_York (EST/EDT)</option>
                                    <option value="America/Los_Angeles">America/Los_Angeles (PST/PDT)</option>
                                    <option value="Asia/Dubai">Asia/Dubai (GST)</option>
                                    <option value="Asia/Tokyo">Asia/Tokyo (JST)</option>
                                </select>
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

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="email">
                                    Email
                                </label>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="property@example.com"
                                    type="email"
                                />
                            </div>
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
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="address">
                                Address
                            </label>
                            <textarea
                                id="address"
                                v-model="form.address"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="123 Main Street, London, UK"
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
                                Active Property
                            </label>
                            <p class="mt-1 text-xs text-slate-500">Inactive properties will not appear in availability searches</p>
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
                            {{ property ? 'Update Property' : 'Create Property' }}
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
    property: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'submit']);

const form = reactive({
    name: '',
    code: '',
    timezone: 'UTC',
    currency: 'GBP',
    email: '',
    phone: '',
    address: '',
    is_active: true,
});

watch(
    () => props.property,
    (newProperty) => {
        if (newProperty) {
            form.name = newProperty.name || '';
            form.code = newProperty.code || '';
            form.timezone = newProperty.timezone || 'UTC';
            form.currency = newProperty.currency || 'GBP';
            form.email = newProperty.email || '';
            form.phone = newProperty.phone || '';
            form.address = newProperty.address || '';
            form.is_active = newProperty.is_active !== undefined ? newProperty.is_active : true;
        } else {
            // Reset form for new property
            form.name = '';
            form.code = '';
            form.timezone = 'UTC';
            form.currency = 'GBP';
            form.email = '';
            form.phone = '';
            form.address = '';
            form.is_active = true;
        }
    },
    { immediate: true }
);

function handleClose() {
    emit('close');
}

function handleSubmit() {
    emit('submit', { ...form });
}
</script>


