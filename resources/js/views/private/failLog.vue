<template>
    <div class="d-flex align-items-center justify-content-between mb-4 mt-4">
        <h1 class="text-3xl font-bold">Failed Question Logs</h1>

        <button
            @click="markSelectedAsChecked"
            :disabled="selectedLogIds.length === 0"
            class="btn btn-primary shadow-lg"
            :class="{'opacity-50 cursor-not-allowed': selectedLogIds.length === 0}"
        >
            Mark {{ selectedLogIds.length }} Selected as Checked
        </button>
    </div>

    <div v-if="loading" class="text-center py-20">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div v-if="error" class="alert alert-danger" role="alert">
        Error: {{ error }}
    </div>

    <div v-if="!loading && fails.length === 0 && !error">
        <div class="alert alert-info" role="alert">
            <p class="mb-0">✅ No failed logs available. Everything looks checked!</p>
        </div>
    </div>

    <div v-if="!loading && fails.length > 0" class="shadow-lg rounded-lg overflow-hidden">
        <table class="table table-hover mb-0">
            <thead class="bg-gray-100">
                <tr>
                    <!-- Selection Checkbox Column -->
                    <th scope="col" class="py-3 px-4">
                        <input type="checkbox" @click="toggleSelectAll" :checked="isAllSelected">
                    </th>
                    <th scope="col" class="py-3 px-4">ID</th>
                    <th scope="col" class="py-3 px-4">Question Text</th>
                    <th scope="col" class="py-3 px-4">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="fail in fails" :key="fail.id" class="border-b">
                    <td class="py-3 px-4">
                        <input type="checkbox" :value="fail.id" v-model="selectedLogIds">
                    </td>

                    <td class="py-3 px-4 font-mono">{{ fail.id }}</td>
                    <td class="py-3 px-4">{{ fail.question_text }}</td>

                    <td class="py-3 px-4">
                        <span v-if="fail.status == null" class="text-muted">-</span>
                        <span v-else-if="fail.status == 1" class="text-success fw-bold">Success</span>
                        <span v-else class="failed-text">Failed</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router'; // Assuming you have Vue Router available

export default {
    setup() {
        const router = useRouter(); // Initialize router if needed for future use
        const fails = ref([]);
        const loading = ref(true); // Added loading state
        const error = ref(null);
        const token = localStorage.getItem('sanctum_token');

        // State for tracking which logs are selected by the user
        const selectedLogIds = ref([]);

        // Computed property to check if all current logs are selected
        const isAllSelected = computed(() => {
            // Only consider logs if the list isn't empty, to avoid misleading checkmark
            return fails.value.length > 0 && selectedLogIds.value.length === fails.value.length;
        });

        // Function to select/deselect all items
        const toggleSelectAll = () => {
            if (isAllSelected.value) {
                selectedLogIds.value = []; // Deselect all
            } else {
                // Select all IDs currently displayed in the 'fails' list
                selectedLogIds.value = fails.value.map(f => f.id);
            }
        };

        const getFail = async() => {
            loading.value = true;
            error.value = null; // Clear previous errors
            try {
                // Assuming the backend endpoint is '/api/selectFailedLogs'
                const response = await axios.get('/api/selectFailedLogs', {
                    headers: { Authorization: `Bearer ${token}` }
                });
                fails.value = response.data;
            }
            catch (err) {
                error.value = err.response?.data?.message || 'Error fetching failed logs';
                fails.value = []; // Clear list on error
            } finally {
                loading.value = false;
            }
        };

        /**
         * Sends the list of selected log IDs to the backend to mark them as 'checked'.
         * This function is triggered by the 'Mark as Checked' button click.
         */
        const markSelectedAsChecked = async() => {
            if (selectedLogIds.value.length === 0) {
                console.warn('No logs selected to mark as checked.');
                return;
            }

            const payload = {
                ids: selectedLogIds.value // Sending the array of IDs
            };

            try {
                // Assuming the backend route is defined as:
                // Route::post('/api/mark-failed-logs', [QuestionLogController::class, 'markSelectedAsChecked']);
                const response = await axios.post('/api/mark-failed-logs', payload, {
                    headers: { Authorization: `Bearer ${token}` }
                });

                console.log(response.data.message);

                // 1. Clear the selection array immediately for user feedback
                selectedLogIds.value = [];

                // 2. Refresh the list of failed logs (re-calling getFail)
                // The marked logs will disappear from the list automatically.
                await getFail();

            }
            catch (err) {
                error.value = err.response?.data?.message || 'Error marking logs as checked';
            }
        };


        onMounted(() => {
            getFail();
        });

        return {
            fails,
            loading,
            error,
            selectedLogIds, // Exported to be used with v-model
            isAllSelected,
            toggleSelectAll,
            getFail,
            markSelectedAsChecked,
        };
    }
};
</script>


<style scoped>
    /* Scoped styles ensure they only apply to this component */
    .failed-text {
        font-weight: bold;
        color: #dc3545 !important; /* Bootstrap's danger color */
    }
    .btn-primary {
        background-color: #0d6efd;
        border-color: #0d6efd;
        transition: all 0.2s;
    }
    .btn-primary:hover:not(:disabled) {
        background-color: #0b5ed7;
        border-color: #0a58ca;
    }

    /* Custom styles for clean table look */
    .table {
        --bs-table-bg: white;
    }
    .table thead th {
        vertical-align: bottom;
        border-bottom: 2px solid #dee2e6;
        background-color: #f8f9fa;
        color: #343a40;
        font-weight: 600;
        text-align: left;
    }

    .table tbody tr:hover {
        background-color: #f1f1f1;
    }
    .text-3xl { font-size: 1.75rem; }
    .font-bold { font-weight: 700; }
    .shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
    .rounded-lg { border-radius: 0.5rem; }
    .overflow-hidden { overflow: hidden; }
</style>
