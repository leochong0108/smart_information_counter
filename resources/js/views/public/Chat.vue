<template>
    <!-- 全局布局容器 -->
    <div class="chat-layout">

        <!-- 1. 顶部固定区域 (Header) -->
        <header class="chat-header d-flex justify-content-between align-items-center p-3">
            <h5 class="m-0 fw-bold text-primary d-none d-md-block">Intelligent Campus Enquiry System</h5>
            <h5 class="m-0 fw-bold text-primary d-md-none">Campus Enquiry</h5>
            <button @click="endChat" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-box-arrow-right"></i> End Chat
            </button>
        </header>

        <!-- 2. 中间滚动区域 (Main) -->
        <main class="chat-main" ref="chatContainerRef">
            <div class="responsive-container h-100 d-flex flex-column">

                <!-- A. 初始欢迎界面 -->
                <div v-if="messages.length === 0" class="welcome-view flex-grow-1 d-flex flex-column justify-content-center align-items-center text-center px-3">
                    <div class="mb-4">
                        <i class="bi bi-robot fs-1 text-primary bg-light p-3 rounded-circle shadow-sm"></i>
                    </div>
                    <h2 class="fw-bold mb-2">Hi, how can I help?</h2>
                    <p class="text-muted mb-4">Ask me anything about Southern University College</p>

                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <button v-for="top in FAQs" :key="top.id"
                                @click="setInputAndSend(top.question)"
                                class="btn btn-light border rounded-pill px-3 py-2 text-start">
                            {{ top.question }}
                        </button>
                    </div>
                </div>

                <!-- B. 聊天消息列表 -->
                <div v-else class="messages-list py-3 px-3">
                    <div v-for="(m, idx) in messages" :key="idx" class="message-wrapper d-flex mb-3"
                         :class="m.from === 'user' ? 'justify-content-end' : 'justify-content-start'">

                        <div v-if="m.from === 'ai'" class="avatar me-2 align-self-start">
                            <i class="bi bi-robot text-primary bg-light p-1 rounded-circle border"></i>
                        </div>

                        <div class="bubble p-3 shadow-sm"
                             :class="m.from === 'user' ? 'user-bubble' : 'ai-bubble'">
                            <div v-html="m.text"></div>

                            <div v-if="m.from === 'ai' && m.isFailure && !m.waitingForHuman && !m.replied" class="mt-2 border-top pt-2">
                                <button @click="requestHelp(idx, m.logId)" class="btn btn-warning btn-sm w-100 rounded-pill">
                                    <i class="bi bi-person-raised-hand"></i> Request Staff
                                </button>
                            </div>
                            <div v-if="m.waitingForHuman" class="mt-2 text-warning small fst-italic">
                                <span class="spinner-grow spinner-grow-sm" role="status"></span> Waiting for staff...
                            </div>
                        </div>
                    </div>

                    <div v-if="isLoading" class="message-wrapper d-flex mb-3 justify-content-start">
                        <div class="avatar me-2 align-self-start">
                            <i class="bi bi-robot text-primary bg-light p-1 rounded-circle border"></i>
                        </div>
                        <div class="bubble ai-bubble p-3 loading-indicator">
                            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                        </div>
                    </div>

                     <!-- 语音监听指示器 -->
                    <div v-if="isListening" class="message-wrapper d-flex mb-3 justify-content-start">
                         <div class="avatar me-2 align-self-start">
                            <i class="bi bi-robot text-primary bg-light p-1 rounded-circle border"></i>
                        </div>
                        <div class="bubble ai-bubble p-3">
                            <i class="bi bi-mic-fill text-danger animate-pulse"></i> Listening...
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <!-- 3. 底部固定输入区域 (Footer) -->
        <footer class="chat-footer bg-white border-top pt-2 pb-3 px-3">
            <div class="responsive-container">

                <div v-if="messages.length > 0 && visibleFAQs.length > 0" class="suggestion-chips d-flex gap-2 overflow-auto mb-2 pb-1">
                    <button v-for="top in visibleFAQs" :key="top.id"
                            @click="setInputAndSend(top.question)"
                            class="btn btn-sm btn-outline-secondary rounded-pill text-nowrap">
                        {{ top.question }}
                    </button>
                </div>

                <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden border">
                    <button @click="startVoiceInput" class="btn btn-light border-end" :class="{'text-danger': isListening}" type="button">
                        <i class="bi" :class="isListening ? 'bi-mic-fill' : 'bi-mic'"></i>
                    </button>
                    <input
                        v-model="input"
                        @keyup.enter="sendMessage"
                        type="text"
                        class="form-control border-0 bg-light"
                        placeholder="Type a message..."
                        :disabled="isListening || isLoading"
                    >
                    <button @click="sendMessage" class="btn btn-primary px-4" :disabled="isLoading || !input.trim()">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
                <div class="text-center mt-2">
                    <small class="text-muted footer-note">Intelligent Campus Enquiry System.</small>
                </div>
            </div>
        </footer>

        <!-- Modal -->
        <div v-if="showModal" class="modal-backdrop-custom d-flex justify-content-center align-items-center">
            <div class="modal-card bg-white p-4 rounded shadow-lg text-center mx-3">
                <div class="mb-3 text-warning">
                    <i class="bi bi-exclamation-circle fs-1"></i>
                </div>
                <h5 class="mb-3" v-if="isEndChatConfirmation">End this session?</h5>
                <h5 class="mb-3" v-else>Are you still there?</h5>

                <p class="text-muted" v-if="!isEndChatConfirmation">Redirecting in {{ countdown }} seconds.</p>

                <div class="d-flex justify-content-center gap-2 mt-4">
                    <button @click="continueChat" class="btn btn-outline-secondary px-4">Cancel</button>
                    <button @click="endChatImmediate" class="btn btn-danger px-4">End Chat</button>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick } from "vue";
