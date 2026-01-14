import { ref, computed } from 'vue';
import axios from 'axios';

export function useChatLogic() {
    const messages = ref([]);
    const FAQs = ref([]);
    const isLoading = ref(false);
    const token = localStorage.getItem('sanctum_token');
    let pollingInterval = null;

    const fetchTopFAQs = async () => {
        try {
            const res = await axios.get('/api/top10ForChat', {
                headers: { Authorization: `Bearer ${token}` }
            });
            FAQs.value = res.data;
        } catch (e) {
            console.error("Failed to fetch FAQs", e);
        }
    };

    const sendMessageToApi = async (text) => {
        if (!text.trim()) return;

        messages.value.push({ from: "user", text });
        isLoading.value = true;

        try {
            const res = await axios.post('/api/chat',
                { message: text },
                { headers: { Authorization: `Bearer ${token}` } }
            );

            messages.value.push({
                from: "ai",
                text: res.data.reply,
                isFailure: res.data.status === false,
                logId: res.data.log_id,
                waitingForHuman: false,
                replied: false
            });

        } catch (error) {
            messages.value.push({ from: "ai", text: "Sorry, network error occurred." });
        } finally {
            isLoading.value = false;
        }
    };

    const requestHumanHelp = async (index, logId) => {
        try {
            await axios.post('/api/request-help', { log_id: logId });

            if (messages.value[index]) {
                messages.value[index].waitingForHuman = true;
            }
            messages.value.push({ from: 'ai', text: '<i>Request sent! Please wait for staff...</i>' });

            startPolling(logId);
        } catch (e) {
            alert("Failed to request help.");
        }
    };

    const startPolling = (logId) => {
        if (pollingInterval) clearInterval(pollingInterval);

        pollingInterval = setInterval(async () => {
            try {
                const res = await axios.post('/api/check-reply', { log_id: logId });
                if (res.data.replied) {
                    clearInterval(pollingInterval);

                    const originalMsg = messages.value.find(m => m.logId === logId);
                    if (originalMsg) {
                        originalMsg.waitingForHuman = false;
                        originalMsg.replied = true;
                    }

                    messages.value.push({
                        from: 'ai',
                        text: `👨‍💼 <strong>Staff Reply:</strong> ${res.data.reply}`
                    });
                }
            } catch (e) { console.error("Polling error"); }
        }, 3000);
    };

    const stopPolling = () => {
        if (pollingInterval) clearInterval(pollingInterval);
    };

    const visibleFAQs = computed(() => FAQs.value.slice(0, 3));

    return {
        messages,
        FAQs,
        visibleFAQs,
        isLoading,
        fetchTopFAQs,
        sendMessageToApi,
        requestHumanHelp,
        stopPolling
    };
}
