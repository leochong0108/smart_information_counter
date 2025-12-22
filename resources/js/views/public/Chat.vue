<template>
    <div class="chat-layout">

        <!-- 1. Header -->
        <header class="chat-header d-flex justify-content-between align-items-center p-3">
            <h5 class="m-0 fw-bold text-primary d-none d-md-block">Intelligent Campus Enquiry System</h5>
            <h5 class="m-0 fw-bold text-primary d-md-none">Campus Enquiry</h5>
            <button @click="confirmEndChat" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-box-arrow-right"></i> End Chat
            </button>
        </header>

        <!-- 2. Main Chat Area -->
        <main class="chat-main" ref="chatContainerRef">
            <div class="responsive-container h-100 d-flex flex-column">

                <!-- A. Welcome Screen -->
                <div v-if="messages.length === 0" class="welcome-view flex-grow-1 d-flex flex-column justify-content-center align-items-center text-center px-3">
                    <div class="mb-4">
                        <i class="bi bi-robot fs-1 text-primary bg-light p-3 rounded-circle shadow-sm"></i>
                    </div>
                    <h2 class="fw-bold mb-2">Hi, how can I help?</h2>
                    <p class="text-muted mb-4">Ask me anything about Southern University College</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <button v-for="top in FAQs" :key="top.id"
                                @click="handleSend(top.question)"
                                class="btn btn-light border rounded-pill px-3 py-2 text-start">
                            {{ top.question }}
                        </button>
                    </div>
                </div>

                <!-- B. Message List -->
                <div v-else class="messages-list py-3 px-3">
                    <div v-for="(m, idx) in messages" :key="idx" class="message-wrapper d-flex mb-3"
                         :class="m.from === 'user' ? 'justify-content-end' : 'justify-content-start'">

                        <!-- Avatar -->
                        <div v-if="m.from === 'ai'" class="avatar me-2 align-self-start">
                            <i class="bi bi-robot text-primary bg-light p-1 rounded-circle border"></i>
                        </div>

                        <!-- Bubble -->
                        <div class="bubble p-3 shadow-sm" :class="m.from === 'user' ? 'user-bubble' : 'ai-bubble'">
                            <div v-html="m.text"></div>

                            <!-- Request Help Button -->
                            <div v-if="m.from === 'ai' && m.isFailure && !m.waitingForHuman && !m.replied" class="mt-2 border-top pt-2">
                                <button @click="requestHumanHelp(idx, m.logId)" class="btn btn-warning btn-sm w-100 rounded-pill">
                                    <i class="bi bi-person-raised-hand"></i> Request Staff
                                </button>
                            </div>
                            <!-- Waiting Spinner -->
                            <div v-if="m.waitingForHuman" class="mt-2 text-warning small fst-italic">
                                <span class="spinner-grow spinner-grow-sm" role="status"></span> Waiting for staff...
                            </div>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div v-if="isLoading" class="message-wrapper d-flex mb-3 justify-content-start">
                        <div class="avatar me-2 align-self-start">
                            <i class="bi bi-robot text-primary bg-light p-1 rounded-circle border"></i>
                        </div>
                        <div class="bubble ai-bubble p-3 loading-indicator">
                            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                        </div>
                    </div>

                     <!-- Listening Indicator -->
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

        <!-- 3. Footer Input -->
        <footer class="chat-footer bg-white border-top pt-2 pb-3 px-3">
            <div class="responsive-container">
                <!-- Suggestion Chips -->
                <div v-if="messages.length > 0 && visibleFAQs.length > 0" class="suggestion-chips d-flex gap-2 overflow-auto mb-2 pb-1">
                    <button v-for="top in visibleFAQs" :key="top.id"
                            @click="handleSend(top.question)"
                            class="btn btn-sm btn-outline-secondary rounded-pill text-nowrap">
                        {{ top.question }}
                    </button>
                </div>

                <div class="input-group input-group-lg shadow-sm rounded-pill overflow-hidden border">
                    <button @click="toggleSpeech" class="btn btn-light border-end" :class="{'text-danger': isListening}" type="button">
                        <i class="bi" :class="isListening ? 'bi-mic-fill' : 'bi-mic'"></i>
                    </button>
                    <input
                        v-model="input"
                        @keyup.enter="handleSend(input)"
                        type="text"
                        class="form-control border-0 bg-light"
                        placeholder="Type a message..."
                        :disabled="isListening || isLoading"
                    >
                    <button @click="handleSend(input)" class="btn btn-primary px-4" :disabled="isLoading || !input.trim()">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
                <div class="text-center mt-2">
                    <small class="text-muted footer-note">Intelligent Campus Enquiry System.</small>
                </div>
            </div>
        </footer>

        <!-- 4. Timeout / End Chat Modal -->
        <div v-if="showTimeoutModal" class="modal-backdrop-custom d-flex justify-content-center align-items-center">
            <div class="modal-card bg-white p-4 rounded shadow-lg text-center mx-3 animate-fade-in">
                <div class="mb-3 text-warning">
                    <i class="bi bi-exclamation-circle fs-1"></i>
                </div>
                <h5 class="mb-3" v-if="isEndChatConfirmation">End this session?</h5>
                <h5 class="mb-3" v-else>Are you still there?</h5>

                <p class="text-muted" v-if="!isEndChatConfirmation">Redirecting in {{ countdown }} seconds.</p>

                <div class="d-flex justify-content-center gap-2 mt-4">
                    <button @click="continueSession" class="btn btn-outline-secondary px-4">Cancel</button>
                    <button @click="endSession" class="btn btn-danger px-4">End Chat</button>
                </div>
            </div>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from "vue";
