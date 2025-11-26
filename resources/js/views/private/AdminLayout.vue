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
                <button class="btn me-2 position-relative"
                    :class="liveRequests.length > 0 ? 'btn-warning' : 'btn-outline-secondary'"
                    @click="openHelpModal">
                    <i class="bi bi-headset"></i> Live Help
                    <span v-if="liveRequests.length > 0" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ liveRequests.length }}
                    </span>
                </button>
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

    <div v-if="showHelpModal" class="modal fade show d-block" style="background: rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">Live Assistance Requests</h5>
                    <button type="button" class="btn-close" @click="showHelpModal = false"></button>
                </div>
                <div class="modal-body">
                    <div v-if="liveRequests.length === 0" class="text-center p-4">
                        <p class="text-muted">No pending requests at the moment.</p>
                    </div>
                    <div v-else>
                        <div v-for="req in liveRequests" :key="req.id" class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title text-primary">User Question:</h6>
                                <p class="card-text fs-5">{{ req.question_text }}</p>
                                <hr>
                                <div class="input-group">
                                    <input type="text" class="form-control" v-model="req.replyDraft" placeholder="Type reply (e.g., 'Please wait, I am coming out')">
                                    <button class="btn btn-success" @click="sendReply(req)">Send Reply</button>
                                </div>
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-secondary me-1" @click="req.replyDraft = 'Please wait, I am coming to the kiosk now.'">Quick: Wait</button>
                                    <button class="btn btn-sm btn-outline-secondary" @click="req.replyDraft = 'Please go to AFO for assistance.'">Quick: AFO</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <main class="container-fluid">
      <RouterView />
    </main>

</template>

<script>
import { ref , onMounted, onUnmounted} from 'vue';
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
        const liveRequests = ref([]);
        const showHelpModal = ref(false);
        let adminPollTimer = null;


    const startAdminPolling = () => {
            if (!token) return;

            adminPollTimer = setInterval(async () => {
                try {
                    const res = await axios.get('/api/admin/support-requests', {
                        headers: { Authorization: `Bearer ${token}` }
                    });

                    const newData = res.data;

                    // 🔔 如果有新请求，播放声音
                    if (newData.length > liveRequests.value.length) {
                         // playSound(); // 如果你启用了声音
                    }

                    // 🔥 智能合并逻辑：
                    // 我们不能直接覆盖 liveRequests，否则正在输入的 replyDraft 会丢失。
                    // 我们需要保留现有的 draft，只更新列表。

                    const updatedList = newData.map(newItem => {
                        // 检查旧列表中是否已经有这个 item
                        const existingItem = liveRequests.value.find(oldItem => oldItem.id === newItem.id);

                        // 如果有，保留旧的 replyDraft；如果没有，初始化为空
                        return {
                            ...newItem,
                            replyDraft: existingItem ? existingItem.replyDraft : ''
                        };
                    });

                    liveRequests.value = updatedList;

                } catch (e) {
                    console.error("Admin poll error");
                }
            }, 5000);
        };

/*         const playSound = () => {
            audio.play().catch(e => console.log("Audio play failed (interaction needed)", e));
        }; */

        const openHelpModal = () => {
            showHelpModal.value = true;
        };

        const sendReply = async (req) => {
            if (!req.replyDraft) return alert("Please type a reply");

            try {
                await axios.post('/api/admin/reply-support', {
                    log_id: req.id,
                    reply: req.replyDraft
                }, {
                     headers: { Authorization: `Bearer ${token}` }
                });

                // 发送成功后，从列表移除该请求
                liveRequests.value = liveRequests.value.filter(r => r.id !== req.id);
                alert("Reply sent!");
            } catch (e) {
                alert("Failed to send reply");
            }
        };

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
            startAdminPolling();
        });

        onUnmounted(() => {
            if (adminPollTimer) clearInterval(adminPollTimer);
        });

        return {
            failsCount,
            refreshFailedLogs,
            viewFailLog,
            isLoggedIn,
            handleLogout,
            liveRequests,
            showHelpModal,
            openHelpModal,
            sendReply,
        };

    }
}
</script>
