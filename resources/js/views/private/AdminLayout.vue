<template>
    <nav class="navbar navbar-expand-lg bg-body-tertiary shadow-lg rounded-lg">
        <div class="container-fluid">
            <h1 class="navbar-brand" >
                <RouterLink to="/admin/" class="nav-link active" aria-current="page">
                    Admin Panel
                </RouterLink>
            </h1>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                <RouterLink to="/admin/departments" class="nav-link active" aria-current="page">
                    Departments
                </RouterLink>
                </li>


                <li class="nav-item">
                <RouterLink to="/admin/intents" class="nav-link active" aria-current="page">
                    Intents
                </RouterLink>
                </li>


                <li class="nav-item">
                <RouterLink to="/admin/faqs" class="nav-link active" aria-current="page">
                    FAQs
                </RouterLink>
                </li>

                <li class="nav-item">
                <RouterLink to="/admin/logs" class="nav-link active" aria-current="page">
                    Questions Log
                </RouterLink>
                </li>




            </ul>
            <form class="d-flex" >
                <button class="btn btn-outline-success me-2" type="button" @click="viewFailLog">
                    Failed Logs <span class="badge bg-danger">{{ failsCount }}</span>
                </button>
                 <button v-if="isLoggedIn" @click="handleLogout" class="btn btn-danger" style= "margin-right: 10px;">Logout</button>
                 <RouterLink v-else to="/login" class="hover:bg-gray-700 p-2 rounded">Login</RouterLink>
                 <RouterLink to="/" class=""><button  class='btn btn-primary'>To Chat</button></RouterLink>
            </form>
            </div>
        </div>
    </nav>

    <main class="container-fluid">
      <RouterView />
    </main>

</template>

<script>
import { ref , onMounted} from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useFailedLogStore } from '../../services/useFailsLog';

export default {

    setup() {
        const { failsCount, refreshFailedLogs } = useFailedLogStore();
        const router = useRouter();
        //const fails = ref([]);
        //const error = ref(null);
        const token = localStorage.getItem('sanctum_token');


/*         const getFail = async() => {
            try {
                const response = await axios.get('/api/selectFailedLogs', {
                    headers: { Authorization: `Bearer ${token}`  }
                });
                fails.value = response.data;
                console.log(fails.value);
            }
            catch (err) {
                error.value = err.response?.data?.message || 'Error fetching fails';
            }
        }; */

        const viewFailLog = async() => {

                router.push(`/admin/failLog/`);

        };


        const isLoggedIn = async() => {
            // Return a boolean based on whether the token exists
            return !!localStorage.getItem('sanctum_token');
        };


        const handleLogout = async() => {
            // 1. Remove the token from local storage
            localStorage.removeItem('sanctum_token');
            // 2. Redirect the user back to the login page
            router.push('/login');
        };

        onMounted(() => {
            refreshFailedLogs();
        });

        return {
            failsCount,
            refreshFailedLogs,
            viewFailLog,
            isLoggedIn,
            handleLogout
        };

    }
}
</script>
