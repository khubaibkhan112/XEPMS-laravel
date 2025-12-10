<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 p-4 overflow-y-auto"
        >
            <div class="w-full max-w-2xl rounded-lg bg-white shadow-xl my-8 max-h-[90vh] overflow-y-auto">
                <div class="border-b border-slate-200 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-slate-900">
                            {{ feature ? 'Edit Room Feature' : 'Create New Room Feature' }}
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
                                    Feature Name <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="Extra Bed"
                                    required
                                    type="text"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="code">
                                    Code
                                </label>
                                <input
                                    id="code"
                                    v-model="form.code"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="EXTRA-BED"
                                    type="text"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="type">
                                    Type <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="type"
                                    v-model="form.type"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    required
                                >
                                    <option value="addon">Add-on</option>
                                    <option value="extra_bed">Extra Bed</option>
                                    <option value="amenity">Amenity</option>
                                    <option value="service">Service</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="room_type_id">
                                    Room Type (Optional)
                                </label>
                                <input
                                    id="room_type_id"
                                    v-model.number="form.room_type_id"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    placeholder="Leave empty for all room types"
                                    type="number"
                                />
                                <p class="mt-1 text-xs text-slate-500">Leave empty to apply to all room types</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700" for="description">
                                Description
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                placeholder="Feature description..."
                                rows="2"
                            ></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="price">
                                    Price <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="price"
                                    v-model.number="form.price"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    min="0"
                                    required
                                    step="0.01"
                                    type="number"
                                />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="pricing_type">
                                    Pricing Type <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="pricing_type"
                                    v-model="form.pricing_type"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    required
                                >
                                    <option value="per_night">Per Night</option>
                                    <option value="per_stay">Per Stay</option>
                                    <option value="per_person">Per Person</option>
                                    <option value="per_person_per_night">Per Person Per Night</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
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
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="max_quantity">
                                    Max Quantity
                                </label>
                                <input
                                    id="max_quantity"
                                    v-model.number="form.max_quantity"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    min="1"
                                    placeholder="Unlimited"
                                    type="number"
                                />
                                <p class="mt-1 text-xs text-slate-500">Leave empty for unlimited</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700" for="sort_order">
                                    Sort Order
                                </label>
                                <input
                                    id="sort_order"
                                    v-model.number="form.sort_order"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    min="0"
                                    type="number"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700">
                                <input
                                    v-model="form.is_required"
                                    class="mr-2 h-4 w-4 rounded border-slate-300"
                                    type="checkbox"
                                />
                                Required Feature
                            </label>
                            <p class="text-xs text-slate-500">Required features are automatically included in bookings</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">
                                <input
                                    v-model="form.is_active"
                                    class="mr-2 h-4 w-4 rounded border-slate-300"
                                    type="checkbox"
                                />
                                Active
                            </label>
                            <p class="mt-1 text-xs text-slate-500">Inactive features will not appear in booking options</p>
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
                            {{ feature ? 'Update Feature' : 'Create Feature' }}
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
    feature: {
        type: Object,
        default: null,
    },
    propertyId: {
        type: [String, Number],
        required: true,
    },
});

const emit = defineEmits(['close', 'submit']);

const form = reactive({
    property_id: props.propertyId,
    room_type_id: null,
    name: '',
    code: '',
    type: 'addon',
    description: '',
    price: 0,
    pricing_type: 'per_night',
    currency: 'GBP',
    max_quantity: null,
    is_required: false,
    is_active: true,
    sort_order: 0,
});

watch(
    () => props.feature,
    (newFeature) => {
        if (newFeature) {
            form.property_id = newFeature.property_id || props.propertyId;
            form.room_type_id = newFeature.room_type_id || null;
            form.name = newFeature.name || '';
            form.code = newFeature.code || '';
            form.type = newFeature.type || 'addon';
            form.description = newFeature.description || '';
            form.price = newFeature.price || 0;
            form.pricing_type = newFeature.pricing_type || 'per_night';
            form.currency = newFeature.currency || 'GBP';
            form.max_quantity = newFeature.max_quantity || null;
            form.is_required = newFeature.is_required || false;
            form.is_active = newFeature.is_active !== undefined ? newFeature.is_active : true;
            form.sort_order = newFeature.sort_order || 0;
        } else {
            // Reset form for new feature
            form.property_id = props.propertyId;
            form.room_type_id = null;
            form.name = '';
            form.code = '';
            form.type = 'addon';
            form.description = '';
            form.price = 0;
            form.pricing_type = 'per_night';
            form.currency = 'GBP';
            form.max_quantity = null;
            form.is_required = false;
            form.is_active = true;
            form.sort_order = 0;
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
        max_quantity: form.max_quantity || null,
    };
    emit('submit', submitData);
}
</script>



