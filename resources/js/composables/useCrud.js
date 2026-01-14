import { ref, reactive } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

/**
 * @param {string} resourceName
 * @param {object} apiEndpoints
 * @param {object} defaultFormState
 * @param {function} refreshListCallback
 */
export function useCrud(resourceName, apiEndpoints, defaultFormState, refreshListCallback) {

    const showModal = ref(false);
    const isEditMode = ref(false);
    const isSaving = ref(false);
    const modalError = ref('');
    const currentId = ref(null);
    const token = localStorage.getItem('sanctum_token');

    const form = reactive(JSON.parse(JSON.stringify(defaultFormState)));

    const openCreateModal = () => {
        isEditMode.value = false;
        currentId.value = null;
        modalError.value = '';

        Object.keys(defaultFormState).forEach(key => {
            form[key] = defaultFormState[key];
        });

        showModal.value = true;
    };

    const openEditModal = (itemData) => {
        isEditMode.value = true;
        currentId.value = itemData.id;
        modalError.value = '';

        Object.keys(defaultFormState).forEach(key => {
            form[key] = itemData[key] ?? '';
        });

        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
    };

    const saveItem = async () => {
        if (!token) return;
        isSaving.value = true;
        modalError.value = '';

        try {
            if (isEditMode.value) {
                const url = apiEndpoints.update(currentId.value);
                await axios.put(url, form, {
                    headers: { Authorization: `Bearer ${token}` }
                });
                Swal.fire('Updated', `${resourceName} details updated.`, 'success');
            } else {
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
