<template>
<div class="container-fluid">
    <div class="row">
    <div class="col-12 d-flex align-items-center justify-content-between mb-2">
        <h1>FAQs Management</h1>
        <div>
            <button @click="exportFAQs" class="btn btn-success me-2">
                <i class="fas fa-file-export"></i> Export Excel
            </button>
            <input type="file" ref="importFile" style="display:none" @change="importFAQs" />
            <button @click="triggerImport" class="btn btn-secondary me-2">
                <i class="fas fa-file-import"></i> Import Excel
            </button>
            <button @click="createFAQs" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> + Add New
            </button>
        </div>
    </div>
    </div>

<!-- 🟢 NEW: Search and Filter Row -->
<div class="row mb-3">
    <!-- Search Bar -->
    <div class="col-md-5">
        <input
            type="text"
            v-model="searchTerm"
            class="form-control"
            placeholder="Search by question, answer, or ID..."
        />
    </div>

    <!-- Intent Filter -->
    <div class="col-md-3">
        <select v-model="selectedIntentId" class="form-select">
            <option value="">All Intents</option>
            <option
                v-for="intent in intents"
                :key="intent.id"
                :value="intent.id"
            >
                {{ intent.intent_name }}
            </option>
             <option :value="null">Unassigned Intent</option>
        </select>
    </div>

    <!-- Department Filter -->
    <div class="col-md-3">
        <select v-model="selectedDepartmentId" class="form-select">
            <option value="">All Departments</option>
            <option
                v-for="dept in departments"
                :key="dept.id"
                :value="dept.id"
            >
                {{ dept.name }}
            </option>
            <option :value="null">Unassigned Department</option>
        </select>
    </div>
</div>
<!-- 🟢 END NEW: Search and Filter Row -->


<div class="row">

    <div class="col-12 shadow-lg rounded-lg">
    <!-- Simplified loading check for brevity -->
    <div v-if="loading" class="text-center" style="padding: 13rem;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- 🟢 CHANGE: Check filteredFAQs length and display appropriate message -->
    <div v-if="!filteredFAQs.length && !loading">
        <p>
             <span v-if="FAQs.length === 0">No Data available. Please add some.</span>
             <span v-else>No FAQs match your current search/filter criteria.</span>
        </p>
    </div>

    <!-- 🟢 CHANGE: Use filteredFAQs for table data -->
    <div v-if="filteredFAQs.length" >
        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Question</th>
                    <th scope="col">Answer</th>
                    <th scope="col">Intent</th>
                    <th scope="col">Department</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="FAQ in filteredFAQs" :key="FAQ.id">
                    <td>{{ FAQ.id }}</td>
                    <td>{{ FAQ.question }}</td>
                    <td>{{ FAQ.answer }}</td>
                    <td v-if="FAQ.intent_id == null">-</td>
                    <td v-else>{{ FAQ.intent.intent_name }}</td>
                    <td v-if="FAQ.department_id == null">-</td>
                    <td v-else>{{ FAQ.department.name }}</td>
                    <td>
                        <div class="d-flex">
                        <button class="btn btn-success" @click="editFAQs(FAQ)"><i class="fas fa-edit"></i>&nbsp;Edit</button>
                        <button class="btn btn-danger ms-2" @click="deleteFAQs(FAQ.id)"><i class="fas fa-trash-alt"></i>&nbsp;Delete</button>
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
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';
import { saveAs } from 'file-saver';
import { useDataFetcher } from '../../services/dataFetcher';
// The composable is imported, but we'll use chained logic for this multi-filter component
import { useFilterableData } from '../../services/filter';

