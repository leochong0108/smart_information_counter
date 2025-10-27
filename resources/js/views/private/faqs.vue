<!-- <template>
  <div>
    <h1 class="text-2xl font-bold mb-4">FAQ Management</h1>
    <button @click="fetchFaqs" class="btn btn-primary" style="margin-bottom: 10px;">+ Add New FAQs</button>

    <table class="w-full border">
      <thead>
        <tr class="bg-gray-200">
          <th class="p-2 border">Question</th>
          <th class="p-2 border">Answer</th>
          <th class="p-2 border">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="faq in faqs" :key="faq.id">
          <td class="border p-2">{{ faq.question }}</td>
          <td class="border p-2">{{ faq.answer }}</td>
          <td class="border p-2">
            <button class="btn btn-primary">Edit</button>
            <button @click="deleteFaq(faq.id)" class="btn btn-danger" style="margin-left: 5px;">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const faqs = ref([])

const fetchFaqs = async () => {

  const token = localStorage.getItem('sanctum_token');

    if (token) {
          const res = await axios.get('/api/allFaqs', {
            headers: { Authorization: `Bearer ${token}` }
        })
        faqs.value = res.data
    }

}

const deleteFaq = async (id) => {

const token = localStorage.getItem('sanctum_token');

if (token) {
    await axios.delete(`/api/faqs/${id}`, {
        headers: { Authorization: `Bearer ${token}` }
    })
}

  fetchFaqs()
}

onMounted(fetchFaqs)
</script> -->

<template>
<div class="d-flex align-items-center justify-content-between mb-5">
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
                    <td v-else>{{ FAQ.intent.name }}</td>
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
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import * as XLSX from 'xlsx';
import { saveAs } from 'file-saver';

export default {
    setup() {
        const FAQs = ref([]);
        const departments = ref([]);
        const error = ref(null);
        const token = localStorage.getItem('sanctum_token');
        const importFile = ref(null);

        const getFAQs = async () => {

        if(token){
                try {
                    const response = await axios.get('/api/allFaqs', {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    FAQs.value = response.data;
                    console.log(FAQs.value);
                } catch (err) {
                    error.value = err.response?.data?.message || 'Error fetching FAQs';
                }
            };
        };

        const getDepartments = async () => {

        if(token){
                try {
                    const response = await axios.get('/api/allDepartments', {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    departments.value = response.data;
                    console.log(departments.value);
                } catch (err) {
                    error.value = err.response?.data?.message || 'Error fetching departments';
                }
            };
        };


        const createFAQs = async () => {

            await getDepartments();

            const deptOptions = departments.value.map(dept => `<option value="${dept.id}">${dept.name}</option>`).join('');

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
                                <input id="intent" class="swal2-input" placeholder="Intent">
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
                                <input id="intent" class="swal2-input" placeholder="Intent" value="${FAQs.intent_id}">
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
