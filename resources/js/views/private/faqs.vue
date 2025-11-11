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


<div class="row">

    <div class="col-12 ">
    <div v-if="loading" class="text-center" style="padding: 13rem;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div v-if="!FAQs.length">
        <p>No Data available. Please add some.</p>
    </div>

    <div v-if="FAQs.length" >
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
                <tr v-for="FAQ in FAQs" :key="FAQ.id">
                    <td>{{ FAQ.id }}</td>
                    <td>{{ FAQ.question }}</td>
                    <td>{{ FAQ.answer }}</td>
                    <td v-if="FAQ.intent_id == null">
                    -
                    </td>
                    <td v-else>{{ FAQ.intent.intent_name }}</td>
                    <td v-if="FAQ.department_id == null">
                    -
                    </td>
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
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';
import { saveAs } from 'file-saver';
import { useDataFetcher } from '../../services/dataFetcher';

export default {
    setup() {
        const error = ref(null);
        const token = localStorage.getItem('sanctum_token');
        const importFile = ref(null);

        //import the data and functions from other files
        const {
            intents,
            FAQs,
            departments,
            getFAQs,
            getIntents,
            getDepartments

        }
        = useDataFetcher();

        const createFAQs = async () => {

            await getDepartments();
            await getIntents();

            const deptOptions = departments.value.map(dept => `<option value="${dept.id}">${dept.name}</option>`).join('');
            const intentOptions = intents.value.map(intent => `<option value="${intent.id}">${intent.intent_name}</option>`).join('');

            const { value: formValues } = await Swal.fire({
                title: 'Create',
                html:
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
                                    ${intentOptions}
                                </select>
                            </div>
                            <div class="swal2-input-group">
                                <label for="department" ">Department</label>
                                <select id="department" class="swal2-input" style="margin-left: 40px;">
                                    <option value="" disabled selected>Select Department</option>
                                    ${deptOptions}
                                </select>
                            </div>`,
                focusConfirm: false,
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                customClass: { container: 'swal2-custom-wide'},

                preConfirm: () => {
                    return {
                        question: document.getElementById('question').value,
                        answer: document.getElementById('answer').value,
                        intent_id: document.getElementById('intent').value,
                        department_id: document.getElementById('department').value,
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
                                    <option value="">None</option>
                                    ${intents.value.map(intent => `
                                        <option value="${intent.id}" ${intent.id === FAQs.intent_id ? 'selected' : ''}>${intent.intent_name}</option>
                                    `).join('')}
                                </select>
                            </div>
                            <div class="swal2-input-group">
                                <label for="department">Department</label>
                                <select id="department" class="swal2-input" style="margin-left: 40px;">
                                    <option value="" disabled>Select Department</option>
                                    <option value="">None</option>
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
                    return {
                        question: document.getElementById('question').value,
                        answer: document.getElementById('answer').value,
                        intent_id: document.getElementById('intent').value,
                        department_id: document.getElementById('department').value,
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

        // 🟢 EXPORT to Excel (Frontend)
        const exportFAQs = () => {
            if (!FAQs.value.length) {
                Swal.fire('No Data', 'There is no FAQ data to export.', 'info');
                return;
            }

            try {
                // Map data into a flat array for Excel
                const exportData = FAQs.value.map(faq => ({
                    ID: faq.id,
                    Question: faq.question_text,
                    Answer: faq.answer_text,
                    Intent: faq.intent_id ?? 'No value',
                    Department: faq.department_id ?? 'No value',
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

        // keep your importFAQs the same
        const importFAQs = async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);

            if (token) {
                try {
                    await axios.post('/api/import', formData, {
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
        });

        return {
            FAQs,
            departments,
            intents,
            error,
            getFAQs,
            getDepartments,
            createFAQs,
            editFAQs,
            deleteFAQs,
            exportFAQs,
            triggerImport,
            importFAQs,
            importFile,
        };
    }
};
</script>


<style>
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
