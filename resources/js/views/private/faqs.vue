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
            <input type="file" ref="importFile" style="display:none" @change="importFAQs" />
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
                        <input type="text" v-model="searchTerm" class="form-control border-start-0 bg-light" placeholder="Search..." />
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

<!-- 📱 MOBILE VIEW: Cards (只在手机显示) -->
        <div class="d-block d-md-none">
            <div v-for="FAQ in filteredFAQs" :key="FAQ.id" class="card shadow-sm mb-3 border-0">
                <div class="card-body">
                    <!-- ID & Actions Row -->
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-light text-secondary">#{{ FAQ.id }}</span>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" @click="openEditModal(FAQ)">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" @click="deleteFAQs(FAQ.id)">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Question -->
                    <h6 class="fw-bold text-dark mb-2">{{ FAQ.question }}</h6>

                    <!-- 🌟 重点修改：Answer 区域 -->
                    <!-- scrollable-answer 类负责限制高度和滚动 -->
                    <div class="scrollable-answer mb-3">
                        {{ FAQ.answer }}
                    </div>

                    <!-- Metadata Tags -->
                    <!-- 🌟 重点修改：添加 text-wrap 允许文字换行，d-inline-flex 防止宽度撑爆 -->
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

<!-- 💻 DESKTOP/TABLET VIEW: Table (只在电脑平板显示) -->
        <div class="d-none d-md-block card shadow border-0 rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-top mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="px-3 py-3">#</th>
                            <th class="px-3 py-3">ID</th>
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
                            <td class="px-3 fw-bold text-secondary">{{ FAQ.id }}</td>

                            <!-- Question: 稍微限制一下，避免太长 -->
                            <td class="px-3">
                                <div class="fw-medium text-break">{{ FAQ.question }}</div>
                            </td>

                            <!-- 🌟 重点修改：Answer -->
                            <td class="px-3">
                                <!-- 使用专门的 table-scrollable-content 类 -->
                                <div class="table-scrollable-content text-secondary small">
                                    {{ FAQ.answer }}
                                </div>
                            </td>

                            <!-- 🌟 重点修改：Intent Tag -->
                            <td class="px-3">
                                <span v-if="FAQ.intent"
                                      class="badge bg-info bg-opacity-10 text-info text-wrap text-start lh-sm d-inline-block"
                                      style="max-width: 140px;">
                                    {{ FAQ.intent.intent_name }}
                                </span>
                                <span v-else class="text-muted small">-</span>
                            </td>

                            <!-- 🌟 重点修改：Department Tag -->
                            <td class="px-3">
                                <span v-if="FAQ.department"
                                      class="badge bg-purple bg-opacity-10 text-purple text-wrap text-start lh-sm d-inline-block"
                                      style="max-width: 140px;">
                                    {{ FAQ.department.name }}
                                </span>
                                <span v-else class="text-muted small">-</span>
                            </td>

                            <td class="px-3 text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" @click="openEditModal(FAQ)">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteFAQs(FAQ.id)">
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

    <!-- 4. Modal (表单弹窗) -->
    <div v-if="showModal" class="modal-backdrop fade show"></div>
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1" @click.self="closeModal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">{{ isEditMode ? 'Edit FAQ' : 'New FAQ' }}</h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
                </div>
                <div class="modal-body p-4">
                    <form @submit.prevent="saveFAQ">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Question</label>
                            <input type="text" v-model="form.question" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Answer</label>
                            <textarea v-model="form.answer" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Intent</label>
                                <select v-model="form.intent_id" class="form-select">
                                    <option :value="null">None</option>
                                    <option v-for="i in intents" :key="i.id" :value="i.id">{{ i.intent_name }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Department</label>
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

<script>
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';
import { saveAs } from 'file-saver';
import { useDataFetcher } from '../../services/dataFetcher';

export default {
    setup() {
        const token = localStorage.getItem('sanctum_token');
        const importFile = ref(null);

        const { intents, FAQs, departments, getFAQs, getIntents, getDepartments, loading } = useDataFetcher();

        const searchTerm = ref('');
        const selectedIntentId = ref('');
        const selectedDepartmentId = ref('');

        const showModal = ref(false);
        const isEditMode = ref(false);
        const isSaving = ref(false);
        const modalError = ref('');
        const currentId = ref(null);

        const form = reactive({ question: '', answer: '', intent_id: null, department_id: null });

        const filteredFAQs = computed(() => {
            let data = FAQs.value;
            if (searchTerm.value) {
                const lower = searchTerm.value.toLowerCase();
                data = data.filter(f =>
                    f.question?.toLowerCase().includes(lower) ||
                    f.answer?.toLowerCase().includes(lower) ||
                    f.id?.toString().includes(searchTerm.value)
                );
            }
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

        const openCreateModal = () => {
            isEditMode.value = false; currentId.value = null;
            Object.assign(form, { question: '', answer: '', intent_id: null, department_id: null });
            modalError.value = ''; showModal.value = true;
        };

        const openEditModal = (item) => {
            isEditMode.value = true; currentId.value = item.id;
            Object.assign(form, {
                question: item.question,
                answer: item.answer,
                intent_id: item.intent_id,
                department_id: item.department_id
            });
            modalError.value = ''; showModal.value = true;
        };

        const closeModal = () => showModal.value = false;

        const saveFAQ = async () => {
            if(!token) return;
            isSaving.value = true; modalError.value = '';
            try {
                if (isEditMode.value) {
                    await axios.put(`/api/updateFaqs/${currentId.value}`, form, { headers: { Authorization: `Bearer ${token}` }});
                    Swal.fire('Success', 'Updated successfully', 'success');
                } else {
                    await axios.post('/api/createFaqs', form, { headers: { Authorization: `Bearer ${token}` }});
                    Swal.fire('Success', 'Created successfully', 'success');
                }
                await getFAQs(); closeModal();
            } catch (err) {
                modalError.value = err.response?.data?.message || 'Save failed';
            } finally {
                isSaving.value = false;
            }
        };

        const deleteFAQs = async (id) => {
            const res = await Swal.fire({
                title: 'Delete this FAQ?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Delete'
            });
            if (res.isConfirmed && token) {
                try {
                    await axios.delete(`/api/deleteFaqs/${id}`, { headers: { Authorization: `Bearer ${token}` }});
                    await getFAQs();
                    Swal.fire('Deleted', '', 'success');
                } catch (e) { Swal.fire('Error', 'Delete failed', 'error'); }
            }
        };

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

        const triggerImport = () => importFile.value.click();
        const importFAQs = async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const fd = new FormData(); fd.append('file', file); fd.append('type', 'faq');
            try {
                await axios.post('/api/importExcel', fd, { headers: { 'Content-Type': 'multipart/form-data', Authorization: `Bearer ${token}` }});
                await getFAQs(); Swal.fire('Success', 'Imported', 'success');
            } catch (e) { Swal.fire('Error', 'Import failed', 'error'); }
            e.target.value = '';
        };

        onMounted(() => { getFAQs(); getDepartments(); getIntents(); });

        return {
            intents, departments, filteredFAQs, loading, searchTerm, selectedIntentId, selectedDepartmentId,
            showModal, isEditMode, isSaving, modalError, form,
            openCreateModal, openEditModal, closeModal, saveFAQ, deleteFAQs,
            exportFAQs, triggerImport, importFAQs, importFile
        };
    }
};
</script>

<style scoped>
/* 自定义颜色 (保持原样) */
.text-purple { color: #eae8ee !important; }
.bg-purple { background-color: #6f42c1 !important; }
.border-purple { border-color: #6f42c1 !important; }

/* 强制文字换行，防止长单词撑破表格 */
.text-break {
    word-break: break-word;
    overflow-wrap: break-word;
}

/* Modal 背景 */
.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1040;
}
.modal {
    z-index: 1050;
}

/* 🌟 新增：电脑平板表格内的滚动样式 */
.table-scrollable-content {
    max-height: 120px;       /* 限制高度，比如 120px，大约 5-6 行 */
    overflow-y: auto;        /* 内容多时出现滚动条 */
    white-space: pre-wrap;   /* 保留换行符 */
    padding-right: 5px;      /* 给滚动条留点位置 */

    /* 增加平滑滚动 */
    scrollbar-width: thin;   /* Firefox 细滚动条 */
    scrollbar-color: #dee2e6 transparent;
}

/* Webkit (Chrome/Safari/Edge) 滚动条美化 */
.table-scrollable-content::-webkit-scrollbar {
    width: 4px;
}
.table-scrollable-content::-webkit-scrollbar-track {
    background: transparent;
}
.table-scrollable-content::-webkit-scrollbar-thumb {
    background-color: #ced4da; /* 灰色滑块 */
    border-radius: 4px;
}
.table-scrollable-content::-webkit-scrollbar-thumb:hover {
    background-color: #adb5bd; /* 鼠标悬停变深 */
}

/* 🌟 新增：移动端 Answer 滚动框样式 */
.scrollable-answer {
    max-height: 200px; /* 这里限制高度，大约显示 8-10 行字 */
    overflow-y: auto;  /* 开启垂直滚动 */
    background-color: #f8f9fa; /* 浅灰背景 */
    border: 1px solid #e9ecef; /* 边框 */
    border-radius: 0.375rem;   /* 圆角 */
    padding: 0.75rem;          /* 内边距 */
    white-space: pre-wrap;     /* 保留换行符 */
    font-size: 0.9rem;         /* 稍微调小字体 */
    color: #495057;
    /* 增加平滑滚动体验 */
    -webkit-overflow-scrolling: touch;
}

/* 美化滚动条 (可选，让它在手机上看起来更细) */
.scrollable-answer::-webkit-scrollbar {
    width: 4px;
}
.scrollable-answer::-webkit-scrollbar-track {
    background: transparent;
}
.scrollable-answer::-webkit-scrollbar-thumb {
    background-color: #adb5bd;
    border-radius: 4px;
}
</style>
