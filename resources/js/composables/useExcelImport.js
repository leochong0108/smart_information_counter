import { ref } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2'; // 假设你使用 SweetAlert2

export function useExcelImport() {
    const isLoading = ref(false);
    const importFileRef = ref(null); // 用于绑定隐藏的 input 元素
    const token = localStorage.getItem('sanctum_token');

    // 1. 触发文件选择框
    const triggerImport = () => {
        if (importFileRef.value) {
            importFileRef.value.click();
        }
    };

    // 2. 处理文件上传的核心逻辑
    // type: 'department' | 'intent' | 'faq'
    // onSuccess: 上传成功后的回调函数 (通常用于刷新列表)
    const handleFileUpload = async (event, type, onSuccess) => {
        const file = event.target.files[0];
        if (!file) return;

        // 验证文件类型 (简单前端验证)
        const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'];
        if (!validTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv)$/)) {
             Swal.fire('Error', 'Please upload an Excel or CSV file.', 'error');
             return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', type); // 🔥 关键：传入后端需要的 type

        isLoading.value = true;

        try {
            await axios.post('/api/importExcel', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'Authorization': `Bearer ${token}`,
                },
            });

            // 成功提示
            Swal.fire('Success', `${type}s imported successfully!`, 'success');

            // 执行成功回调 (例如刷新列表)
            if (onSuccess && typeof onSuccess === 'function') {
                await onSuccess();
            }

        } catch (error) {
            console.error(error);
            const msg = error.response?.data?.message || 'Import failed';
            Swal.fire('Error', msg, 'error');
        } finally {
            isLoading.value = false;
            // 清空 input，防止无法连续上传同一个文件
            event.target.value = '';
        }
    };

    return {
        isLoading,
        importFileRef,
        triggerImport,
        handleFileUpload
    };
}