import { useRouter } from 'vue-router';
import axios from 'axios';

// --- State ---
const messages = ref([]);
const input = ref("");
const isLoading = ref(false);
const showModal = ref(false);
const countdown = ref(10);
const router = useRouter();
const FAQs = ref([]);
const token = localStorage.getItem('sanctum_token');
const pollingInterval = ref(null);
const isListening = ref(false);
let recognition = null;
const isEndChatConfirmation = ref(false);
let idleTimer;
let modalCountdownTimer;
const chatContainerRef = ref(null);

// --- Computed ---
const visibleFAQs = computed(() => FAQs.value.slice(0, 3));

// --- API & Logic ---
const getTop10FAQs = async () => {
        try {
            const response = await axios.get('/api/top10ForChat', {
                headers: { Authorization: `Bearer ${token}` }
            });
            FAQs.value = response.data;
        } catch (err) {
            console.error('Error fetching FAQs', err);
    }
};

const scrollToBottom = async () => {
    await nextTick();
    if (chatContainerRef.value) {
        chatContainerRef.value.scrollTop = chatContainerRef.value.scrollHeight;
    }
};

const sendMessage = async () => {
    resetIdleTimer();
    if (!input.value.trim()) return;

    messages.value.push({ from: "user", text: input.value });
    const userMessage = input.value;
    input.value = "";
    isLoading.value = true;
    scrollToBottom();

    try {
        const response = await axios.post('/api/chat',
            { message: userMessage },
            { headers: { Authorization: `Bearer ${token}` }}
        );

        const data = response.data;

        messages.value.push({
            from: "ai",
            text: data.reply,
            isFailure: data.status === false,
            logId: data.log_id,
            waitingForHuman: false,
            replied: false
        });
    } catch (error) {
        messages.value.push({ from: "ai", text: "Sorry, network error occurred." });
    } finally {
        isLoading.value = false;
        scrollToBottom();
    }
};

const setInputAndSend = (question) => {
    input.value = question;
    sendMessage();
};

const requestHelp = async (index, logId) => {
    try {
        await axios.post('/api/request-help', { log_id: logId });
        messages.value[index].waitingForHuman = true;
        messages.value.push({ from: 'ai', text: '<i>Request sent! Please wait...</i>' });
        scrollToBottom();
        startPolling(logId);
    } catch (e) {
        alert("Failed to request help.");
    }
};

const startPolling = (logId) => {
    if (pollingInterval.value) clearInterval(pollingInterval.value);
    pollingInterval.value = setInterval(async () => {
        try {
            const res = await axios.post('/api/check-reply', { log_id: logId });
            if (res.data.replied) {
                clearInterval(pollingInterval.value);
                const originalMessage = messages.value.find(m => m.logId === logId);
                if (originalMessage) {
                    originalMessage.waitingForHuman = false;
                    originalMessage.replied = true;
                }
                messages.value.push({ from: 'ai', text: `👨‍💼 <strong>Staff Reply:</strong> ${res.data.reply}` });
                scrollToBottom();
            }
        } catch (e) { console.error("Polling error"); }
    }, 3000);
};

// --- Idle & End Logic ---
const resetIdleTimer = () => {
    if (isEndChatConfirmation.value) return;
    clearTimeout(idleTimer);
    clearTimeout(modalCountdownTimer);
    showModal.value = false;
    idleTimer = setTimeout(() => {
        showModal.value = true;
        countdown.value = 10;
        modalCountdownTimer = setInterval(() => {
            countdown.value--;
            if (countdown.value <= 0) endChatImmediate();
        }, 1000);
    }, 90000);
};

