import { ref, watch } from 'vue';

const STORAGE_KEY = 'selected_property_id';
const selectedPropertyId = ref(null);
const selectedProperty = ref(null);

// Load from localStorage on initialization
if (typeof window !== 'undefined') {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
        selectedPropertyId.value = parseInt(stored);
    }
}

// Watch for changes and save to localStorage
watch(selectedPropertyId, (newValue) => {
    if (typeof window !== 'undefined') {
        if (newValue) {
            localStorage.setItem(STORAGE_KEY, newValue.toString());
        } else {
            localStorage.removeItem(STORAGE_KEY);
        }
    }
});

export function usePropertySelection() {
    function setSelectedProperty(property) {
        selectedProperty.value = property;
        selectedPropertyId.value = property?.id || null;
    }

    function setSelectedPropertyId(propertyId) {
        selectedPropertyId.value = propertyId;
        // If we have the property object, keep it in sync
        if (selectedProperty.value && selectedProperty.value.id !== propertyId) {
            selectedProperty.value = null;
        }
    }

    function clearSelection() {
        selectedProperty.value = null;
        selectedPropertyId.value = null;
    }

    async function loadPropertyDetails() {
        if (!selectedPropertyId.value) {
            selectedProperty.value = null;
            return;
        }

        try {
            const response = await fetch(`/api/properties/${selectedPropertyId.value}`);
            const result = await response.json();
            if (result.success) {
                selectedProperty.value = result.data;
            } else {
                // Property might have been deleted, clear selection
                clearSelection();
            }
        } catch (error) {
            console.error('Error loading property details:', error);
            selectedProperty.value = null;
        }
    }

    return {
        selectedPropertyId,
        selectedProperty,
        setSelectedProperty,
        setSelectedPropertyId,
        clearSelection,
        loadPropertyDetails,
    };
}



