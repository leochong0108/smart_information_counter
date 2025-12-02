<template>
    <div class="container-fluid">
        <div class="row">
                <div class="col-12 d-flex align-items-center justify-content-between mb-2">
                    <h1>Departments Management</h1>
                    <div>
                        <button @click="exportDepartments" class="btn btn-success me-2">
                            <i class="fas fa-file-export"></i> Export Excel
                        </button>
                        <input type="file" ref="importFileRef" style="display:none" accept=".xlsx, .xls, .csv" @change="onFileChange" />
                        <button @click="triggerImport" class="btn btn-secondary me-2">
                            <i class="fas fa-file-import"></i> Import Excel
                        </button>
                        <button @click="createDepartments" class="btn btn-primary">
                            <i class="fas fa-plus-circle"></i> + Add New
                        </button>
                    </div>
                </div>
        </div>
        <div class="row mb-3">
                <div class="col-md-5">
                    <input
                        type="text"
                        v-model="searchTerm"
                        class="form-control"
                        placeholder="Search by department, description, location, contact info, or ID"
                    />
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
        <div class="row">
            <div class="col-md-12 shadow-lg rounded-lg">
                <div v-if="loading" class="text-center" style="padding: 13rem;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <div v-if="!filteredDepartments.length">
                    <p>No Data available. Please add some.</p>
                </div>

                <div v-if="filteredDepartments.length" >
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
                            <tr v-for="department in filteredDepartments" :key="department.id">
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
            </div>
        </div>


    </div>

</template>

<script>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { useExcelImport } from '../../services/useExcelImport';
import { useDataFetcher } from '../../services/dataFetcher';
import * as XLSX from 'xlsx';

export default {
    setup() {
        const error = ref(null);
        const token = localStorage.getItem('sanctum_token');

        const searchTerm = ref('');
        const selectedDepartmentId = ref(''); // Bound to the Department dropdown
        const { isLoading, importFileRef, triggerImport, handleFileUpload } = useExcelImport();
        const {getDepartments, departments} = useDataFetcher();

        const onFileChange = (event) => {
            // 调用通用上传函数，传入: 事件对象, 类型字符串, 成功后的回调
            handleFileUpload(event, 'department', getDepartments);
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

        const filteredDepartments = computed(() => {
            let data = departments.value;

            // A. Apply Text Search (Question, Answer, ID)
            if (searchTerm.value) {
                const searchLower = searchTerm.value.toLowerCase();
                data = data.filter(dpt =>
                    dpt.name?.toLowerCase().includes(searchLower) ||
                    dpt.description?.toLowerCase().includes(searchLower) ||
                    dpt.location?.toLowerCase().includes(searchLower) ||
                    dpt.contact_info?.toLowerCase().includes(searchLower) ||
                    dpt.id?.toString().includes(searchTerm.value)
                );
            }

            if (selectedDepartmentId.value !== '') {
                // Department IDs are usually numbers, unless 'null' is passed for unassigned
                const deptFilterValue = selectedDepartmentId.value === null ? null : parseInt(selectedDepartmentId.value);
                data = data.filter(dpt => dpt.id === deptFilterValue);
            }

            return data;
        });

        const exportDepartments = () => {
            if (!departments.value.length) {
                Swal.fire('No Data', 'There is no departments data to export.', 'info');
                return;
            }

            try {
                // Map data to include department name for clarity in export
                const exportData = departments.value.map(department => ({
                    ID: department.id,
                    Name: department.name,
                    Description: department.description,
                    Location: department.location,
                    contact_info: department.contact_info
                }));

                // Convert JSON to worksheet
                const worksheet = XLSX.utils.json_to_sheet(exportData);
                const workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, worksheet, 'Departments');

                // Generate and save file
                const excelBuffer = XLSX.write(workbook, { bookType: 'xlsx', type: 'array' });
                const blob = new Blob([excelBuffer], {
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                });
                saveAs(blob, 'Departments.xlsx');
                //Swal.fire('Exported!', 'Intent data successfully exported.', 'success');
            } catch (error) {
                console.error('Export Error:', error);
                Swal.fire('Error', 'Failed to export Departments', 'error');
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
            searchTerm,
            selectedDepartmentId,
            filteredDepartments,
            importFileRef,
            triggerImport,
            handleFileUpload,
            onFileChange,
            exportDepartments
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
