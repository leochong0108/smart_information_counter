<template>
    <div class="d-flex align-items-center justify-content-between mb-5">
        <h1>FAQs Management</h1>

        <button @click="createFAQs" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> + Add New
        </button>
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
                    <td>{{ FAQ.intent_id }}</td>
                    <td>{{ FAQ.department_id }}</td>
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

export default {
    setup() {
        const FAQs = ref([]);
        const error = ref(null);
        const token = localStorage.getItem('sanctum_token');

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


        const createFAQs = async () => {
            const { value: formValues } = await Swal.fire({
                title: 'Create New Rack',
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
                                <label for="department">Department</label>
                                <input id="department" class="swal2-input" placeholder="Department">
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
            const { value: formValues } = await Swal.fire({
                title: 'Edit Rack',
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
                                <input id="department" class="swal2-input" placeholder="Department" value="${FAQs.department_id}">
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

        onMounted(() => {
            getFAQs();
        });

        return {
            FAQs,
            error,
            getFAQs,
            createFAQs,
            editFAQs,
            deleteFAQs,
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
