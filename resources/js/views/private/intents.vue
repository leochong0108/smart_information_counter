<template>
<div class="container-fluid">
    <div class="row">
    <div class="col-12 d-flex align-items-center justify-content-between mb-2">
        <h1>Intent Management </h1>
        <div>
            <button @click="exportIntents" class="btn btn-success me-2">
                <i class="fas fa-file-export"></i> Export Excel
            </button>
            <input type="file" ref="importFile" style="display:none" @change="importIntents" />
            <button @click="triggerImport" class="btn btn-secondary me-2">
                <i class="fas fa-file-import"></i> Import Excel
            </button>
            <button @click="createIntent" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> + Add New
            </button>
        </div>
    </div>
</div>


<div class="row">

    <div class="col-12 ">
    <div v-if="loading" class="text-center" style="padding: 13rem;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div v-if="!intents.length && !loading">
        <p>No Intent Data available. Please add some.</p>
    </div>

    <div v-if="intents.length && !loading" >
        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Intent Name</th>
                    <th scope="col">Department</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="intent in intents" :key="intent.id">
                    <td>{{ intent.id }}</td>
                    <td>{{ intent.intent_name }}</td>
                    <td v-if="intent.department">
                        {{ intent.department.name }}
                    </td>
                    <td v-else>
                    -
                    </td>
                    <td>
                        <div class="d-flex">
                        <button class="btn btn-success" @click="editIntent(intent)"><i class="fas fa-edit"></i>&nbsp;Edit</button>
                        <button class="btn btn-danger ms-2" @click="deleteIntent(intent.id)"><i class="fas fa-trash-alt"></i>&nbsp;Delete</button>
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

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';
import { saveAs } from 'file-saver';
// Assuming useDataFetcher exposes a way to fetch intents and departments
import { useDataFetcher } from '../../services/dataFetcher';

