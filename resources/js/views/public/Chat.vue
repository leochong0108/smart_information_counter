<template>
    <div class="container-fluid d-flex flex-column justify-content-center align-items-center vh-100 position-relative">

        <button @click="endChat" class="btn btn-secondary end-chat-btn position-absolute top-0 end-0 m-3">End Chat</button>

        <div v-if="messages.length === 0" class="initial-view text-center">
            <h1 class="text-muted">Hi Welcome, Ask something you want to know about the Southern University College</h1>
            <div class="container mt-5 faqs-container">
                <div class="row g-2 justify-content-center">
                    <div class="col-auto" v-for="top in FAQs" :key="top.id">
                        <button @click="setInputAndSend(top.question)" class="btn btn-predefined">{{top.question}}</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="chat-container">
            <div v-for="(m, idx) in messages" :key="idx" class="message d-flex" :class="m.from === 'user' ? 'justify-content-end' : 'justify-content-start'">
                <div class="bubble p-2 m-1" :class="m.from === 'user' ? 'user-bubble' : 'ai-bubble'" v-html="m.text"></div>
            </div>
            <div v-if="isLoading" class="d-flex justify-content-start message">
                <div class="bubble p-2 m-1 ai-bubble loading-indicator">
                    <span class="dot">.</span><span class="dot">.</span><span class="dot">.</span>
                </div>
            </div>
            <div v-if="isListening" class="d-flex justify-content-start message">
                <div class="bubble p-2 m-1 ai-bubble listening-indicator">Listening...</div>
            </div>
        </div>

        <div v-if="messages.length > 0" class="container mt-3 mb-3 faqs-container chat-faqs">
            <div class="row g-2 justify-content-center">
                <div class="col-auto" v-for="top in visibleFAQs" :key="top.id">
                    <button @click="setInputAndSend(top.question)" class="btn btn-predefined btn-sm">{{top.question}}</button>
                </div>
            </div>
        </div>

        <div class="input-box d-flex">
            <button
                @click="startVoiceInput"
                class="btn me-2 voice-btn"
                :class="isListening ? 'btn-danger' : 'btn-predefined'"
                :disabled="isLoading"
            >
                <i class="bi bi-mic"></i>
                <span class="d-none d-md-inline">{{ isListening ? 'Stop Listening' : 'Voice Input' }}</span>
                <span class="d-inline d-md-none">{{ isListening ? 'Stop' : 'Voice' }}</span>
            </button>
            <input v-model="input" @keyup.enter="sendMessage" class="form-control me-2" placeholder="Ask here" :disabled="isListening || isLoading" />
            <button @click="sendMessage" class="btn btn-primary" :disabled="isLoading || isListening">Send</button>
        </div>

        <div v-if="showModal" class="modal-overlay">
            <div class="custom-modal">
                <div class="modal-content">
                    <p v-if="isEndChatConfirmation">Are you sure you want to end this chat session?</p>
                    <p v-else>Are you still there? You will be redirected to the home page in {{ countdown }} seconds.</p>
                    <div class="d-flex justify-content-center">
                        <button @click="continueChat" class="btn me-2" :class="isEndChatConfirmation ? 'btn-primary' : 'btn-primary'">
                            {{ isEndChatConfirmation ? 'No, stay in chat' : 'Yes, continue' }}
                        </button>
                        <button @click="endChatImmediate" class="btn btn-secondary">
                            {{ isEndChatConfirmation ? 'Yes, end chat' : 'No, end chat' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";
import { sendMessageToAI } from "../../services/api";
import { useRouter } from 'vue-router';
import axios from 'axios';
// import Faqs from "../private/faqs.vue"; // Removed as it seems unused

const messages = ref([]);
const input = ref("");
const isLoading = ref(false);
const showModal = ref(false);
const countdown = ref(10);
const router = useRouter();
const FAQs = ref([]);
const token = localStorage.getItem('sanctum_token');
const error = ref(null);


// --- Voice Input State and Variables ---
const isListening = ref(false);
let recognition = null;
// ---------------------------------------

// --- New Confirmation State ---
const isEndChatConfirmation = ref(false);
// ------------------------------

let idleTimer;
let modalCountdownTimer;

// Computed property to display only the top 3 FAQs after chat starts
const visibleFAQs = computed(() => {
    // Return the first 3 FAQs from the full list
    return FAQs.value.slice(0, 3);
});


const getTop10FAQs = async () => {
    if (token) {
        try {
            // Updated API endpoint is assumed to be correct
            const response = await axios.get('/api/top10ForChat', {
                headers: { Authorization: `Bearer ${token}` }
            });
            FAQs.value = response.data;
            console.log(FAQs.value);
        } catch (err) {
            error.value = err.response?.data?.message || 'Error fetching top 10 FAQs';
        }
    }
};

const resetIdleTimer = () => {
    // Only reset if we are not currently confirming the end chat action
    if (isEndChatConfirmation.value) return;

    clearTimeout(idleTimer);
    clearTimeout(modalCountdownTimer);
    showModal.value = false;
    idleTimer = setTimeout(showModalAndCountdown, 90000); // set idle time here (90 seconds)
};

const showModalAndCountdown = () => {
    // Ensure we aren't showing the confirmation modal first
    if (isEndChatConfirmation.value) return;

    showModal.value = true;
    countdown.value = 10;
    modalCountdownTimer = setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            clearInterval(modalCountdownTimer);
            endChatImmediate(); // Use immediate end for consistency
        }
    }, 1000);
};

