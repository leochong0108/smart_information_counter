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
                    <th scope="col" class="py-3 px-4">Log ID</th>
                    <th scope="col" class="py-3 px-4">User Question</th>
                    <th scope="col" class="py-3 px-4">Status</th>
                    <th scope="col" class="py-3 px-4">Details</th>
                    <th scope="col" class="py-3 px-4">Action</th>
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
                    <td class="py-3 px-4">Relevant Knowledge Not Available</td>
                    <td class="py-3 px-4"><button class="btn btn-primary " @click="createFAQs(fail)">Insert New FAQs</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useDataFetcher } from '../../services/dataFetcher';
import { useFailedLogStore } from '../../services/useFailsLog';


export default {
    setup() {
        const { refreshFailedLogs } = useFailedLogStore();
        const fails = ref([]);
        const loading = ref(true); // Added loading state
        const error = ref(null);
        const token = localStorage.getItem('sanctum_token');

        const {
            intents,
            departments,
            getIntents,
            getDepartments,
        } = useDataFetcher();

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
            error.value = null;
            try {
                // We call the shared refresh function here. It returns the full array.
                const logs = await refreshFailedLogs();
                fails.value = logs; // Update local state with the full logs array
            }
            catch (err) {
                // The error handling in the store should catch the network error,
                // but we keep this for local display error.
                error.value = err.response?.data?.message || 'Error fetching failed logs';
                fails.value = [];
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

        const createFAQs = async (fails) => {

            await getDepartments();
            await getIntents();


            // Modified to use selectedIntentId/selectedDepartmentId and handle null values
            const deptOptions = departments.value.map(dept => `<option value="${dept.id}">${dept.name}</option>`).join('');
            const intentOptions = intents.value.map(intent => `<option value="${intent.id}">${intent.intent_name}</option>`).join('');

            const { value: formValues } = await Swal.fire({
                title: 'Create',
                html:
                    // ... (HTML unchanged) ...
                    // Added option value="null" to allow unassignment in creation
                    `<div class="swal2-input-group">
                        <label for="question">Question</label>
                        <input id="question" class="swal2-input" placeholder="Question" value="${fails.question_text}">
                    </div>
                    <div class="swal2-input-group">
                        <label for="answer">Answer</label>
                        <input id="answer" class="swal2-input" placeholder="Answer">
                    </div>
                    <div class="swal2-input-group">
                        <label for="intent">Intent</label>
                        <select id="intent" class="swal2-input" style="margin-left: 40px;">
                            <option value="" disabled selected>Select Intent</option>
                            <option value="null">None</option>
                            ${intentOptions}
                        </select>
                    </div>
                    <div class="swal2-input-group">
                        <label for="department" ">Department</label>
                        <select id="department" class="swal2-input" style="margin-left: 40px;">
                            <option value="" disabled selected>Select Department</option>
                            <option value="null">None</option>
                            ${deptOptions}
                        </select>
                    </div>`,
                focusConfirm: false,
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                customClass: { container: 'swal2-custom-wide'},

                preConfirm: () => {
                    const intentValue = document.getElementById('intent').value;
                    const departmentValue = document.getElementById('department').value;
                    return {
                        question: document.getElementById('question').value,
                        answer: document.getElementById('answer').value,
                        intent_id: intentValue === 'null' ? null : intentValue,
                        department_id: departmentValue === 'null' ? null : departmentValue,
                    };
                }
            });

            if (formValues) {
                if(token){
                    try {
                        await axios.post(`/api/insertAndMark/${fails.id}`, formValues, {
                            headers: { Authorization: `Bearer ${token}` }
                        });
                        await getFail();
                        Swal.fire('Created!', 'New FAQ has been created.', 'success');
                    } catch (err) {
                        Swal.fire('Error', err.response?.data?.message || 'Could not create FAQ', 'error');
                    }
                }
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
            intents,
            departments,
            getIntents,
            getDepartments,
            createFAQs,
            refreshFailedLogs
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
