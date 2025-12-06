<template>
<div class="container-fluid py-4">

    <!-- 1. Header & Actions -->
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-6 mb-3 mb-md-0">
            <h1 class="h3 mb-0 text-gray-800">Departments Management</h1>
        </div>
        <div class="col-12 col-md-6 d-flex flex-wrap justify-content-md-end gap-2">
            <button @click="exportDepartments" class="btn btn-success flex-grow-1 flex-md-grow-0">
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
                <div class="col-12 col-md-8">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                        <input
                            type="text"
                            v-model="searchTerm"
                            class="form-control border-start-0 bg-light"
                            placeholder="Search name, location, contact info..."
                        />
                    </div>
                </div>
                <!-- 这里的过滤器逻辑是：只看某一个特定部门 -->
                <div class="col-12 col-md-4">
                    <select v-model="selectedDepartmentId" class="form-select">
                        <option value="">All Departments</option>
                        <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                            {{ dept.name }}
                        </option>
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
    <div v-else-if="!filteredDepartments.length" class="text-center py-5 text-muted">
        <i class="bi bi-building-slash fs-1"></i>
        <p>No Departments found.</p>
    </div>

    <div v-else>

        <!-- 📱 MOBILE VIEW: Cards (只在手机显示) -->
        <div class="d-block d-md-none">
            <div v-for="dept in filteredDepartments" :key="dept.id" class="card shadow-sm mb-3 border-0">
                <div class="card-body">
                    <!-- Title & Actions -->
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <span class="badge bg-light text-secondary mb-1">#{{ dept.id }}</span>
                            <h5 class="fw-bold text-primary mb-0">{{ dept.name }}</h5>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" @click="openEditModal(dept)">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" @click="deleteDepartments(dept.id)">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Location & Contact (自动换行) -->
                    <div class="mb-3 small text-secondary">
                        <div class="d-flex align-items-start mb-1">
                            <i class="bi bi-geo-alt-fill me-2 mt-1 text-danger"></i>
                            <span class="text-break">{{ dept.location || 'No location set' }}</span>
                        </div>
                        <div class="d-flex align-items-start">
                            <i class="bi bi-telephone-fill me-2 mt-1 text-success"></i>
                            <span class="text-break">{{ dept.contact_info || 'No contact info' }}</span>
                        </div>
                    </div>

                    <!-- Description (可滚动) -->
                    <div v-if="dept.description" class="scrollable-content bg-light p-3 rounded text-dark">
                        {{ dept.description }}
                    </div>
                    <div v-else class="text-muted small fst-italic ps-1">
                        No description available.
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
                            <th class="px-3 py-3" style="width: 5%">#</th>
                            <th class="px-3 py-3" style="width: 5%">ID</th>
                            <th class="px-3 py-3" style="width: 20%">Name</th>
                            <th class="px-3 py-3" style="width: 30%">Description</th>
                            <th class="px-3 py-3" style="width: 20%">Location</th>
                            <th class="px-3 py-3" style="width: 10%">Contact</th>
                            <th class="px-3 py-3 text-end" style="width: 10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(dept,index) in filteredDepartments" :key="dept.id">
                            <td class="px-3 fw-bold text-secondary">{{ index + 1 }}.</td>
                            <td class="px-3 fw-bold text-secondary">{{ dept.id }}</td>

                            <td class="px-3">
                                <span class="fw-bold text-primary">{{ dept.name }}</span>
                            </td>

                            <!-- Description: 固定高度滚动 -->
                            <td class="px-3">
                                <div class="table-scrollable-content text-secondary small">
                                    {{ dept.description || '-' }}
                                </div>
                            </td>

                            <!-- Location: 自动换行 -->
                            <td class="px-3">
                                <div class="text-wrap" style="max-width: 200px;">
                                    <i class="bi bi-geo-alt text-danger me-1 small"></i>
                                    {{ dept.location || '-' }}
                                </div>
                            </td>

                            <!-- Contact: 自动换行 -->
                            <td class="px-3">
                                <div class="text-wrap" style="max-width: 150px;">
                                    <i class="bi bi-telephone text-success me-1 small"></i>
                                    {{ dept.contact_info || '-' }}
                                </div>
                            </td>

                            <td class="px-3 text-end">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary" @click="openEditModal(dept)">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" @click="deleteDepartments(dept.id)">
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

    <!-- 4. Modal (Create/Edit Form) -->
    <div v-if="showModal" class="modal-backdrop fade show"></div>
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1" @click.self="closeModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi" :class="isEditMode ? 'bi-pencil-square' : 'bi-plus-circle'"></i>
                        {{ isEditMode ? 'Edit Department' : 'New Department' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
                </div>
                <div class="modal-body p-4">
                    <form @submit.prevent="saveDepartment">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Department Name <span class="text-danger">*</span></label>
                            <input type="text" v-model="form.name" class="form-control" placeholder="e.g. IT Department" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <textarea v-model="form.description" class="form-control" rows="3" placeholder="Describe the department..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Location</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                                <input type="text" v-model="form.location" class="form-control" placeholder="e.g. Block A, Level 2">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Contact Info</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                <input type="text" v-model="form.contact_info" class="form-control" placeholder="e.g. +6012-3456789 or email@example.com">
                            </div>
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
import { useDataFetcher } from '../../services/dataFetcher';
import { useExcelImport } from '../../services/useExcelImport';

export default {
    setup() {
        const token = localStorage.getItem('sanctum_token');

        // Use Data Fetcher
        const { departments, getDepartments, getFAQs, loading } = useDataFetcher(); // getFAQs needed if deletion affects FAQs
        // Use Excel Import Service
        const { importFileRef, triggerImport, handleFileUpload } = useExcelImport();

        // Filters
        const searchTerm = ref('');
        const selectedDepartmentId = ref('');

        // Modal State
        const showModal = ref(false);
        const isEditMode = ref(false);
        const isSaving = ref(false);
        const modalError = ref('');
        const currentId = ref(null);

        // Form Data
        const form = reactive({
            name: '',
            description: '',
            location: '',
            contact_info: ''
        });

        // Computed: Filter Logic
        const filteredDepartments = computed(() => {
            let data = departments.value;

            if (searchTerm.value) {
                const lower = searchTerm.value.toLowerCase();
                data = data.filter(d =>
                    d.name?.toLowerCase().includes(lower) ||
                    d.description?.toLowerCase().includes(lower) ||
                    d.location?.toLowerCase().includes(lower) ||
                    d.contact_info?.toLowerCase().includes(lower) ||
                    d.id?.toString().includes(searchTerm.value)
                );
            }

            if (selectedDepartmentId.value !== '') {
                const val = parseInt(selectedDepartmentId.value);
                data = data.filter(d => d.id === val);
            }

            return data;
        });

        // --- Modal Actions ---
        const openCreateModal = () => {
            isEditMode.value = false; currentId.value = null;
            Object.assign(form, { name: '', description: '', location: '', contact_info: '' });
            modalError.value = ''; showModal.value = true;
        };

        const openEditModal = (item) => {
            isEditMode.value = true; currentId.value = item.id;
            Object.assign(form, {
                name: item.name,
                description: item.description,
                location: item.location,
                contact_info: item.contact_info
            });
            modalError.value = ''; showModal.value = true;
        };

        const closeModal = () => showModal.value = false;

        // --- CRUD Actions ---
        const saveDepartment = async () => {
            if(!token) return;
            isSaving.value = true; modalError.value = '';
            try {
                if (isEditMode.value) {
                    await axios.put(`/api/updateDepartments/${currentId.value}`, form, { headers: { Authorization: `Bearer ${token}` } });
                    Swal.fire('Updated', 'Department details updated.', 'success');
                } else {
                    await axios.post('/api/createDepartments', form, { headers: { Authorization: `Bearer ${token}` } });
                    Swal.fire('Created', 'New department added.', 'success');
                }
                await getDepartments();
                closeModal();
            } catch (err) {
                modalError.value = err.response?.data?.message || 'Action failed.';
            } finally {
                isSaving.value = false;
            }
        };

        const deleteDepartments = async (id) => {
            const res = await Swal.fire({
                title: 'Are you sure?',
                text: "Deleting a department may affect associated FAQs!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            });

            if (res.isConfirmed && token) {
                try {
                    await axios.delete(`/api/deleteDepartments/${id}`, { headers: { Authorization: `Bearer ${token}` } });
                    await getDepartments();
                    Swal.fire('Deleted!', 'Department removed.', 'success');
                } catch (err) {
                    Swal.fire('Error', 'Delete failed.', 'error');
                }
            }
        };

        // --- Import / Export ---
        const onImportFileChange = (event) => {
            handleFileUpload(event, 'department', getDepartments);
        };

        const exportDepartments = () => {
            if (!departments.value.length) return Swal.fire('Info', 'No data', 'info');
            try {
                const data = departments.value.map(d => ({
                    ID: d.id, Name: d.name, Description: d.description,
                    Location: d.location, Contact: d.contact_info
                }));
                const ws = XLSX.utils.json_to_sheet(data);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, 'Departments');
                saveAs(new Blob([XLSX.write(wb, { bookType: 'xlsx', type: 'array' })]), 'Departments.xlsx');
            } catch (e) {
                Swal.fire('Error', 'Export failed', 'error');
            }
        };

        onMounted(() => {
            getDepartments();
        });

        return {
            departments, filteredDepartments, loading,
            searchTerm, selectedDepartmentId,
            showModal, isEditMode, isSaving, modalError, form,
            openCreateModal, openEditModal, closeModal, saveDepartment, deleteDepartments,
            importFileRef, triggerImport, onImportFileChange, exportDepartments
        };
    }
};
</script>

<style scoped>
/* 强制换行 */
.text-break {
    word-break: break-word;
    overflow-wrap: break-word;
}

/* Modal 遮罩 */
.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1040;
}
.modal {
    z-index: 1050;
}

/* 📱 手机端：卡片内的描述滚动 */
.scrollable-content {
    max-height: 150px;
    overflow-y: auto;
    white-space: pre-wrap;
    font-size: 0.9rem;
    -webkit-overflow-scrolling: touch;
    border: 1px solid #f0f0f0;
}

/* 💻 电脑端：表格内的描述滚动 */
.table-scrollable-content {
    max-height: 100px; /* 描述通常不需要像 FAQ 答案那么高 */
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