const endChat = () => {
    isEndChatConfirmation.value = true;
    showModal.value = true;
    clearTimeout(idleTimer);
};

const continueChat = () => {
    showModal.value = false;
    clearInterval(modalCountdownTimer);
    isEndChatConfirmation.value = false;
    resetIdleTimer();
};

const endChatImmediate = () => {
    if (pollingInterval.value) clearInterval(pollingInterval.value);
    router.push('/');
};

// --- Voice Input with Error Handling ---
const setupSpeechRecognition = () => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        console.warn("Speech Recognition API not supported in this browser.");
        return;
    }

    recognition = new SpeechRecognition();
    recognition.lang = 'en-US';
    recognition.continuous = false; // 通常设为 false 体验更好，说完一句话自动停止
    recognition.interimResults = false;

    recognition.onstart = () => {
        isListening.value = true;
    };

    recognition.onend = () => {
        isListening.value = false;
        // 如果有内容，自动发送（可选）
        // if (input.value) sendMessage();
    };

    recognition.onresult = (event) => {
        const transcript = event.results[0][0].transcript;
        input.value = transcript;
    };

    // 🔥 新增：错误处理，告诉你是不是 HTTPS 问题
    recognition.onerror = (event) => {
        isListening.value = false;
        console.error("Speech Recognition Error:", event.error);
        if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
            alert("Microphone access blocked! \n\nReason: You are likely using HTTP. \nPlease use HTTPS or enable 'Insecure origins treated as secure' in browser flags.");
        }
    };
};

const startVoiceInput = () => {
    if (!recognition) {
        alert("Your browser does not support voice input.");
        return;
    }
    if (isListening.value) recognition.stop();
    else {
        input.value = ""; // 清空输入框准备听写
        recognition.start();
    }
};

onMounted(() => {
    getTop10FAQs();
    resetIdleTimer();
    setupSpeechRecognition();
});

onUnmounted(() => {
    clearTimeout(idleTimer);
    clearInterval(modalCountdownTimer);
    if (pollingInterval.value) clearInterval(pollingInterval.value);
    if (recognition) recognition.abort();
});
</script>

<style scoped>
/* 1. 全局布局 */
.chat-layout {
    height: 100dvh;
    display: flex;
    flex-direction: column;
    background-color: #f8f9fa;
    position: relative;
    overflow: hidden;
}

/* 2. 响应式容器 */
.responsive-container {
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
    position: relative;
}

/* 3. Header */
.chat-header {
    background: #fff;
    border-bottom: 1px solid #e9ecef;
    flex-shrink: 0;
    z-index: 10;
}

/* 4. Main Scroll Area */
.chat-main {
    flex-grow: 1;
    overflow-y: auto;
    scroll-behavior: smooth;
    scrollbar-width: thin;
}
.chat-main::-webkit-scrollbar {
    width: 6px;
}
.chat-main::-webkit-scrollbar-thumb {
    background-color: #ccc;
    border-radius: 3px;
}

/* 5. Footer */
.chat-footer {
    flex-shrink: 0;
    z-index: 10;
}
.footer-note {
    font-size: 0.75rem;
}

/* 6. Bubbles */
.bubble {
    max-width: 85%;
    border-radius: 1.2rem;
    font-size: 1rem;
    line-height: 1.5;
    word-wrap: break-word;
}

.user-bubble {
    background-color: #e3f2fd;
    color: #0d47a1;
    border-bottom-right-radius: 0.2rem;
}

.ai-bubble {
    background-color: #ffffff;
    color: #212529;
    border: 1px solid #e9ecef;
    border-bottom-left-radius: 0.2rem;
}

.suggestion-chips {
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.suggestion-chips::-webkit-scrollbar {
    display: none;
}

/* Modal */
.modal-backdrop-custom {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 2000;
}
.modal-card {
    width: 100%;
    max-width: 400px;
}

/* Loading */
.loading-indicator .dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #888;
    margin: 0 2px;
    animation: bounce 1.4s infinite ease-in-out both;
}
.loading-indicator .dot:nth-child(1) { animation-delay: -0.32s; }
.loading-indicator .dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes bounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

.animate-pulse {
    animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .5; }
}

@media (max-width: 576px) {
    .bubble {
        max-width: 92%;
    }
    .chat-header, .chat-footer {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
    .welcome-view h2 {
        font-size: 1.5rem;
    }
}
</style>