import { useSpeech } from "../../composables/useSpeech";
import { useIdleTimer } from "../../composables/useIdleTimer";
import { useChatLogic } from "../../composables/useChatLogic";

// 1. 引入 Composables
const { isListening, transcript, toggleSpeech } = useSpeech();
const {
    showTimeoutModal, countdown, isEndChatConfirmation,
    resetTimer, endSession, confirmEndChat, continueSession
} = useIdleTimer(90, 10); // 90秒空闲，10秒倒计时

const {
    messages, FAQs, visibleFAQs, isLoading,
    fetchTopFAQs, sendMessageToApi, requestHumanHelp, stopPolling
} = useChatLogic();

// 2. 本地状态
const input = ref("");
const chatContainerRef = ref(null);

// 3. 逻辑绑定

// 滚动到底部
const scrollToBottom = async () => {
    await nextTick();
    if (chatContainerRef.value) {
        chatContainerRef.value.scrollTop = chatContainerRef.value.scrollHeight;
    }
};

// 发送消息处理
const handleSend = async (text) => {
    if (!text || !text.trim()) return;

    resetTimer(); // 重置空闲计时
    input.value = ""; // 清空输入框

    await sendMessageToApi(text);
    scrollToBottom();
};

// 监听语音输入，自动填充并发送(可选，或者只填充不发送)
watch(transcript, (newVal) => {
    if (newVal) {
        input.value = newVal;
        // 如果想语音说完自动发送，取消下面注释
        // handleSend(newVal);
    }
});

// 监听消息列表变化，自动滚动 (用于异步返回消息时)
watch(() => messages.value.length, () => {
    scrollToBottom();
});

// 4. 生命周期
onMounted(() => {
    fetchTopFAQs();
    // useIdleTimer 的 onMounted 已经自动启动了计时器
});

onUnmounted(() => {
    stopPolling(); // 确保离开页面停止轮询
});
</script>

<style scoped>
/* 保持原有样式，完全不用动，只需确保类名匹配 */
.chat-layout {
    height: 100dvh;
    display: flex;
    flex-direction: column;
    background-color: #f8f9fa;
    position: relative;
    overflow: hidden;
}

.responsive-container {
    width: 100%;
    max-width: 900px;
    margin: 0 auto;
    position: relative;
}

.chat-header {
    background: #fff;
    border-bottom: 1px solid #e9ecef;
    flex-shrink: 0;
    z-index: 10;
}

.chat-main {
    flex-grow: 1;
    overflow-y: auto;
    scroll-behavior: smooth;
    scrollbar-width: thin;
}
.chat-main::-webkit-scrollbar { width: 6px; }
.chat-main::-webkit-scrollbar-thumb { background-color: #ccc; border-radius: 3px; }

.chat-footer {
    flex-shrink: 0;
    z-index: 10;
}
.footer-note { font-size: 0.75rem; }

/* Bubbles */
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
.suggestion-chips::-webkit-scrollbar { display: none; }

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
.modal-card { width: 100%; max-width: 400px; }
.animate-fade-in { animation: fadeIn 0.3s ease-out; }
@keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

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
@keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }

.animate-pulse { animation: pulse 1.5s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }

@media (max-width: 576px) {
    .bubble { max-width: 92%; }
    .chat-header, .chat-footer { padding-left: 1rem !important; padding-right: 1rem !important; }
    .welcome-view h2 { font-size: 1.5rem; }
}
</style>
