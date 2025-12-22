import { ref } from 'vue';
import axios from 'axios';

// 全局状态 (Singleton Pattern)
const failsCount = ref(0);
const liveRequests = ref([]);
let pollingTimer = null;

export function useNotificationStore() {
    const token = localStorage.getItem('sanctum_token');

    // 核心：拉取所有通知数据
    const fetchAllNotifications = async () => {
        if (!token) return;
        try {
            // 并行请求：获取失败日志数 + 实时请求
            const [failsRes, liveRes] = await Promise.all([
                axios.get('/api/selectFailedLogs', { headers: { Authorization: `Bearer ${token}` } }),
                axios.get('/api/admin/support-requests', { headers: { Authorization: `Bearer ${token}` } })
            ]);

            // 更新失败数
            failsCount.value = failsRes.data.length;

            // 更新实时请求 (保留正在输入的回复草稿)
            // 逻辑：新数据来了，如果旧数据里有用户正在打字的 replyDraft，把它复制过来
            const newData = liveRes.data;
            liveRequests.value = newData.map(newItem => {
                const oldItem = liveRequests.value.find(o => o.id === newItem.id);
                return {
                    ...newItem,
                    replyDraft: oldItem?.replyDraft || '' // 保留草稿
                };
            });

        } catch (e) {
            // 静默失败，不打扰 UI
            console.error("Notification Poll Error", e);
        }
    };

    // 发送回复
    const sendReplyToUser = async (req) => {
        if (!req.replyDraft) return;
        try {
            await axios.post('/api/admin/reply-support', {
                log_id: req.id,
                reply: req.replyDraft
            }, { headers: { Authorization: `Bearer ${token}` } });

            // 成功的乐观更新：直接从列表中移除该请求，不用等下一次轮询
            liveRequests.value = liveRequests.value.filter(r => r.id !== req.id);
            return true;
        } catch (e) {
            alert("Failed to send reply");
            return false;
        }
    };

    // 启动轮询
    const startPolling = () => {
        if (pollingTimer) return; // 防止重复启动
        fetchAllNotifications(); // 立即执行一次
        pollingTimer = setInterval(fetchAllNotifications, 5000); // 每5秒一次
    };

    // 停止轮询
    const stopPolling = () => {
        if (pollingTimer) clearInterval(pollingTimer);
        pollingTimer = null;
    };

    return {
        failsCount,
        liveRequests,
        startPolling,
        stopPolling,
        refresh: fetchAllNotifications,
        sendReplyToUser
    };
}
