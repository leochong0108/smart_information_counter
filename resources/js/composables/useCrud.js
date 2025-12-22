import { ref, reactive } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

/**
 * 通用 CRUD 逻辑封装
 * @param {string} resourceName - 资源名称 (例如 "Department", "FAQ")
 * @param {object} apiEndpoints - API 地址定义 { create, update(id), delete(id) }
 * @param {object} defaultFormState - 表单的初始空状态
 * @param {function} refreshListCallback - 操作成功后刷新列表的回调函数
 */
export function useCrud(resourceName, apiEndpoints, defaultFormState, refreshListCallback) {

    // 1. 状态定义
    const showModal = ref(false);
    const isEditMode = ref(false);
    const isSaving = ref(false);
    const modalError = ref('');
    const currentId = ref(null);
    const token = localStorage.getItem('sanctum_token');

    // 创建响应式表单，初始值为传入的默认值
    // 使用 JSON 解析深拷贝，防止引用污染
    const form = reactive(JSON.parse(JSON.stringify(defaultFormState)));

    // 2. 打开新建弹窗
    const openCreateModal = () => {
        isEditMode.value = false;
        currentId.value = null;
        modalError.value = '';

        // 重置表单
        Object.keys(defaultFormState).forEach(key => {
            form[key] = defaultFormState[key];
        });

        showModal.value = true;
    };

    // 3. 打开编辑弹窗
    const openEditModal = (itemData) => {
        isEditMode.value = true;
        currentId.value = itemData.id;
        modalError.value = '';

        // 填充表单 (只填充 defaultFormState 中定义的字段)
        Object.keys(defaultFormState).forEach(key => {
            form[key] = itemData[key] ?? '';
        });

        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
    };

    // 4. 保存逻辑 (Create / Update)
    const saveItem = async () => {
        if (!token) return;
        isSaving.value = true;
        modalError.value = '';

        try {
            if (isEditMode.value) {
                // Update
                const url = apiEndpoints.update(currentId.value);
                await axios.put(url, form, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                Swal.fire('Updated', `${resourceName} details updated.`, 'success');
            } else {
                // Create
                const url = apiEndpoints.create;
                await axios.post(url, form, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                Swal.fire('Created', `New ${resourceName} added.`, 'success');
            }

            closeModal();
            if (refreshListCallback) await refreshListCallback();

        } catch (err) {
            console.error(err);
            modalError.value = err.response?.data?.message || 'Operation failed.';
        } finally {
            isSaving.value = false;
        }
    };

    // 5. 删除逻辑
    const deleteItem = async (id) => {
        const res = await Swal.fire({
            title: 'Are you sure?',
            text: `Deleting a ${resourceName} may affect associated data!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        });

        if (res.isConfirmed && token) {
            try {
                const url = apiEndpoints.delete(id);
                await axios.delete(url, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                Swal.fire('Deleted!', `${resourceName} removed.`, 'success');
                if (refreshListCallback) await refreshListCallback();
            } catch (err) {
                Swal.fire('Error', 'Delete failed.', 'error');
            }
        }
    };

    return {
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
    };
}
