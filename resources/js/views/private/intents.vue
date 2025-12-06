<template>
<div class="container-fluid py-4">

    <!-- 1. Header & Actions -->
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-6 mb-3 mb-md-0">
            <h1 class="h3 mb-0 text-gray-800">Intent Management</h1>
        </div>
        <div class="col-12 col-md-6 d-flex flex-wrap justify-content-md-end gap-2">
            <button @click="exportIntents" class="btn btn-success flex-grow-1 flex-md-grow-0">
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

    <!-- 2. Search & Filter -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body p-3">
            <div class="row g-3">
                <!-- Search -->
                <div class="col-12 col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            v-model="searchTerm"
                            class="form-control border-start-0 bg-light"
                            placeholder="Search intent name, description..."
                        />
                    </div>
                </div>

                <!-- Intent Filter (Dropdown selection) -->
                <div class="col-6 col-md-3">
                    <select v-model="selectedIntentId" class="form-select">
                        <option value="">All Intents</option>
                        <option v-for="intent in intents" :key="intent.id" :value="intent.id">
                            {{ intent.intent_name }}
                        </option>
                    </select>
                </div>

                <!-- Department Filter -->
                <div class="col-6 col-md-4">
                    <select v-model="selectedDepartmentId" class="form-select">
                        <option value="">All Departments</option>
                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                            {{ dept.name }}
                        </option>
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
    <div v-else-if="!filteredIntents.length" class="text-center py-5 text-muted">
        <i class="bi bi-diagram-2 fs-1"></i>
        <p>No Intents found.</p>
    </div>

    <div v-else>

        <!-- 📱 MOBILE VIEW: Cards (手机端) -->
        <div class="d-block d-md-none">
            <div v-for="intent in filteredIntents" :key="intent.id" class="card shadow-sm mb-3 border-0">
                <div class="card-body">
                    <!-- Header: ID & Actions -->
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="badge bg-light text-secondary">#{{ intent.id }}</span>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" @click="openEditModal(intent)">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" @click="deleteIntent(intent.id)">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Intent Name -->
                    <h6 class="fw-bold text-dark mb-2">{{ intent.intent_name }}</h6>

                    <!-- Description (Scrollable) -->
                    <!-- 即使之前列表没显示，现在加上描述会让界面更丰富，且方便管理 -->
                    <div v-if="intent.description" class="scrollable-content bg-light p-3 rounded mb-3 text-secondary">
                        {{ intent.description }}
                    </div>

                    <!-- Department Tag -->
                    <div>
                        <span v-if="intent.department" class="badge bg-purple bg-opacity-10 text-purple border border-purple border-opacity-25 text-wrap text-start lh-sm">
                            <i class="bi bi-building"></i> {{ intent.department.name }}
                        </span>
                        <span v-else class="badge bg-secondary bg-opacity-10 text-secondary">
                            Unassigned
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 💻 DESKTOP/TABLET VIEW: Table (电脑端) -->
        <div class="d-none d-md-block card shadow border-0 rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-top mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="px-3 py-3" style="width: 5%">#</th>
                            <th class="px-3 py-3" style="width: 5%">ID</th>
                            <th class="px-3 py-3" style="width: 25%">Intent Name</th>
                            <th class="px-3 py-3" style="width: 35%">Description</th>
                            <th class="px-3 py-3" style="width: 20%">Department</th>
                            <th class="px-3 py-3 text-end" style="width: 10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(intent, index) in filteredIntents" :key="intent.id">
                            <td class="px-3 fw-bold text-secondary">{{ index + 1 }}.</td>
                            <td class="px-3 fw-bold text-secondary">{{ intent.id }}</td>

                            <td class="px-3">
                                <span class="fw-bold text-dark">{{ intent.intent_name }}</span>
                            </td>

                            <!-- Description: Scrollable -->
                            <td class="px-3">
                                <div class="table-scrollable-content text-secondary small">
                                    {{ intent.description || '-' }}
                                </div>
                            </td>

                            <!-- Department: Tag with wrap -->
                            <td class="px-3">
                                <span v-if="intent.department"
                                      class="badge bg-purple bg-opacity-10 text-purple text-wrap text-start lh-sm d-inline-block"
                                      style="max-width: 180px;">
                                    {{ intent.department.name }}
                                </span>
                                <span v-else class="text-muted small">-</span>
                            </td>

                            <td class="px-3 text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" @click="openEditModal(intent)">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteIntent(intent.id)">
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

    <!-- 4. Native Modal -->
    <div v-if="showModal" class="modal-backdrop fade show"></div>
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1" @click.self="closeModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi" :class="isEditMode ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
                        {{ isEditMode ? 'Edit Intent' : 'New Intent' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
                </div>
                <div class="modal-body p-4">
                    <form @submit.prevent="saveIntent">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Intent Name <span class="text-danger">*</span></label>
                            <input type="text" v-model="form.intent_name" class="form-control" placeholder="e.g. WiFi Issues" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <textarea v-model="form.description" class="form-control" rows="3" placeholder="Describe this intent..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Department</label>
                            <select v-model="form.department_id" class="form-select">
                                <option :value="null">None (Unassigned)</option>
                                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                    {{ dept.name }}
                                </option>
                            </select>
                        </div>

                        <div v-if="modalError" class="alert alert-danger py-2 small">{{ modalError }}</div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-light" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4" :disabled="isSaving">
                                <span v-if="isSaving" class="spinner-border spinner-border-sm me-1"></span>
                                {{ isEditMode ? 'Update' : 'Create' }}
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
import { useDataFetcher } from '../../services/useDataFetcher';
import { useExcelImport } from '../../services/useExcelImport';

export default {
    setup() {
        const token = localStorage.getItem('sanctum_token');

        // Data Fetching
        const { intents, departments, getIntents, getDepartments, loading } = useDataFetcher();
        const { importFileRef, triggerImport, handleFileUpload } = useExcelImport();

        // Filters
        const searchTerm = ref('');
        const selectedIntentId = ref('');
        const selectedDepartmentId = ref('');

        // Modal State
        const showModal = ref(false);
        const isEditMode = ref(false);
        const isSaving = ref(false);
        const modalError = ref('');
        const currentId = ref(null);

        // Form Data
        const form = reactive({
            intent_name: '',
            description: '',
            department_id: null
        });

        // Filter Logic
        const filteredIntents = computed(() => {
            let data = intents.value;

            // Search Text
            if (searchTerm.value) {
                const lower = searchTerm.value.toLowerCase();
                data = data.filter(i =>
                    i.intent_name?.toLowerCase().includes(lower) ||
                    i.description?.toLowerCase().includes(lower) ||
                    i.id?.toString().includes(searchTerm.value)
                );
            }

            // Intent Filter (Dropdown)
            if (selectedIntentId.value !== '') {
                const val = parseInt(selectedIntentId.value);
                data = data.filter(i => i.id === val);
            }

            // Department Filter
            if (selectedDepartmentId.value !== '') {
                const val = selectedDepartmentId.value === null ? null : parseInt(selectedDepartmentId.value);
                data = data.filter(i => i.department_id === val);
            }

            return data;
        });

        // --- Modal Logic ---
        const openCreateModal = async () => {
            // Ensure departments are fresh when opening modal
            if(departments.value.length === 0) await getDepartments();

            isEditMode.value = false; currentId.value = null;
            Object.assign(form, { intent_name: '', description: '', department_id: null });
            modalError.value = ''; showModal.value = true;
        };

        const openEditModal = async (item) => {
            if(departments.value.length === 0) await getDepartments();

            isEditMode.value = true; currentId.value = item.id;
            Object.assign(form, {
                intent_name: item.intent_name,
                description: item.description,
                department_id: item.department_id
            });
            modalError.value = ''; showModal.value = true;
        };

        const closeModal = () => showModal.value = false;

        // --- CRUD ---
        const saveIntent = async () => {
            if (!token) return;
            isSaving.value = true; modalError.value = '';

            try {
                if (isEditMode.value) {
                    await axios.put(`/api/updateIntents/${currentId.value}`, form, { headers: { Authorization: `Bearer ${token}` } });
                    Swal.fire('Updated', 'Intent updated successfully.', 'success');
                } else {
                    await axios.post('/api/createIntents', form, { headers: { Authorization: `Bearer ${token}` } });
                    Swal.fire('Created', 'New Intent created.', 'success');
                }
                await getIntents();
                closeModal();
            } catch (err) {
                modalError.value = err.response?.data?.message || 'Operation failed.';
            } finally {
                isSaving.value = false;
            }
        };

        const deleteIntent = async (id) => {
            const res = await Swal.fire({
                title: 'Are you sure?',
                text: "Deleting an intent might affect FAQs linked to it.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            });

            if (res.isConfirmed && token) {
                try {
                    await axios.delete(`/api/deleteIntents/${id}`, { headers: { Authorization: `Bearer ${token}` } });
                    await getIntents();
                    Swal.fire('Deleted!', 'Intent has been deleted.', 'success');
                } catch (err) {
                    Swal.fire('Error', 'Delete failed.', 'error');
                }
            }
        };

        // --- Import / Export ---
        const onImportFileChange = (event) => {
            handleFileUpload(event, 'intent', getIntents);
        };

        const exportIntents = () => {
            if (!intents.value.length) return Swal.fire('Info', 'No data', 'info');
            try {
                const data = intents.value.map(i => ({
                    ID: i.id,
                    Intent_Name: i.intent_name,
                    Description: i.description,
                    Department: i.department ? i.department.name : 'None'
                }));
                const ws = XLSX.utils.json_to_sheet(data);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Intents');
                saveAs(new Blob([XLSX.write(wb, { bookType: 'xlsx', type: 'array' })]), 'Intents.xlsx');
            } catch (e) {
                Swal.fire('Error', 'Export failed', 'error');
            }
        };

        onMounted(() => {
            getIntents();
            getDepartments();
        });

        return {
            intents, departments, filteredIntents, loading,
            searchTerm, selectedIntentId, selectedDepartmentId,
            showModal, isEditMode, isSaving, modalError, form,
            openCreateModal, openEditModal, closeModal, saveIntent, deleteIntent,
            importFileRef, triggerImport, onImportFileChange, exportIntents
        };
    }
};
</script>

<style scoped>
/* 样式复用自之前的页面，保持一致性 */
.text-purple { color: #efecf6 !important; }
.bg-purple { background-color: #6f42c1 !important; }
.border-purple { border-color: #6f42c1 !important; }

/* Modal 背景 */
.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1040;
}
.modal {
    z-index: 1050;
}

/* 📱 手机端：描述滚动框 */
.scrollable-content {
    max-height: 120px;
    overflow-y: auto;
    white-space: pre-wrap;
    font-size: 0.9rem;
    -webkit-overflow-scrolling: touch;
    border: 1px solid #dee2e6;
}

/* 💻 电脑端：表格内描述滚动框 */
.table-scrollable-content {
    max-height: 100px;
    overflow-y: auto;
    white-space: pre-wrap;
    padding-right: 5px;
    scrollbar-width: thin;
}

/* 滚动条美化 */
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
