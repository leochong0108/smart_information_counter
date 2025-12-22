<template>
<div class="container-fluid py-4">

    <!-- 1. Header & Actions -->
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-6 mb-3 mb-md-0">
            <h1 class="h3 mb-0 text-gray-800">FAQs Management</h1>
        </div>
        <div class="col-12 col-md-6 d-flex flex-wrap justify-content-md-end gap-2">
            <button @click="exportFAQs" class="btn btn-success flex-grow-1 flex-md-grow-0">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export
            </button>
            <input type="file" ref="importFileRef" style="display:none" accept=".xlsx, .xls, .csv" @change="onImportFileChange" />
            <button @click="triggerImport" class="btn btn-secondary flex-grow-1 flex-md-grow-0">
                <i class="bi bi-upload"></i> Import
            </button>
            <button @click="openCreateModal" class="btn btn-primary flex-grow-1 flex-md-grow-0">
                <i class="bi bi-plus-lg"></i> Add New
            </button>
        </div>
    </div>

    <!-- 2. Filter Section -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body p-3">
            <div class="row g-3">
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" v-model="searchTerm" class="form-control border-start-0 bg-light" placeholder="Search question, answer..." />
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select v-model="selectedIntentId" class="form-select">
                        <option value="">All Intents</option>
                        <option v-for="i in intents" :key="i.id" :value="i.id">{{ i.intent_name }}</option>
                        <option :value="null">Unassigned</option>
                    </select>
                </div>
                <div class="col-6 col-md-4">
                    <select v-model="selectedDepartmentId" class="form-select">
                        <option value="">All Departments</option>
                        <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                        <option :value="null">Unassigned</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-primary" role="status"></div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!filteredFAQs.length" class="text-center py-5 text-muted">
        <i class="bi bi-inbox fs-1"></i>
        <p>No FAQs found.</p>
    </div>

    <div v-else>

        <!-- 📱 MOBILE VIEW -->
        <div class="d-block d-md-none">
            <div v-for="FAQ in filteredFAQs" :key="FAQ.id" class="card shadow-sm mb-3 border-0">
                <div class="card-body">
                    <!-- Actions -->
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-light text-secondary">#{{ FAQ.id }}</span>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" @click="openEditModal(FAQ)">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" @click="deleteItem(FAQ.id)">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-2">{{ FAQ.question }}</h6>

                    <div class="scrollable-answer mb-3">
                        {{ FAQ.answer }}
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <span v-if="FAQ.intent" class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 text-wrap text-start lh-sm">
                            <i class="bi bi-diagram-2"></i> {{ FAQ.intent.intent_name }}
                        </span>
                        <span v-if="FAQ.department" class="badge bg-purple bg-opacity-10 text-purple border border-purple border-opacity-25 text-wrap text-start lh-sm">
                            <i class="bi bi-building"></i> {{ FAQ.department.name }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 💻 DESKTOP VIEW -->
        <div class="d-none d-md-block card shadow border-0 rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-top mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="px-3 py-3">#</th>
                            <th class="px-3 py-3" style="width: 20%">Question</th>
                            <th class="px-3 py-3" style="width: 35%">Answer</th>
                            <th class="px-3 py-3" style="width: 15%">Intent</th>
                            <th class="px-3 py-3" style="width: 15%">Department</th>
                            <th class="px-3 py-3 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(FAQ,index) in filteredFAQs" :key="FAQ.id">
                            <td class="px-3 fw-bold text-secondary">{{ index + 1 }}.</td>
                            <td class="px-3">
                                <div class="fw-medium text-break">{{ FAQ.question }}</div>
                            </td>
                            <td class="px-3">
                                <div class="table-scrollable-content text-secondary small">
                                    {{ FAQ.answer }}
                                </div>
                            </td>
                            <td class="px-3">
                                <span v-if="FAQ.intent" class="badge bg-info bg-opacity-10 text-info text-wrap text-start lh-sm d-inline-block" style="max-width: 140px;">
                                    {{ FAQ.intent.intent_name }}
                                </span>
                                <span v-else class="text-muted small">-</span>
                            </td>
                            <td class="px-3">
                                <span v-if="FAQ.department" class="badge bg-purple bg-opacity-10 text-purple text-wrap text-start lh-sm d-inline-block" style="max-width: 140px;">
                                    {{ FAQ.department.name }}
                                </span>
                                <span v-else class="text-muted small">-</span>
                            </td>
                            <td class="px-3 text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" @click="openEditModal(FAQ)">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteItem(FAQ.id)">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- 4. Modal (Form) -->
    <div v-if="showModal" class="modal-backdrop fade show"></div>
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1" @click.self="closeModal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ isEditMode ? 'Edit FAQ' : 'New FAQ' }}</h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
                </div>
                <div class="modal-body p-4">
                    <form @submit.prevent="saveItem">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Question</label>
                            <input type="text" v-model="form.question" class="form-control" required placeholder="e.g. How do I reset my password?">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Answer</label>
                            <textarea v-model="form.answer" class="form-control" rows="5" required placeholder="Type the answer here..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Intent</label>
                                <!-- 依赖 intents 数据 -->
                                <select v-model="form.intent_id" class="form-select">
                                    <option :value="null">None</option>
                                    <option v-for="i in intents" :key="i.id" :value="i.id">{{ i.intent_name }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
                                <!-- 依赖 departments 数据 -->
                                <select v-model="form.department_id" class="form-select">
                                    <option :value="null">None</option>
                                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div v-if="modalError" class="alert alert-danger py-2 small">{{ modalError }}</div>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-light" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4" :disabled="isSaving">
                                {{ isSaving ? 'Saving...' : 'Save' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';
import { saveAs } from 'file-saver';
import { useDataFetcher } from '../../composables/useDataFetcher';
import { useExcelImport } from '../../composables/useExcelImport';
import { useCrud } from '../../composables/useCrud';

// 1. 数据获取
// FAQs 列表页面需要 Intents 和 Departments 来做筛选和显示
const { intents, FAQs, departments, getFAQs, getIntents, getDepartments, loading } = useDataFetcher();

// 2. Excel 导入
const { importFileRef, triggerImport, handleFileUpload } = useExcelImport();
const onImportFileChange = (event) => handleFileUpload(event, 'faq', getFAQs);

// 3. 筛选逻辑
const searchTerm = ref('');
const selectedIntentId = ref('');
const selectedDepartmentId = ref('');

const filteredFAQs = computed(() => {
    let data = FAQs.value;

    // Search
    if (searchTerm.value) {
        const lower = searchTerm.value.toLowerCase();
        data = data.filter(f =>
            f.question?.toLowerCase().includes(lower) ||
            f.answer?.toLowerCase().includes(lower) ||
            f.id?.toString().includes(searchTerm.value)
        );
    }

    // Filters
    if (selectedIntentId.value !== '') {
        const val = selectedIntentId.value === null ? null : parseInt(selectedIntentId.value);
        data = data.filter(f => f.intent_id === val);
    }
    if (selectedDepartmentId.value !== '') {
        const val = selectedDepartmentId.value === null ? null : parseInt(selectedDepartmentId.value);
        data = data.filter(f => f.department_id === val);
    }
    return data;
});

// 4. CRUD 逻辑 (核心瘦身)
const defaultForm = {
    question: '',
    answer: '',
    intent_id: null,
    department_id: null
};

const apiEndpoints = {
    create: '/api/createFaqs',
    update: (id) => `/api/updateFaqs/${id}`,
    delete: (id) => `/api/deleteFaqs/${id}`
};

const {
    form,
    showModal,
    isEditMode,
    isSaving,
    modalError,
    openCreateModal,
    openEditModal,
    closeModal,
    saveItem,
    deleteItem
} = useCrud('FAQ', apiEndpoints, defaultForm, getFAQs);

// 5. Excel 导出
const exportFAQs = () => {
    if (!FAQs.value.length) return Swal.fire('Info', 'No data', 'info');
    const data = FAQs.value.map(f => ({
        ID: f.id, Question: f.question, Answer: f.answer,
        Intent: f.intent?.intent_name ?? 'None', Department: f.department?.name ?? 'None'
    }));
    const ws = XLSX.utils.json_to_sheet(data);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'FAQs');
    saveAs(new Blob([XLSX.write(wb, { bookType: 'xlsx', type: 'array' })]), 'FAQs.xlsx');
};

onMounted(() => {
    // 必须并行加载所有数据
    getFAQs();
    getDepartments();
    getIntents();
});
</script>

<style scoped>
/* 保持原有样式 */
.text-purple { color: #eae8ee !important; }
.bg-purple { background-color: #6f42c1 !important; }
.border-purple { border-color: #6f42c1 !important; }

.text-break {
    word-break: break-word;
    overflow-wrap: break-word;
}

.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1040;
}
.modal {
    z-index: 1050;
}

/* 电脑端表格内滚动 */
.table-scrollable-content {
    max-height: 120px;
    overflow-y: auto;
    white-space: pre-wrap;
    padding-right: 5px;
    scrollbar-width: thin;
}

.table-scrollable-content::-webkit-scrollbar { width: 4px; }
.table-scrollable-content::-webkit-scrollbar-thumb { background-color: #ced4da; border-radius: 4px; }

/* 手机端 Answer 滚动 */
.scrollable-answer {
    max-height: 200px;
    overflow-y: auto;
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 0.75rem;
    white-space: pre-wrap;
    font-size: 0.9rem;
    color: #495057;
    -webkit-overflow-scrolling: touch;
}
.scrollable-answer::-webkit-scrollbar { width: 4px; }
.scrollable-answer::-webkit-scrollbar-thumb { background-color: #adb5bd; border-radius: 4px; }
</style>