export default {
    setup() {
        const error = ref(null);
        const token = localStorage.getItem('sanctum_token');
        const importFile = ref(null);

        const {
            intents,
            FAQs, // This holds ALL the FAQs
            departments,
            getFAQs,
            getIntents,
            getDepartments,
            loading
        } = useDataFetcher();

        // 🟢 NEW: Reactive state for the text search and dropdown filters
        const searchTerm = ref('');
        const selectedIntentId = ref(''); // Bound to the Intent dropdown
        const selectedDepartmentId = ref(''); // Bound to the Department dropdown

        // 🟢 NEW: Chained filtering logic in a single computed property
        const filteredFAQs = computed(() => {
            let data = FAQs.value;

            // A. Apply Text Search (Question, Answer, ID)
            if (searchTerm.value) {
                const searchLower = searchTerm.value.toLowerCase();
                data = data.filter(faq =>
                    faq.question?.toLowerCase().includes(searchLower) ||
                    faq.answer?.toLowerCase().includes(searchLower) ||
                    faq.id?.toString().includes(searchTerm.value)
                );
            }

            // B. Apply Intent Filter
            // Filter only if a specific Intent ID (number or null for unassigned) is selected
            if (selectedIntentId.value !== '') {
                 // Intent IDs are usually numbers, unless 'null' is passed for unassigned
                const intentFilterValue = selectedIntentId.value === null ? null : parseInt(selectedIntentId.value);
                data = data.filter(faq => faq.intent_id === intentFilterValue);
            }

            // C. Apply Department Filter
            // Filter only if a specific Department ID (number or null for unassigned) is selected
            if (selectedDepartmentId.value !== '') {
                // Department IDs are usually numbers, unless 'null' is passed for unassigned
                const deptFilterValue = selectedDepartmentId.value === null ? null : parseInt(selectedDepartmentId.value);
                data = data.filter(faq => faq.department_id === deptFilterValue);
            }

            return data;
        });

        // 💡 NOTE: The existing create/edit logic uses the `departments` and `intents` data
        // fetched on mount, so no change is needed there.

        const createFAQs = async () => {

            await getDepartments();
            await getIntents();

            // Modified to use selectedIntentId/selectedDepartmentId and handle null values
            const deptOptions = departments.value.map(dept => `<option value="${dept.id}">${dept.name}</option>`).join('');
            const intentOptions = intents.value.map(intent => `<option value="${intent.id}">${intent.intent_name}</option>`).join('');

            const { value: formValues } = await Swal.fire({
                title: 'Create',
                html:
                    // ... (HTML unchanged) ...
                    // Added option value="null" to allow unassignment in creation
                    `<div class="swal2-input-group">
                        <label for="question">Question</label>
                        <input id="question" class="swal2-input" placeholder="Question">
                    </div>
                    <div class="swal2-input-group">
                        <label for="answer">Answer</label>
                        <input id="answer" class="swal2-input" placeholder="Answer">
                    </div>
                    <div class="swal2-input-group">
                        <label for="intent">Intent</label>
                        <select id="intent" class="swal2-input" style="margin-left: 40px;">
                            <option value="" disabled selected>Select Intent</option>
                            <option value="null">None</option>
                            ${intentOptions}
                        </select>
                    </div>
                    <div class="swal2-input-group">
                        <label for="department" ">Department</label>
                        <select id="department" class="swal2-input" style="margin-left: 40px;">
                            <option value="" disabled selected>Select Department</option>
                            <option value="null">None</option>
                            ${deptOptions}
                        </select>
                    </div>`,
                focusConfirm: false,
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                customClass: { container: 'swal2-custom-wide'},

                preConfirm: () => {
                    const intentValue = document.getElementById('intent').value;
                    const departmentValue = document.getElementById('department').value;
                    return {
                        question: document.getElementById('question').value,
                        answer: document.getElementById('answer').value,
                        intent_id: intentValue === 'null' ? null : intentValue,
                        department_id: departmentValue === 'null' ? null : departmentValue,
                    };
                }
            });

            if (formValues) {
                if(token){
                    try {
                        await axios.post('/api/createFaqs', formValues, {
                            headers: { Authorization: `Bearer ${token}` }
                        });
                        await getFAQs();
                        Swal.fire('Created!', 'New FAQ has been created.', 'success');
                    } catch (err) {
                        Swal.fire('Error', err.response?.data?.message || 'Could not create FAQ', 'error');
                    }
                }
            }
        };

        const editFAQs = async (FAQs) => {
            await getDepartments();
            await getIntents();
            const { value: formValues } = await Swal.fire({
                title: 'Edit',
                html:
                    // ... (HTML unchanged) ...
                    // Updated to handle existing null values for selection
                    `<div class="swal2-input-group">
                        <label for="question">Question</label>
                        <input id="question" class="swal2-input" placeholder="Question" value="${FAQs.question}">
                    </div>
                    <div class="swal2-input-group">
                        <label for="answer">Answer</label>
                        <input id="answer" class="swal2-input" placeholder="Answer" value="${FAQs.answer}">
                    </div>
                    <div class="swal2-input-group">
                        <label for="intent">Intent</label>
                        <select id="intent" class="swal2-input" style="margin-left: 40px;">
                            <option value="" disabled>Select Intent</option>
                            <option value="null" ${FAQs.intent_id === null ? 'selected' : ''}>None</option>
                            ${intents.value.map(intent => `
                                <option value="${intent.id}" ${intent.id === FAQs.intent_id ? 'selected' : ''}>${intent.intent_name}</option>
                            `).join('')}
                        </select>
                    </div>
                    <div class="swal2-input-group">
                        <label for="department">Department</label>
                        <select id="department" class="swal2-input" style="margin-left: 40px;">
                            <option value="" disabled>Select Department</option>
                            <option value="null" ${FAQs.department_id === null ? 'selected' : ''}>None</option>
                            ${departments.value.map(dept => `
                                <option value="${dept.id}" ${dept.id === FAQs.department_id ? 'selected' : ''}>${dept.name}</option>
                            `).join('')}
                        </select>
                    </div>`,
                focusConfirm: false,
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                customClass: { container: 'swal2-custom-wide'},


                preConfirm: () => {
                    const intentValue = document.getElementById('intent').value;
                    const departmentValue = document.getElementById('department').value;
                    return {
                        question: document.getElementById('question').value,
                        answer: document.getElementById('answer').value,
                        intent_id: intentValue === 'null' ? null : intentValue,
                        department_id: departmentValue === 'null' ? null : departmentValue,
                    };
                }
            });

            if (formValues) {
                if(token){
                    try {
                        await axios.put(`/api/updateFaqs/${FAQs.id}`, formValues, {
                            headers: { Authorization: `Bearer ${token}` }
                        });
                        await getFAQs();
                        Swal.fire('Updated!', 'Faq details have been updated.', 'success');
                    } catch (err) {
                        Swal.fire('Error', err.response?.data?.message || 'Could not update', 'error');
                    }
                }
            }
        };

        const deleteFAQs = async (id) => {
            const result = await Swal.fire({
                title: 'Warning!',
                text: 'Do you want to continue?',
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
                        await axios.delete(`/api/deleteFaqs/${id}`, {
                            headers: { Authorization: `Bearer ${token}` }
                        });
                        await getFAQs();
                        Swal.fire('Deleted!', 'FAQ has been deleted.', 'success');
                    } catch (err) {
                        Swal.fire('Error', err.response?.data?.message || 'Could not delete', 'error');
                    }
                }
            }
        };

        const exportFAQs = () => {
            if (!FAQs.value.length) {
                Swal.fire('No Data', 'There is no FAQ data to export.', 'info');
                return;
            }

            try {
                // Map data into a flat array for Excel, including intent/dept names if available
                const exportData = FAQs.value.map(faq => ({
                    ID: faq.id,
                    Question: faq.question,
                    Answer: faq.answer,
                    Intent: faq.intent?.intent_name ?? 'No value',
                    Department: faq.department?.name ?? 'No value',
                }));

                // Convert JSON to worksheet
                const worksheet = XLSX.utils.json_to_sheet(exportData);
                const workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, worksheet, 'FAQs');

                // Generate and save file
                const excelBuffer = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
                const blob = new Blob([excelBuffer], {
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                });
                saveAs(blob, 'FAQs.xlsx');
            } catch (error) {
                console.error('Export Error:', error);
                Swal.fire('Error', 'Failed to export FAQs', 'error');
            }
        };

        const triggerImport = () => {
            importFile.value.click();
        };

        const importFAQs = async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', 'faq');

            if (token) {
                try {
                    await axios.post('/api/importExcel', formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                            Authorization: `Bearer ${token}`,
                        },
                    });
                    await getFAQs();
                    Swal.fire('Success', 'FAQs imported successfully', 'success');
                } catch (error) {
                    Swal.fire('Error', error.response?.data?.message || 'Import failed', 'error');
                }
            }
        };

        onMounted(() => {
            getFAQs();
            // Fetch filter data immediately on mount
            getDepartments();
            getIntents();
        });

        return {
            FAQs,
            departments,
            intents,
            error,
            loading,
            getFAQs,
            getDepartments,
            createFAQs,
            editFAQs,
            deleteFAQs,
            exportFAQs,
            triggerImport,
            importFAQs,
            importFile,

            // 🟢 NEW: Filter variables and the resulting array
            searchTerm,
            selectedIntentId,
            selectedDepartmentId,
            filteredFAQs,
        };
    }
};
</script>


<style>
/* ... (existing styles) ... */
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

    .swal2-input-group input {
        flex-grow: 1; /* Makes the input field take up the remaining space */
    }
</style>