const continueChat = () => {
    showModal.value = false;
    clearInterval(modalCountdownTimer);
    isEndChatConfirmation.value = false; // Reset flag
    resetIdleTimer();
};

const endChatImmediate = () => {
    showModal.value = false;
    clearInterval(modalCountdownTimer);
    isEndChatConfirmation.value = false; // Reset flag
    messages.value = [];
    router.push('/');
};

const sendMessage = async () => {
    resetIdleTimer();
    if (!input.value.trim()) return;

    messages.value.push({ from: "user", text: input.value });
    const userMessage = input.value;
    input.value = "";
    isLoading.value = true;

    try {
        // Assuming sendMessageToAI is a function that calls your Laravel/Gemini endpoint
        const reply = await sendMessageToAI(userMessage);
        messages.value.push({ from: "ai", text: reply });
    } catch (error) {
        console.error("Failed to get AI reply:", error);
        messages.value.push({ from: "ai", text: "Sorry, I am unable to respond at this time." });
    } finally {
        isLoading.value = false;
        // Scroll to the bottom of the chat after new message
        setTimeout(() => {
            const chatContainer = document.querySelector('.chat-container');
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        }, 100);
    }
};

const setInputAndSend = (question) => {
    resetIdleTimer();
    input.value = question;
    // Call sendMessage() immediately after setting the input
    sendMessage();
};

const endChat = () => {
    // Show confirmation modal instead of immediate end
    isEndChatConfirmation.value = true;
    showModal.value = true;
    // Stop any running idle countdown immediately
    clearInterval(modalCountdownTimer);
    clearTimeout(idleTimer);
};

// --- Voice Input Implementation (kept as-is for functionality) ---
const setupSpeechRecognition = () => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (!SpeechRecognition) {
        console.warn("Speech Recognition not supported in this browser.");
        return;
    }

    recognition = new SpeechRecognition();
    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = 'en-US';

    recognition.onstart = () => {
        isListening.value = true;
        console.log('Voice recognition started.');
    };

    recognition.onresult = (event) => {
        let finalTranscript = '';
        let interimTranscript = '';

        for (let i = event.resultIndex; i < event.results.length; i++) {
            const transcript = event.results[i][0].transcript;
            if (event.results[i].isFinal) {
                finalTranscript += transcript;
            } else {
                interimTranscript += transcript;
            }
        }

        input.value = finalTranscript + interimTranscript;

        if (finalTranscript) {
            recognition.stop();
        }
    };

    recognition.onerror = (event) => {
        console.error('Speech recognition error:', event.error);
        isListening.value = false;
    };

    recognition.onend = () => {
        isListening.value = false;
        console.log('Voice recognition ended.');
    };
};

const startVoiceInput = () => {
    if (!recognition) return;

    if (isListening.value) {
        recognition.stop();
    } else {
        input.value = "";
        recognition.start();
    }
};
// ------------------------------------

onMounted(() => {
    setupSpeechRecognition();
    resetIdleTimer();
    getTop10FAQs();
});

