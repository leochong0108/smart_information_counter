<template>
<div class="container-fluid py-4">

    <!-- 1. 顶部 Header & 批量操作按钮 -->


    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-6 mb-3 mb-md-0">
            <h1 class="h3 mb-0 text-gray-800">
                Failed Logs
                <span class="badge bg-danger fs-6 align-middle rounded-pill ms-2">{{ filteredFails.length }}</span>
            </h1>
        </div>
        <div class="col-12 col-md-6 d-flex flex-wrap justify-content-md-end gap-2">
            <select v-model="selectedRemark" class="form-select flex-grow-1 flex-md-grow-0 shadow-sm w-auto">
                <option value="">All Remarks</option>
                <option v-for="remark in uniqueRemarks" :key="remark" :value="remark">
                    {{ remark }}
                </option>
            </select>
            <button
                @click="markSelectedAsChecked"
                class="btn btn-primary flex-grow-1 flex-md-grow-0"
                :class="{ 'disabled-btn': selectedLogIds.length === 0 }"
                :disabled="selectedLogIds.length === 0"
            >
                <i class="bi bi-check2-square me-2"></i>
                <span v-if="selectedLogIds.length > 0">
                    Mark {{ selectedLogIds.length }} Checked
                </span>
                <span v-else>
                    Select logs to mark
                </span>
            </button>
        </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-danger" role="status"></div>
        <p class="text-muted mt-2">Loading data...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger shadow-sm">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ error }}
    </div>

    <!-- Empty State (根据筛选结果显示) -->
    <div v-else-if="filteredFails.length === 0" class="text-center py-5">
        <div class="mb-3 text-muted display-1"><i class="bi bi-filter-circle"></i></div>
        <h4 class="text-muted">No logs found</h4>
        <p class="text-muted">Try changing the filter or there are no failed logs.</p>
    </div>

    <!-- Content Area -->
    <div v-else>

        <!-- 📱 MOBILE VIEW: Cards -->
        <div class="d-block d-md-none">
            <div class="d-flex align-items-center mb-3 px-1">
                <div class="form-check">
                    <input class="form-check-input p-2" type="checkbox" id="mobileSelectAll"
                           :checked="isAllSelected" @change="toggleSelectAll">
                    <label class="form-check-label fw-bold ms-2" for="mobileSelectAll">
                        Select Visible
                    </label>
                </div>
            </div>

            <!-- 🌟 修改：遍历 filteredFails -->
            <div v-for="fail in filteredFails" :key="fail.id"
                 class="card shadow-sm mb-3 border-0 transition-hover"
                 :class="{'border-primary border-2': selectedLogIds.includes(fail.id)}">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <input type="checkbox" class="form-check-input p-2 me-3"
                               :value="fail.id" v-model="selectedLogIds">
                        <span class="badge bg-light text-secondary">#{{ fail.id }}</span>
                    </div>
                    <h6 class="text-muted text-uppercase small fw-bold">User Question:</h6>
                    <div class="scrollable-content bg-light p-3 rounded mb-2 text-dark fw-medium border">
                        {{ fail.question_text }}
                    </div>
                    <div class="d-flex gap-2 flex-wrap mb-2">
                        <span v-if="fail.remark" class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 text-wrap text-start lh-sm">
                            <i class="bi bi-info-circle"></i> {{ fail.remark }}
                        </span>
                    </div>

                    <!-- 如果是系统错误，禁用创建 FAQ 按钮 (可选优化) -->
                    <button v-if="!fail.remark?.includes('System Error')" class="btn btn-outline-primary w-100" @click="openConvertModal(fail)">
                        <i class="bi bi-magic me-2"></i> Create FAQ
                    </button>
                    <button v-else class="btn btn-outline-secondary w-100" disabled>
                        <i class="bi bi-exclamation-triangle"></i> Cannot Convert Error
                    </button>
                </div>
            </div>
        </div>

        <!-- 💻 DESKTOP VIEW: Table -->
        <div class="d-none d-md-block card shadow border-0 rounded-3 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="px-4 py-3 text-center" style="width: 5%">
                                <input class="form-check-input" type="checkbox"
                                       :checked="isAllSelected" @change="toggleSelectAll">
                            </th>
                            <th class="px-4 py-3">#</th>
                            <th class="px-3 py-3" style="width: 5%">ID</th>
                            <th class="px-3 py-3" style="width: 45%">User Question</th>
                            <th class="px-3 py-3" style="width: 20%">Details (Remark)</th>
                            <th class="px-3 py-3 text-center" style="width: 10%">Status</th>
                            <th class="px-3 py-3 text-center" style="width: 15%">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- 🌟 修改：遍历 filteredFails -->
                        <tr v-for="(fail, index) in filteredFails" :key="fail.id"
                            :class="{'table-active': selectedLogIds.includes(fail.id)}">
                            <td class="px-4 text-center">
                                <input class="form-check-input" type="checkbox" :value="fail.id" v-model="selectedLogIds">
                            </td>
                            <td class="px-4 fw-bold text-secondary">{{ index + 1 }}.</td>
                            <td class="px-3 fw-bold text-secondary">{{ fail.id }}</td>

                            <td class="px-3">
                                <div class="table-scrollable-content fw-medium text-dark">
                                    {{ fail.question_text }}
                                </div>
                            </td>

                            <td class="px-3">
                                <div v-if="fail.remark" class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1 rounded text-wrap text-start lh-sm" style="max-width: 200px;">
                                    {{ fail.remark }}
                                </div>
                                <span v-else class="text-muted small">-</span>
                            </td>

                            <td class="px-3 text-center">
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 rounded-pill">
                                    Unanswered
                                </span>
                            </td>

                            <td class="px-3 text-center">
                                <!-- 只有非系统错误才显示 Create FAQ -->
                                <button v-if="!fail.remark?.includes('System Error')" class="btn btn-sm btn-primary px-3 shadow-sm" @click="openConvertModal(fail)">
                                    <i class="bi bi-plus-circle me-1"></i> Create FAQ
                                </button>
                                <span v-else class="text-muted small fst-italic">System Log</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal (保持不变) -->
    <div v-if="showModal" class="modal-backdrop fade show"></div>
    <div v-if="showModal" class="modal fade show d-block" tabindex="-1" @click.self="closeModal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Convert to FAQ</h5>
                    <button type="button" class="btn-close btn-close-white" @click="closeModal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        Creating this FAQ will automatically mark Log #{{ currentLogId }} as checked.
                    </div>
                    <form @submit.prevent="saveConvertedFAQ">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Question</label>
                            <input type="text" v-model="form.question" class="form-control bg-light" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Answer</label>
                            <textarea v-model="form.answer" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Intent</label>
                                <select v-model="form.intent_id" class="form-select">
                                    <option :value="null">None</option>
                                    <option v-for="i in intents" :key="i.id" :value="i.id">{{ i.intent_name }}</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Department</label>
                                <select v-model="form.department_id" class="form-select">
                                    <option :value="null">None</option>
                                    <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div v-if="modalError" class="alert alert-danger py-2 small">{{ modalError }}</div>
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-light" @click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-success px-4" :disabled="isSaving">
                                {{ isSaving ? 'Saving...' : 'Create & Mark Checked' }}
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
import { useDataFetcher } from '../../services/dataFetcher';
import { useFailedLogStore } from '../../services/useFailsLog';

