<template>
    <div class="admin-layout">
        <!-- 1. 顶部导航栏 (Sticky Top) -->
        <!-- 添加 sticky-top 实现吸顶 -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
            <div class="container-fluid">
                <!-- Brand -->
                <router-link to="/admin" class="navbar-brand fw-bold text-primary d-flex align-items-center">
                    <i class="bi bi-robot me-2"></i>
                    <span>Admin Panel</span>
                </router-link>

                <!-- Mobile Toggler -->
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                    <span class="navbar-toggler-icon"></span>
                    <span v-if="liveRequests.length > 0" class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                </button>

                <!-- Navbar Content -->
                <div class="collapse navbar-collapse" id="adminNavbar" ref="navbarCollapse">
                    <!-- Left: Navigation Links -->
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <router-link to="/admin" class="nav-link" exact-active-class="active-link" @click="closeNavbar">
                                Dashboard
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/departments" class="nav-link" active-class="active-link" @click="closeNavbar">
                                Departments
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/intents" class="nav-link" active-class="active-link" @click="closeNavbar">
                                Intents
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/faqs" class="nav-link" active-class="active-link" @click="closeNavbar">
                                FAQs
                            </router-link>
                        </li>
                        <li class="nav-item">
                            <router-link to="/admin/logs" class="nav-link" active-class="active-link" @click="closeNavbar">
                                Logs
                            </router-link>
                        </li>
                    </ul>

                    <!-- Right: Action Buttons (修复按钮大小不一致问题) -->
                    <!-- 使用 align-items-center 确保垂直居中 -->
                    <!-- gap-2 控制间距 -->
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mt-3 mt-lg-0 action-buttons">

                        <!-- 1. Live Help -->
                        <button
                            type="button"
                            class="btn text-nowrap"
                            :class="liveRequests.length > 0 ? 'btn-danger animate-pulse' : 'btn-outline-secondary'"
                            @click="handleLiveHelpClick">
                            <i class="bi bi-headset me-1"></i> Live Help
                            <span v-if="liveRequests.length > 0" class="badge bg-white text-danger ms-1 rounded-pill">
                                {{ liveRequests.length }}
                            </span>
                        </button>

                        <!-- 2. Failed Logs -->
                        <button
                            class="btn btn-outline-warning text-dark text-nowrap"
                            @click="viewFailLog">
                            <i class="bi bi-exclamation-triangle me-1"></i> Failed Logs
                            <span v-if="failsCount > 0" class="badge bg-danger ms-1">{{ failsCount }}</span>
                        </button>

                        <!-- Mobile Divider -->
                        <hr class="d-lg-none my-1 w-100">

                        <!-- 3. To Chat -->
                        <router-link to="/" class="btn btn-outline-primary text-nowrap">
                            <i class="bi bi-chat-dots me-1"></i> To Chat
                        </router-link>

                        <!-- 4. Logout -->
                        <button v-if="isLoggedIn" @click="handleLogout" class="btn btn-dark text-nowrap">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- 2. 主内容区域 -->
        <main class="main-content bg-light">
            <!-- 修复白屏问题：移除了 <transition> 标签 -->
            <!-- 直接渲染组件，这是最稳定的做法 -->
            <router-view />
        </main>

        <!-- 3. Live Help Modal -->
        <div v-if="showHelpModal" class="modal-backdrop fade show"></div>
        <div v-if="showHelpModal" class="modal fade show d-block" tabindex="-1" @click.self="showHelpModal = false">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content shadow-lg border-0">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-headset-vr me-2"></i>Live Assistance
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="showHelpModal = false"></button>
                    </div>

                    <div class="modal-body bg-light">
                        <div v-if="liveRequests.length === 0" class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                            <p class="mt-2">All caught up! No pending requests.</p>
                        </div>

                        <div v-else>
                            <div v-for="req in liveRequests" :key="req.id" class="card mb-3 border-0 shadow-sm animate-slide-in">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <small class="text-muted">User Query:</small>
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    </div>
                                    <h6 class="card-text fw-bold mb-3">{{ req.question_text }}</h6>

                                    <div class="form-group mb-2">
                                        <input type="text" class="form-control" v-model="req.replyDraft" placeholder="Type your reply here..." @keyup.enter="sendReply(req)">
                                    </div>

                                    <div class="d-flex gap-2 flex-wrap mb-2">
                                        <button class="btn btn-sm btn-outline-secondary" @click="req.replyDraft = 'Please wait, I am coming to the kiosk now.'">
                                            Wait
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary" @click="req.replyDraft = 'Please proceed to AFO counter.'">
                                            AFO
                                        </button>
                                    </div>

                                    <button class="btn btn-success w-100" @click="sendReply(req)" :disabled="!req.replyDraft">
                                        <i class="bi bi-send-fill me-1"></i> Send Reply
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useFailedLogStore } from '../../services/useFailsLog';
import { Collapse } from 'bootstrap';

