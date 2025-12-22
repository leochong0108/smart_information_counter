import { ref, computed } from 'vue';
import axios from 'axios';

export function useChatLogic() {
    const messages = ref([]);
    const FAQs = ref([]);
    const isLoading = ref(false);
    const token = localStorage.getItem('sanctum_token');
    let pollingInterval = null;

    // 获取 Top 10 FAQ
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

    // 发送消息
    const sendMessageToApi = async (text) => {
        if (!text.trim()) return;

        // 1. UI 立即显示用户消息
        messages.value.push({ from: "user", text });
        isLoading.value = true;

        try {
            // 2. API 请求
            const res = await axios.post('/api/chat',
                { message: text },
                { headers: { Authorization: `Bearer ${token}` } }
            );

            // 3. UI 显示 AI 回复
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

    // 请求人工协助
    const requestHumanHelp = async (index, logId) => {
        try {
            await axios.post('/api/request-help', { log_id: logId });

            // 更新 UI 状态
            if (messages.value[index]) {
                messages.value[index].waitingForHuman = true;
            }
            messages.value.push({ from: 'ai', text: '<i>Request sent! Please wait for staff...</i>' });

            // 开始轮询
            startPolling(logId);
        } catch (e) {
            alert("Failed to request help.");
        }
    };

    // 轮询管理员回复
    const startPolling = (logId) => {
        if (pollingInterval) clearInterval(pollingInterval);

        pollingInterval = setInterval(async () => {
            try {
                const res = await axios.post('/api/check-reply', { log_id: logId });
                if (res.data.replied) {
                    clearInterval(pollingInterval);

                    // 找到原始消息更新状态
                    const originalMsg = messages.value.find(m => m.logId === logId);
                    if (originalMsg) {
                        originalMsg.waitingForHuman = false;
                        originalMsg.replied = true;
                    }

                    // 添加回复消息
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

    // 计算属性：只显示前 3 个 FAQ
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