export default {
    setup() {
        const error = ref(null);
        const loading = ref(true); // Added loading state
        const token = localStorage.getItem('sanctum_token');
        const importFile = ref(null);

        // Renamed FAQs to intents for clarity in this component
        const {
            intents,
            departments,
            getFAQs: originalGetFAQs, // Renamed to avoid confusion if it still exists
            getIntents, // Keep this for fetching Intents
            getDepartments

        }
        = useDataFetcher();

        // --- Fetching Data on Mount ---
        const fetchAllData = async () => {
             loading.value = true;
             try {
                 await getIntents();
                 await getDepartments(); // Fetch departments for dropdowns
             } catch (err) {
                 error.value = err;
             } finally {
                 loading.value = false;
             }
        };


        // --- Create Intent ---
        const createIntent = async () => {
            await getDepartments(); // Ensure departments are fresh

            const deptOptions = departments.value.map(dept => `<option value="${dept.id}">${dept.name}</option>`).join('');

            const { value: formValues } = await Swal.fire({
                title: 'Create New Intent',
                html:
                                `<div class="swal2-input-group">
                                    <label for="intent_name">Intent Name</label>
                                    <input id="intent_name" class="swal2-input" placeholder="intent name">
                                </div>
                                <div class="swal2-input-group">
                                    <label for="department">Department</label>
                                    <select id="department" class="swal2-input" style="margin-left: 40px;">
                                        <option value="">None</option>
                                        ${deptOptions}
                                    </select>
                                </div>`,
                focusConfirm: false,
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                customClass: { container: 'swal2-custom-wide'},

                preConfirm: () => {
                    const intentName = document.getElementById('intent_name').value;
                    const departmentId = document.getElementById('department').value;
                    if (!intentName) {
                        Swal.showValidationMessage('Intent Name is required');
                        return false;
                    }
                    return {
                        intent_name: intentName,
                        department_id: departmentId || null, // Use null if 'None' is selected
                    };
                }
            });

            if (formValues) {
                if(token){
                    try {
                        // API endpoint updated for creating Intents
                        await axios.post('/api/createIntents', formValues, {
                            headers: { Authorization: `Bearer ${token}` }
                        });
                        await getIntents(); // Refresh list
                        Swal.fire('Created!', 'New Intent has been created.', 'success');
                    } catch (err) {
                        Swal.fire('Error', err.response?.data?.message || 'Could not create Intent', 'error');
                    }
                }
            }
        };

        // --- Edit Intent ---
        const editIntent = async (intent) => {
            await getDepartments(); // Ensure departments are fresh

            const deptOptions = departments.value.map(dept => `
                <option value="${dept.id}" ${dept.id === intent.department_id ? 'selected' : ''}>${dept.name}</option>
            `).join('');

            const { value: formValues } = await Swal.fire({
                title: 'Edit Intent',
                html:
                                `<div class="swal2-input-group">
                                    <label for="intent_name">Intent Name</label>
                                    <input id="intent_name" class="swal2-input" placeholder="Intent Name" value="${intent.intent_name}">
                                </div>
                                <div class="swal2-input-group">
                                    <label for="department">Department</label>
                                    <select id="department" class="swal2-input" style="margin-left: 40px;">
                                        <option value="">None (Optional)</option>
                                        ${deptOptions}
                                    </select>
                                </div>`,
                focusConfirm: false,
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                customClass: { container: 'swal2-custom-wide'},

                preConfirm: () => {
                    const intentName = document.getElementById('intent_name').value;
                    const departmentId = document.getElementById('department').value;
                    if (!intentName) {
                        Swal.showValidationMessage('Intent Name is required');
                        return false;
                    }
                    return {
                        intent_name: intentName,
                        department_id: departmentId || null, // Use null if 'None' is selected
                    };
                }
            });

            if (formValues) {
                if(token){
                    try {
                        // API endpoint updated for updating Intents
                        await axios.put(`/api/updateIntents/${intent.id}`, formValues, {
                            headers: { Authorization: `Bearer ${token}` }
                        });
                        await getIntents(); // Refresh list
                        Swal.fire('Updated!', 'Intent details have been updated.', 'success');
                    } catch (err) {
                        Swal.fire('Error', err.response?.data?.message || 'Could not update Intent', 'error');
                    }
                }
            }
        };

        // --- Delete Intent ---
        const deleteIntent = async (id) => {
            const result = await Swal.fire({
                title: 'Warning!',
                text: 'Do you want to continue? This will permanently delete the intent.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
            });

            if (result.isConfirmed) {
                if(token){
                    try {
                        // API endpoint updated for deleting Intents
                        await axios.delete(`/api/deleteIntents/${id}`, {
                            headers: { Authorization: `Bearer ${token}` }
                        });
                        await getIntents(); // Refresh list
                        Swal.fire('Deleted!', 'Intent has been deleted.', 'success');
                    } catch (err) {
                        Swal.fire('Error', err.response?.data?.message || 'Could not delete Intent', 'error');
                    }
                }
            }
        };

        // --- EXPORT to Excel ---
        const exportIntents = () => {
            if (!intents.value.length) {
                Swal.fire('No Data', 'There is no Intent data to export.', 'info');
                return;
            }

            try {
                // Map data to include department name for clarity in export
                const exportData = intents.value.map(intent => ({
                    ID: intent.id,
                    Intent_Name: intent.intent_name,
                    Department_ID: intent.department_id,
                    Department_Name: intent.department ? intent.department.name : 'None',
                }));

                // Convert JSON to worksheet
                const worksheet = XLSX.utils.json_to_sheet(exportData);
                const workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, worksheet, 'Intents');

                // Generate and save file
                const excelBuffer = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
                const blob = new Blob([excelBuffer], {
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                });
                saveAs(blob, 'Intents.xlsx');
                Swal.fire('Exported!', 'Intent data successfully exported.', 'success');
            } catch (error) {
                console.error('Export Error:', error);
                Swal.fire('Error', 'Failed to export Intents', 'error');
            }
        };

        const triggerImport = () => {
            importFile.value.click();
        };

        // --- IMPORT from Excel ---
        const importIntents = async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            if (token) {
                try {
                    // API endpoint updated for importing Intents
                    // This endpoint should handle the Excel file and create/update Intents
                    await axios.post('/api/importIntents', formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                            Authorization: `Bearer ${token}`,
                        },
                    });
                    await getIntents();
                    Swal.fire('Success', 'Intents imported successfully', 'success');
                } catch (error) {
                    Swal.fire('Error', error.response?.data?.message || 'Import failed', 'error');
                }
            }
            // Reset the file input to allow re-importing the same file
            event.target.value = null;
        };

        onMounted(() => {
            fetchAllData();
        });

        return {
            intents,
            departments,
            error,
            loading,
            createIntent,
            editIntent,
            deleteIntent,
            exportIntents,
            triggerImport,
            importIntents,
            importFile,
        };
    }
};
</script>


<style>
/* Existing styles */
    .swal2-custom-wide .swal2-popup {
        width: 800px; /* Adjust this value to your desired width */
        max-width: 90vw; /* Ensures it's still responsive on smaller screens */
    }

    /* New styles to format the form with labels on the left */
    .swal2-input-group {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }

    .swal2-input-group label {
        flex-basis: 120px; /* Adjust this to change the label width */
        text-align: left;
        margin-right: 15px;
        font-weight: 500;
    }

    .swal2-input-group input, .swal2-input-group select {
        flex-grow: 1; /* Makes the input/select field take up the remaining space */
    }
</style>
