import axios from 'axios';

const API_BASE_URL = '/api';

// Set up axios defaults
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Get CSRF token from meta tag if available
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}

export const availabilityService = {
    /**
     * Check room availability for a date range
     */
    async checkAvailability(params) {
        try {
            const response = await axios.get(`${API_BASE_URL}/availability/check`, { params });
            return response.data;
        } catch (error) {
            console.error('Error checking availability:', error);
            throw error;
        }
    },

    /**
     * Get availability calendar for a date range
     */
    async getAvailabilityCalendar(params) {
        try {
            const response = await axios.get(`${API_BASE_URL}/availability/calendar`, { params });
            return response.data;
        } catch (error) {
            console.error('Error getting availability calendar:', error);
            throw error;
        }
    },

    /**
     * Block dates for maintenance
     */
    async blockDates(data) {
        try {
            const response = await axios.post(`${API_BASE_URL}/availability/block`, data);
            return response.data;
        } catch (error) {
            console.error('Error blocking dates:', error);
            throw error;
        }
    },

    /**
     * Unblock dates
     */
    async unblockDates(data) {
        try {
            const response = await axios.post(`${API_BASE_URL}/availability/unblock`, data);
            return response.data;
        } catch (error) {
            console.error('Error unblocking dates:', error);
            throw error;
        }
    },
};

