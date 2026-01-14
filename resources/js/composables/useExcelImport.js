import { ref } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

export function useExcelImport() {
    const isLoading = ref(false);
    const importFileRef = ref(null);
    const token = localStorage.getItem('sanctum_token');


    const triggerImport = () => {
        if (importFileRef.value) {
            importFileRef.value.click();
        }
    };


    const handleFileUpload = async (event, type, onSuccess) => {
        const file = event.target.files[0];
        if (!file) return;


        const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'];
        if (!validTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv)$/)) {
             Swal.fire('Error', 'Please upload an Excel or CSV file.', 'error');
             return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('type', type);

        isLoading.value = true;

        try {
            await axios.post('/api/importExcel', formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                    'Authorization': `Bearer ${token}`,
                },
            });


            Swal.fire('Success', `${type}s imported successfully!`, 'success');


            if (onSuccess && typeof onSuccess === 'function') {
                await onSuccess();
            }

        } catch (error) {
            console.error(error);
            const msg = error.response?.data?.message || 'Import failed';
            Swal.fire('Error', msg, 'error');
        } finally {
            isLoading.value = false;
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