export default {
    setup() {
        const { refreshFailedLogs } = useFailedLogStore();
        const { intents, departments, getIntents, getDepartments } = useDataFetcher();
        const token = localStorage.getItem('sanctum_token');

        const fails = ref([]);
        const loading = ref(true);
        const error = ref(null);
        const selectedLogIds = ref([]);

        // 🌟 新增：Filter State
        const selectedRemark = ref('');

        const showModal = ref(false);
        const isSaving = ref(false);
        const modalError = ref('');
        const currentLogId = ref(null);

        const form = reactive({
            question: '', answer: '', intent_id: null, department_id: null
        });

        // 🌟 新增：自动提取所有不重复的 Remarks
        const uniqueRemarks = computed(() => {
            if (!fails.value) return [];
            // map提取remark -> filter去空 -> Set去重 -> 排序
            const remarks = fails.value.map(f => f.remark).filter(r => r);
            return [...new Set(remarks)].sort();
        });

        // 🌟 新增：过滤后的列表
        const filteredFails = computed(() => {
            if (!selectedRemark.value) return fails.value;
            return fails.value.filter(f => f.remark === selectedRemark.value);
        });

        // 🌟 修改：全选逻辑改为“全选当前可见的”
        const isAllSelected = computed(() => {
            return filteredFails.value.length > 0 &&
                   filteredFails.value.every(f => selectedLogIds.value.includes(f.id));
        });

        const getFail = async () => {
            loading.value = true; error.value = null;
            try {
                const logs = await refreshFailedLogs();
                fails.value = logs;
                selectedLogIds.value = [];
            } catch (err) {
                error.value = 'Failed to load logs.';
                fails.value = [];
            } finally {
                loading.value = false;
            }
        };

        const toggleSelectAll = () => {
            if (isAllSelected.value) {
                // 取消全选：从 selectedLogIds 中移除当前可见的 IDs
                // 这样不会影响到其他被过滤掉但已选中的项目（虽然在这个简单场景下清空也没事，但这样做更严谨）
                const visibleIds = filteredFails.value.map(f => f.id);
                selectedLogIds.value = selectedLogIds.value.filter(id => !visibleIds.includes(id));
            } else {
                // 全选：把当前可见的所有 IDs 加进去
                const visibleIds = filteredFails.value.map(f => f.id);
                // 使用 Set 去重，防止重复添加
                selectedLogIds.value = [...new Set([...selectedLogIds.value, ...visibleIds])];
            }
        };

        const markSelectedAsChecked = async () => {
            if (!selectedLogIds.value.length) return;
            try {
                await axios.post('/api/mark-failed-logs', { ids: selectedLogIds.value }, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                Swal.fire({ icon: 'success', title: 'Marked!', timer: 1500, showConfirmButton: false });
                await getFail();
            } catch (err) { Swal.fire('Error', 'Action failed', 'error'); }
        };

        const openConvertModal = async (failLog) => {
            if (departments.value.length === 0) await getDepartments();
            if (intents.value.length === 0) await getIntents();
            currentLogId.value = failLog.id;
            form.question = failLog.question_text;
            form.answer = ''; form.intent_id = null; form.department_id = null;
            modalError.value = ''; showModal.value = true;
        };

        const closeModal = () => showModal.value = false;

        const saveConvertedFAQ = async () => {
            if (!token) return;
            isSaving.value = true; modalError.value = '';
            try {
                await axios.post(`/api/insertAndMark/${currentLogId.value}`, form, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                Swal.fire('Success', 'FAQ created.', 'success');
                await getFail(); closeModal();
            } catch (err) {
                modalError.value = err.response?.data?.message || 'Conversion failed.';
            } finally { isSaving.value = false; }
        };

        onMounted(() => { getFail(); });

        return {
            fails, loading, error, selectedLogIds,
            selectedRemark, uniqueRemarks, filteredFails, isAllSelected, // Export new refs
            toggleSelectAll, markSelectedAsChecked,
            showModal, isSaving, modalError, form, currentLogId,
            openConvertModal, closeModal, saveConvertedFAQ, intents, departments
        };
    }
};
</script>

<style scoped>
.disabled-btn {
    background-color: #e9ecef;
    border-color: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
    box-shadow: none !important;
}

.scrollable-content {
    max-height: 150px;
    overflow-y: auto;
    white-space: pre-wrap;
    font-size: 0.95rem;
    -webkit-overflow-scrolling: touch;
}

.table-scrollable-content {
    max-height: 80px;
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

.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1040;
}
.modal {
    z-index: 1050;
}
</style>
