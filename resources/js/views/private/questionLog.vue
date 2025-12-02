
<template>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 d-flex align-items-center justify-content-between mb-2">
                <h1>Question Logs Management</h1>
                <div>
                    <button @click="exportLogs" class="btn btn-success me-2">
                        <i class="fas fa-file-export"></i> Export Excel
                    </button>
                </div>
            </div>
        </div>

        <div class="row mb-3">

            <div class="col-md-4">
                <input
                    type="text"
                    v-model="searchTerm"
                    class="form-control"
                    placeholder="Search by question, answer, or ID..."
                />
            </div>


            <div class="col-md-3">
                <select v-model="selectedIntentId" class="form-select">
                    <option value="">All Intents</option>
                    <option
                        v-for="intent in intents"
                        :key="intent.id"
                        :value="intent.id"
                    >
                        {{ intent.intent_name }}
                    </option>
                    <option :value="null">Unassigned Intent</option>
                </select>
            </div>


            <div class="col-md-3">
                <select v-model="selectedDepartmentId" class="form-select">
                    <option value="">All Departments</option>
                    <option
                        v-for="dept in departments"
                        :key="dept.id"
                        :value="dept.id"
                    >
                        {{ dept.name }}
                    </option>
                    <option :value="null">Unassigned Department</option>
                </select>
            </div>

            <div class="col-md-2">
                <select v-model="selectedStatus" class="form-select">
                    <option value="">All Status</option>
                    <option value="Success">
                        Success
                    </option>
                    <option value="Failded">Failed</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 shadow-lg rounded-lg">
                <div v-if="loading" class="text-center" style="padding: 13rem;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div v-if="!filteredLogs.length">
                    <p>No Data available.</p>
                </div>

                <div v-if="filteredLogs.length" >
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th scope="col">ID</th>
                                <th scope="col">Question</th>
                                <th scope="col">Answer</th>
                                <th scope="col">FAQs</th>
                                <th scope="col">Department</th>
                                <th scope="col">Intents</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in filteredLogs" :key="log.id">
                                <td>{{ log.id }}</td>
                                <td>{{ log.question_text }}</td>
                                <td v-if="log.answer_text == null">-</td>
                                <td v-else>{{ log.answer_text }}</td>
                                <td v-if="log.faq_id == null">-</td>
                                <td v-else>{{ log.faq.question }}</td>
                                <td v-if="log.department_id == null">-</td>
                                <td v-else>{{ log.department.name }}</td>
                                <td v-if="log.intent_id == null">-</td>
                                <td v-else>{{ log.intent.intent_name }}</td>
                                <td v-if="log.status == null">
                                    -
                                </td>
                                <td v-else-if="log.status == 1" class="success-text">Success</td>
                                <td v-else class="failed-text">Failed</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>



</template>

<script>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';
import { saveAs } from 'file-saver';
import { useDataFetcher } from '../../services/dataFetcher';


export default {
    setup() {
        const logs = ref([]);
        const error = ref(null);
        const token = localStorage.getItem('sanctum_token');

        const {
            intents,
            FAQs, // This holds ALL the FAQs
            departments,
            getFAQs,
            getIntents,
            getDepartments,
            loading
        } = useDataFetcher();

        const getLogs = async () => {

        if(token){
                try {
                    const response = await axios.get('/api/allQuestionLogs', {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    logs.value = response.data;
                    console.log(logs.value);
                } catch (err) {
                    error.value = err.response?.data?.message || 'Error fetching logs';
                }
            };
        };


        const exportLogs = () => {
            if (!logs.value.length) {
                Swal.fire('No Data', 'There is no data to export.', 'info');
                return;
            }

            try {
                // Map data into a flat array for Excel
                const exportData = logs.value.map(logs => ({
                    ID: logs.id,
                    Question: logs.question_text,
                    Answer: logs.answer_text,
                    Intent: logs.intent_id ?? 'No value',
                    Department: logs.department_id ?? 'No value',
                    Status: logs.status === 1 ? 'Success' : 'Failed'
                }));

                // Convert JSON to worksheet
                const worksheet = XLSX.utils.json_to_sheet(exportData);
                const workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, worksheet, 'logs');

                // Generate and save file
                const excelBuffer = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
                const blob = new Blob([excelBuffer], {
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                });
                saveAs(blob, 'logs.xlsx');
            } catch (error) {
                console.error('Export Error:', error);
                Swal.fire('Error', 'Failed to export logs', 'error');
            }
        };

               // 🟢 NEW: Reactive state for the text search and dropdown filters
        const searchTerm = ref('');
        const selectedIntentId = ref(''); // Bound to the Intent dropdown
        const selectedDepartmentId = ref(''); // Bound to the Department dropdown
        const selectedStatus = ref(''); // Bound to the Status dropdown


        // 🟢 NEW: Chained filtering logic in a single computed property
        const filteredLogs = computed(() => {
            let data = logs.value;

            // A. Apply Text Search (Question, Answer, ID)
            if (searchTerm.value) {
                const searchLower = searchTerm.value.toLowerCase();
                data = data.filter(log =>
                    log.question_text?.toLowerCase().includes(searchLower) ||
                    log.answer_text?.toLowerCase().includes(searchLower) ||
                    log.id?.toString().includes(searchTerm.value)
                );
            }

            // B. Apply Intent Filter
            // Filter only if a specific Intent ID (number or null for unassigned) is selected
            if (selectedIntentId.value !== '') {
                 // Intent IDs are usually numbers, unless 'null' is passed for unassigned
                const intentFilterValue = selectedIntentId.value === null ? null : parseInt(selectedIntentId.value);
                data = data.filter(log => log.intent_id === intentFilterValue);
            }

            // C. Apply Department Filter
            // Filter only if a specific Department ID (number or null for unassigned) is selected
            if (selectedDepartmentId.value !== '') {
                // Department IDs are usually numbers, unless 'null' is passed for unassigned
                const deptFilterValue = selectedDepartmentId.value === null ? null : parseInt(selectedDepartmentId.value);
                data = data.filter(log => log.department_id === deptFilterValue);
            }

            if(selectedStatus.value){
                const isSuccess = selectedStatus.value === 'Success';

                data = data.filter(log => {
                    // 对于 Success: 仅返回 status === 1 的日志
                    // 对于 Failed: 仅返回 status !== 1 的日志
                    return isSuccess ? log.status === 1 : log.status !== 1;
                });
            }

            return data;
        });

        onMounted(() => {
            getLogs();
            getDepartments();
            getIntents();
            getFAQs();
        });

        return {
            logs,
            error,
            getLogs,
            exportLogs,
            departments,
            intents,
            getIntents,
            getDepartments,
            getFAQs,
            FAQs,
            loading,
            searchTerm,
            selectedIntentId,
            selectedDepartmentId,
            selectedStatus,
            filteredLogs,
        };
    }
};
</script>


<style>
        .swal2-custom-wide .swal2-popup {
            width: 800px; /* Adjust this value to your desired width */
            max-width: 90vw; /* Ensures it's still responsive on smaller screens */
        }

        /* New styles to format the form with labels on the left */
        .swal2-input-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .swal2-input-group label {
            flex-basis: 120px; /* Adjust this to change the label width */
            text-align: left;
            margin-right: 15px;
            font-weight: 500;
        }

        .swal2-input-group input {
            flex-grow: 1; /* Makes the input field take up the remaining space */
        }

        .failed-text {
            font-weight: bold;
            color: red !important;
        }

        .success-text {
            font-weight: bold;
            color: rgb(0, 255, 55) !important;
        }
</style>
