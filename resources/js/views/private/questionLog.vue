
<template>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h1>Question Log</h1>

        <div class="d-flex gap-2">
            <button @click="exportLogs" class="btn btn-success">
                <i class="fas fa-plus-circle"></i> Export excel
            </button>
        </div>
    </div>

    <div v-if="loading" class="text-center" style="padding: 13rem;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div v-if="!logs.length">
        <p>No Data available.</p>
    </div>

    <div v-if="logs.length" >
        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Question</th>
                    <th scope="col">Answer</th>
                    <th scope="col">Status</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="log in logs" :key="log.id">
                    <td>{{ log.id }}</td>
                    <td>{{ log.question_text }}</td>
                    <td v-if="log.answer_text == null">-</td>
                    <td v-else>{{ log.answer_text }}</td>
                    <td v-if="log.status == null">
                        -
                    </td>
                    <td v-else-if="log.status == 1">Success</td>
                    <td v-else class="failed-text">Failed</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';
import { saveAs } from 'file-saver';

export default {
    setup() {
        const logs = ref([]);
        const error = ref(null);
        const token = localStorage.getItem('sanctum_token');

        const getLogs = async () => {

        if(token){
                try {
                    const response = await axios.get('/api/allQuestionLogs', {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    logs.value = response.data;
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

        onMounted(() => {
            getLogs();
        });

        return {
            logs,
            error,
            getLogs,
            exportLogs
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
</style>