export default {
    setup() {
        const { failsCount, refreshFailedLogs } = useFailedLogStore();
        const router = useRouter();
        const token = localStorage.getItem('sanctum_token');
        const liveRequests = ref([]);
        const showHelpModal = ref(false);
        const navbarCollapse = ref(null);
        let adminPollTimer = null;

        const startAdminPolling = () => {
            if (!token) return;

            // 建议：先立即执行一次，不要等5秒
            refreshFailedLogs();

            adminPollTimer = setInterval(async () => {
                try {
                    // 1. 轮询 Live Help 请求 (保持原有逻辑)
                    const res = await axios.get('/api/admin/support-requests', {
                        headers: { Authorization: `Bearer ${token}` }
                    });
                    const newData = res.data;
                    const updatedList = newData.map(newItem => {
                        const existingItem = liveRequests.value.find(oldItem => oldItem.id === newItem.id);
                        return {
                            ...newItem,
                            replyDraft: existingItem ? existingItem.replyDraft : ''
                        };
                    });
                    liveRequests.value = updatedList;

                    // 🌟 2. 新增：顺便轮询 Failed Logs 数量
                    // 这样当 Chat 页面产生失败记录时，管理员这里的红色 badge 会自动更新
                    await refreshFailedLogs();

                } catch (e) {
                    console.error("Poll error"); // 静默失败，不打扰管理员
                }
            }, 5000); // 每 5 秒检查一次
        };

        const openHelpModal = () => showHelpModal.value = true;
        const handleLiveHelpClick = () => { closeNavbar(); openHelpModal(); };

        const sendReply = async (req) => {
            if (!req.replyDraft) return;
            try {
                await axios.post('/api/admin/reply-support', {
                    log_id: req.id, reply: req.replyDraft
                }, { headers: { Authorization: `Bearer ${token}` } });
                liveRequests.value = liveRequests.value.filter(r => r.id !== req.id);
            } catch (e) { alert("Failed to send"); }
        };

        const viewFailLog = () => { closeNavbar(); router.push(`/admin/failLog/`); };
        const handleLogout = () => { closeNavbar(); localStorage.removeItem('sanctum_token'); router.push('/login'); };

        const closeNavbar = () => {
            if (navbarCollapse.value && window.innerWidth < 992) {
                if (navbarCollapse.value.classList.contains('show')) {
                    const bsCollapse = Collapse.getInstance(navbarCollapse.value) || new Collapse(navbarCollapse.value);
                    bsCollapse.hide();
                }
            }
        };

        const isLoggedIn = () => !!localStorage.getItem('sanctum_token');

        onMounted(() => { refreshFailedLogs(); startAdminPolling(); });
        onUnmounted(() => { if (adminPollTimer) clearInterval(adminPollTimer); });

        return {
            failsCount, refreshFailedLogs, viewFailLog, isLoggedIn: isLoggedIn(), handleLogout,
            liveRequests, showHelpModal, openHelpModal, sendReply, navbarCollapse, closeNavbar, handleLiveHelpClick
        };
    }
}
</script>

<style scoped>
.admin-layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.main-content {
    flex: 1;
    padding-bottom: 2rem;
    min-height: calc(100vh - 56px);
}

.nav-link {
    font-weight: 500;
    color: #6c757d;
    transition: color 0.2s;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
}

.nav-link:hover {
    color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
}

.active-link {
    color: #0d6efd !important;
    background-color: rgba(13, 110, 253, 0.1);
    font-weight: 600;
}

/* 按钮样式修复 */
.action-buttons .btn {
    /* 强制所有按钮高度一致 */
    display: flex;
    align-items: center;
    justify-content: center;
    padding-top: 0.375rem;
    padding-bottom: 0.375rem;
}

/* 修复手机端按钮宽度 */
@media (max-width: 991px) {
    .action-buttons .btn {
        justify-content: flex-start; /* 手机上文字左对齐 */
        padding-left: 1rem;
    }
}

.animate-pulse {
    animation: pulse-red 2s infinite;
}
@keyframes pulse-red {
    0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}

.modal-backdrop {
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1040;
}
.modal {
    z-index: 1050;
}
.animate-slide-in {
    animation: slideIn 0.3s ease-out;
}
@keyframes slideIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
