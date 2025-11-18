// src/services/useFailedLogStore.js

import { ref, onMounted } from 'vue';
import axios from 'axios';

// Create a central reactive state outside the composable function
// so it's shared across all components that use it (Singleton pattern)
const failsCount = ref(0); // Only storing the count now, for simplicity
const token = localStorage.getItem('sanctum_token');

// Function to fetch and update the count
const fetchFailedLogsCount = async () => {
    try {
        const response = await axios.get('/api/selectFailedLogs', {
            headers: { Authorization: `Bearer ${token}` }
        });
        // Assuming the response.data is an array of logs
        failsCount.value = response.data.length;
        return response.data; // Return the full logs list for components that need it
    } catch (err) {
        console.error('Error fetching failed logs count:', err);
        // On error, reset count but don't show an error in the UI necessarily
        failsCount.value = 0;
        return [];
    }
};

export function useFailedLogStore() {
    // We fetch the count when the store is first used/mounted by a component
    onMounted(() => {
        fetchFailedLogsCount();
    });

    return {
        failsCount,
        refreshFailedLogs: fetchFailedLogsCount, // Provide a way to manually refresh
    };
}
