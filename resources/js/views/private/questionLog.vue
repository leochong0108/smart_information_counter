<template>
<div class="container-fluid py-4">

    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-10 mb-3 mb-md-0">
            <h1 class="h3 mb-0 text-gray-800">
                Question Logs
                <span class="badge bg-secondary fs-6 align-middle rounded-pill ms-2">{{ filteredLogs.length }}</span>
            </h1>
        </div>
        <div class="col-12 col-md-2 text-md-end">
            <button @click="exportLogs" class="btn btn-success w-100 w-md-auto">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export to Excel
            </button>
        </div>
    </div>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            v-model="searchTerm"
                            class="form-control border-start-0 bg-light"
                            placeholder="Search question, answer..."
                        />
                    </div>
                </div>

                <div class="col-6 col-md-2">
                    <select v-model="selectedIntentId" class="form-select">
                        <option value="">All Intents</option>
                        <option v-for="i in intents" :key="i.id" :value="i.id">{{ i.intent_name }}</option>
                        <option :value="null">Unassigned</option>
                    </select>
                </div>

                <div class="col-6 col-md-3">
                    <select v-model="selectedDepartmentId" class="form-select">
                        <option value="">All Departments</option>
                        <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                        <option :value="null">Unassigned</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <select v-model="selectedStatus" class="form-select">
                        <option value="">All Status</option>
                        <option value="Success">Success</option>
                        <option value="Failed">Failed</option>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <select v-model="selectedRemark" class="form-select" :disabled="!uniqueRemarks.length">
                        <option value="">All Remarks</option>
                        <option v-for="r in uniqueRemarks" :key="r" :value="r">{{ r }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
    </div>

    <div v-else-if="!filteredLogs.length" class="text-center py-5 text-muted">
        <i class="bi bi-journal-x fs-1"></i>
        <p>No logs found matching your criteria.</p>
    </div>

    <div v-else>

        <div class="d-block d-md-none">
            <div v-for="log in filteredLogs" :key="log.id" class="card shadow-sm mb-3 border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-light text-secondary">#{{ log.id }}</span>
                        <span v-if="log.status === 1" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                            <i class="bi bi-check-circle-fill me-1"></i> Success
                        </span>
                        <span v-else class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                            <i class="bi bi-x-circle-fill me-1"></i> Failed
                        </span>
                    </div>

                    <div v-if="log.remark && log.status === 0" class="d-flex justify-content-between align-items-center mb-2">
                        <span class=" text-secondary text-wrap text-start lh-sm">.</span>
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 text-wrap text-start lh-sm">
                            <i class="bi bi-info-circle me-1"></i> {{ log.remark }}
                        </span>
                    </div>

                    <div class="mb-3">
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">User Question</small>
                        <div class="fw-bold text-dark">{{ log.question_text }}</div>
                    </div>

                    <div class="mb-3">
                        <small class="text-uppercase text-muted fw-bold" style="font-size: 0.7rem;">AI Answer</small>
                        <div v-if="log.answer_text" class="scrollable-content bg-light p-3 rounded text-secondary border">
                            {{ log.answer_text }}
                        </div>
                        <div v-else class="text-muted small fst-italic ps-2">
                            - No Answer -
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 border-top pt-2">
                        <span v-if="log.intent" class="badge bg-info bg-opacity-10 text-info text-wrap text-start lh-sm">
                            <i class="bi bi-diagram-2"></i> {{ log.intent.intent_name }}
                        </span>

                        <span v-if="log.department" class="badge bg-purple bg-opacity-10 text-purple text-wrap text-start lh-sm">
                            <i class="bi bi-building"></i> {{ log.department.name }}
                        </span>

                        <span v-if="log.faq" class="badge bg-secondary bg-opacity-10 text-secondary text-wrap text-start lh-sm">
                            <i class="bi bi-link-45deg"></i> FAQ #{{ log.faq.id }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-none d-md-block card shadow border-0 rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-top mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="px-3 py-3" style="width: 5%">#</th>
                            <th class="px-3 py-3" style="width: 5%">ID</th>
                            <th class="px-3 py-3" style="width: 15%">Status / Remark</th>
                            <th class="px-3 py-3" style="width: 25%">Question</th>
                            <th class="px-3 py-3" style="width: 25%">Answer</th>
                            <th class="px-3 py-3" style="width: 25%">Context</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(log, index) in filteredLogs" :key="log.id">
                            <td class="px-3 fw-bold text-secondary">{{ index + 1 }}.</td>
                            <td class="px-3 fw-bold text-secondary">{{ log.id }}</td>

                            <td class="px-3">
                                <div class="d-flex flex-column gap-1 align-items-start">
                                    <span v-if="log.status === 1" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                        <i class="bi bi-check-circle"></i> Success
                                    </span>
                                    <span v-else class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                        <i class="bi bi-x-circle"></i> Failed
                                    </span>

                                    <span v-if="log.remark && log.status === 0" class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 text-wrap text-start mt-1" style="font-size: 0.75rem;">
                                        {{ log.remark }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-3">
                                <div class="table-scrollable-content fw-medium text-dark" style="max-height: 80px;">
                                    {{ log.question_text }}
                                </div>
                            </td>

                            <td class="px-3">
                                <div class="table-scrollable-content text-secondary small">
                                    {{ log.answer_text || '-' }}
                                </div>
                            </td>

                            <td class="px-3">
                                <div class="d-flex flex-column gap-1">
                                    <div v-if="log.intent" class="d-flex align-items-center">
                                        <i class="bi bi-diagram-2 text-info me-2 small"></i>
                                        <span class="text-wrap small">{{ log.intent.intent_name }}</span>
                                    </div>

                                    <div v-if="log.department" class="d-flex align-items-center">
                                        <i class="bi bi-building text-purple me-2 small"></i>
                                        <span class="text-wrap small">{{ log.department.name }}</span>
                                    </div>

                                    <div v-if="log.faq" class="d-flex align-items-center text-muted">
                                        <i class="bi bi-link-45deg me-2 small"></i>
                                        <span class="text-wrap small" style="font-size: 0.75rem;">Source: {{ log.faq.question }}</span>
                                    </div>

                                    <div v-if="!log.intent && !log.department && !log.faq" class="text-muted small fst-italic">
                                        - No Context -
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';
import { saveAs } from 'file-saver';
import { useDataFetcher } from '../../composables/useDataFetcher';

        const token = localStorage.getItem('sanctum_token');
        const logs = ref([]);
        const error = ref(null);
        const searchTerm = ref('');

        const {
            intents,
            departments,
            loading,
            getIntents,
            getDepartments
        } = useDataFetcher();

        // Filters
        const selectedIntentId = ref('');
        const selectedDepartmentId = ref('');
        const selectedStatus = ref('');
        // 🌟 新增
        const selectedRemark = ref('');

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

        const uniqueRemarks = computed(() => {
            if (!logs.value.length) return [];
            const remarks = logs.value.map(l => l.remark).filter(r => r);
            return [...new Set(remarks)].sort();
        });

        const exportLogs = () => {
            if (!logs.value.length) return Swal.fire('Info', 'No data', 'info');
            try {
                const data = filteredLogs.value.map(l => ({
                    ID: l.id,
                    Question: l.question_text,
                    Answer: l.answer_text,
                    Status: l.status === 1 ? 'Success' : 'Failed',
                    Remark: l.remark || '',
                    Intent: l.intent?.intent_name ?? 'None',
                    Department: l.department?.name ?? 'None',
                    Source_FAQ_ID: l.faq_id ?? 'None'
                }));
                const ws = XLSX.utils.json_to_sheet(data);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Logs');
                saveAs(new Blob([XLSX.write(wb, { bookType: 'xlsx', type: 'array' })]), 'QuestionLogs.xlsx');
            } catch (e) { Swal.fire('Error', 'Export failed', 'error'); }
        };

        const filteredLogs = computed(() => {
            let data = logs.value;

            // Search
            if (searchTerm.value) {
                const lower = searchTerm.value.toLowerCase();
                data = data.filter(l =>
                    l.question_text?.toLowerCase().includes(lower) ||
                    l.answer_text?.toLowerCase().includes(lower) ||
                    l.id?.toString().includes(searchTerm.value)
                );
            }

            // Filters
            if (selectedIntentId.value !== '') {
                const val = selectedIntentId.value === null ? null : parseInt(selectedIntentId.value);
                data = data.filter(l => l.intent_id === val);
            }
            if (selectedDepartmentId.value !== '') {
                const val = selectedDepartmentId.value === null ? null : parseInt(selectedDepartmentId.value);
                data = data.filter(l => l.department_id === val);
            }
            if (selectedStatus.value) {
                const isSuccess = selectedStatus.value === 'Success';
                data = data.filter(l => isSuccess ? l.status === 1 : l.status !== 1);
            }

            if (selectedRemark.value) {
                data = data.filter(l => l.remark === selectedRemark.value);
            }

            return data;
        });

        onMounted(() => {
            getLogs();
            getIntents();
            getDepartments();
        });

</script>

<style scoped>
.text-purple { color: #f2f1f5 !important; }
.bg-purple { background-color: #6f42c1 !important; }

.scrollable-content {
    max-height: 150px;
    overflow-y: auto;
    white-space: pre-wrap;
    font-size: 0.9rem;
    -webkit-overflow-scrolling: touch;
}

.table-scrollable-content {
    max-height: 120px;
    overflow-y: auto;
    white-space: pre-wrap;
    padding-right: 5px;
    scrollbar-width: thin;
}

.scrollable-content::-webkit-scrollbar,
.table-scrollable-content::-webkit-scrollbar {
    width: 4px;
}
.scrollable-content::-webkit-scrollbar-thumb,
.table-scrollable-content::-webkit-scrollbar-thumb {
    background-color: #adb5bd;
    border-radius: 4px;
}
</style>