onUnmounted(() => {
    clearTimeout(idleTimer);
    clearInterval(modalCountdownTimer);
    if (recognition && isListening.value) {
        recognition.stop();
    }
});
</script>

<style scoped>
/* Existing styles */
.initial-view {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    min-height: 50vh;
}
.chat-container {
    width: 70%; /* Desktop width */
    max-width: 800px;
    min-height: 50vh; /* Increased min-height for better flow */
    max-height: 70vh; /* Max height to allow scrolling */
    padding: 10px; /* Add padding */
    margin-bottom: 10px;
    overflow-y: auto;
    /* Ensure chat container scrolls to the bottom for new messages */
    display: flex;
    flex-direction: column;
}
.message {
    /* To ensure bubbles are pushed to start/end correctly */
    width: 100%;
}
.bubble {
    max-width: 85%; /* Limit bubble width */
    word-wrap: break-word; /* Ensure long words wrap */
    /* Add smooth transition for better visual feedback */
    transition: all 0.2s ease-in-out;
}
.user-bubble {
    background-color: #5d5d81;
    color: white;
    border-radius: 1.5rem 1.5rem 0.5rem 1.5rem;
}
.ai-bubble {
    background-color: #eaeaf2;
    color: black;
    border-radius: 1.5rem 1.5rem 1.5rem 0.5rem;
}
.btn-predefined {
    background-color: #eaeaf2;
    color: black;
    border-radius: 2rem;
    padding: 0.5rem 1rem;
    border: none;
    white-space: nowrap; /* Prevent buttons from breaking across lines too early */
}
.btn-predefined:hover {
    background-color: #dcdce6;
}
.input-box {
    margin-top: 1rem;
    width: 50%; /* Desktop width */
    max-width: 800px;
}
.end-chat-btn {
    z-index: 1000;
}

/* New style for the 3 FAQ row above the input */
.chat-faqs {
    width: 70%;
    max-width: 800px;
    padding: 0 10px;
}
.chat-faqs .btn-sm {
    padding: 0.3rem 0.6rem;
    font-size: 0.85rem;
}

/* Modal styles (kept as-is) */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 2000;
}

.custom-modal {
    background: white;
    padding: 2rem;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.modal-content p {
    font-size: 1.1rem;
    margin-bottom: 1.5rem;
}

/* Voice input styles */
.listening-indicator {
    font-style: italic;
    opacity: 0.7;
}

/* Loading dots animation */
.loading-indicator .dot {
    opacity: 0;
    animation: loading-dot-flash 1.4s infinite alternate;
}
.loading-indicator .dot:nth-child(2) {
    animation-delay: 0.2s;
}
.loading-indicator .dot:nth-child(3) {
    animation-delay: 0.4s;
}
@keyframes loading-dot-flash {
    0% { opacity: 0.2; }
    50% { opacity: 1; }
    100% { opacity: 0.2; }
}

/* --- Mobile Responsiveness (Media Queries) --- */

@media (max-width: 992px) {
    /* For Tablets and smaller desktops */
    .chat-container {
        width: 85%;
        min-height: 60vh;
        max-height: 80vh;
    }
    .input-box {
        width: 85%;
    }
    .chat-faqs {
        width: 85%;
    }
    .voice-btn span {
        /* Hide text on tablets for more compact button */
        display: none !important;
    }
    .voice-btn i {
        /* Ensure icon is visible */
        display: inline !important;
    }
    .btn-predefined {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
    }
}

@media (max-width: 576px) {
    /* For Mobile Phones */
    .chat-container {
        width: 95%;
        min-height: 70vh;
    }
    .input-box {
        width: 95%;
        /* Adjust layout for better use of space */
        flex-wrap: nowrap;
    }
    .chat-faqs {
        width: 95%;
    }
    h1 {
        font-size: 1.5rem;
    }
    .btn-predefined {
        padding: 0.3rem 0.5rem;
        font-size: 0.8rem;
    }
    .chat-faqs .btn-sm {
        font-size: 0.75rem;
    }
    .voice-btn span {
        /* Hide text on mobile for small button */
        display: none !important;
    }
    .voice-btn i {
        /* Ensure icon is visible */
        display: inline !important;
    }
}
</style>
