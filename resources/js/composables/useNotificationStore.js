import { ref } from 'vue';
import axios from 'axios';

const failsCount = ref(0);
const liveRequests = ref([]);
let pollingTimer = null;

export function useNotificationStore() {
    const token = localStorage.getItem('sanctum_token');

    const fetchAllNotifications = async () => {
        if (!token) return;
        try {
            const [failsRes, liveRes] = await Promise.all([
                axios.get('/api/selectFailedLogs', { headers: { Authorization: `Bearer ${token}` } }),
                axios.get('/api/admin/support-requests', { headers: { Authorization: `Bearer ${token}` } })
            ]);

            failsCount.value = failsRes.data.length;

            const newData = liveRes.data;
            liveRequests.value = newData.map(newItem => {
                const oldItem = liveRequests.value.find(o => o.id === newItem.id);
                return {
                    ...newItem,
                    replyDraft: oldItem?.replyDraft || ''
                };
            });

        } catch (e) {
            console.error("Notification Poll Error", e);
        }
    };

    const sendReplyToUser = async (req) => {
        if (!req.replyDraft) return;
        try {
            await axios.post('/api/admin/reply-support', {
                log_id: req.id,
                reply: req.replyDraft
            }, { headers: { Authorization: `Bearer ${token}` } });

            liveRequests.value = liveRequests.value.filter(r => r.id !== req.id);
            return true;
        } catch (e) {
            alert("Failed to send reply");
            return false;
        }
    };


    const startPolling = () => {
        if (pollingTimer) return;
        fetchAllNotifications();
        pollingTimer = setInterval(fetchAllNotifications, 5000);
    };

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
