<template>
    <div class="d-flex align-items-center justify-content-between mb-5">
        <h1>Departments Management</h1>

        <button @click="createDepartments" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> + Add New
        </button>
    </div>

    <div v-if="loading" class="text-center" style="padding: 13rem;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <div v-if="!departments.length">
        <p>No Data available. Please add some.</p>
    </div>

    <div v-if="departments.length" >
        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Name</th>
                    <th scope="col">Description</th>
                    <th scope="col">Location</th>
                    <th scope="col">Contact Info</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="department in departments" :key="department.id">
                    <td>{{ department.id }}</td>
                    <td>{{ department.name }}</td>
                    <td>{{ department.description }}</td>
                    <td>{{ department.location }}</td>
                    <td>{{ department.contact_info }}</td>
                    <td>
                        <div class="d-flex">
                        <button class="btn btn-success" @click="editDepartments(department)"><i class="fas fa-edit"></i>&nbsp;Edit</button>
                        <button class="btn btn-danger ms-2" @click="deleteDepartments(department.id)"><i class="fas fa-trash-alt"></i>&nbsp;Delete</button>
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
        const departments = ref([]);
        const error = ref(null);
        const token = localStorage.getItem('sanctum_token');

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


        const createDepartments = async () => {
            const { value: formValues } = await Swal.fire({
                title: 'Create',
                html:
                            `<div class="swal2-input-group">
                                <label for="name">Name</label>
                                <input id="name" class="swal2-input" placeholder="Name">
                            </div>
                            <div class="swal2-input-group">
                                <label for="description">Description</label>
                                <input id="description" class="swal2-input" placeholder="Description">
                            </div>
                            <div class="swal2-input-group">
                                <label for="location">Location</label>
                                <input id="location" class="swal2-input" placeholder="Location">
                            </div>
                            <div class="swal2-input-group">
                                <label for="contact_info">Contact Info</label>
                                <input id="contact_info" class="swal2-input" placeholder="Contact Info">
                            </div>`,
                focusConfirm: false,
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                customClass: { container: 'swal2-custom-wide'},

                preConfirm: () => {
                    return {
                        name: document.getElementById('name').value,
                        description: document.getElementById('description').value,
                        location: document.getElementById('location').value,
                        contact_info: document.getElementById('contact_info').value,
                    };
                }
            });

            if (formValues) {
                if(token){
                    try {
                        await axios.post('/api/createDepartments', formValues, {
                            headers: { Authorization: `Bearer ${token}` }
                        });
                        await getDepartments();
                        Swal.fire('Created!', 'New department has been created.', 'success');
                    } catch (err) {
                        Swal.fire('Error', err.response?.data?.message || 'Could not create department', 'error');
                    }
                }
            }
        };

        const editDepartments = async (departments) => {
            const { value: formValues } = await Swal.fire({
                title: 'Edit',
                html:
                            `<div class="swal2-input-group">
                                <label for="name">Name</label>
                                <input id="name" class="swal2-input" placeholder="Question" value="${departments.name}">
                            </div>
                            <div class="swal2-input-group">
                                <label for="description">Description</label>
                                <input id="description" class="swal2-input" placeholder="Description" value="${departments.description}">
                            </div>
                            <div class="swal2-input-group">
                                <label for="location">Location</label>
                                <input id="location" class="swal2-input" placeholder="Location" value="${departments.location}">
                            </div>
                            <div class="swal2-input-group">
                                <label for="contact_info">Contact Info</label>
                                <input id="contact_info" class="swal2-input" placeholder="Contact Info" value="${departments.contact_info}">
                            </div>`,
                focusConfirm: false,
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                customClass: { container: 'swal2-custom-wide'},


                preConfirm: () => {
                    return {
                        name: document.getElementById('name').value,
                        description: document.getElementById('description').value,
                        location: document.getElementById('location').value,
                        contact_info: document.getElementById('contact_info').value,
                    };
                }
            });

            if (formValues) {
                if(token){
                    try {
                        await axios.put(`/api/updateDepartments/${departments.id}`, formValues, {
                            headers: { Authorization: `Bearer ${token}` }
                        });
                        await getDepartments();
                        Swal.fire('Updated!', 'department details have been updated.', 'success');
                    } catch (err) {
                        Swal.fire('Error', err.response?.data?.message || 'Could not update', 'error');
                    }
                }
            }
        };

        const deleteDepartments = async (id) => {
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
                        await axios.delete(`/api/deleteDepartments/${id}`, {
                            headers: { Authorization: `Bearer ${token}` }
                        });
                        await getFAQs();
                        Swal.fire('Deleted!', 'Department has been deleted.', 'success');
                    } catch (err) {
                        Swal.fire('Error', err.response?.data?.message || 'Could not delete', 'error');
                    }
                }
            }
        };

        onMounted(() => {
            getDepartments();
        });

        return {
            departments,
            error,
            getDepartments,
            createDepartments,
            editDepartments,
            deleteDepartments,
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
