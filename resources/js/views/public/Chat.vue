<template>
  <div class="container-fluid d-flex flex-column justify-content-center align-items-center vh-100 position-relative">

    <button @click="endChat" class="btn btn-secondary end-chat-btn position-absolute top-0 end-0 m-3">End Chat</button>

    <div v-if="messages.length === 0" class="initial-view text-center">
      <h1 class="text-muted">Hi Welcome, Ask something you want to know about the Southern University College</h1>
      <div class="container mt-5">
        <div class="row g-2 justify-content-center">
          <div class="col-auto">
            <button @click="setInputAndSend('I want to pay tuition fee.')" class="btn btn-predefined">I want to pay tuition fee.</button>
          </div>
          <div class="col-auto">
            <button @click="setInputAndSend('I want to apply scholarship.')" class="btn btn-predefined">I want to apply scholarship.</button>
          </div>
          <div class="col-auto">
            <button @click="setInputAndSend('where to register?')" class="btn btn-predefined">where to register?</button>
          </div>
          <div class="col-auto">
            <button @click="setInputAndSend('I want to apply for an event.')" class="btn btn-predefined">I want to apply for an event.</button>
          </div>
          <div class="col-auto">
            <button @click="setInputAndSend('where to collect my parcel?')" class="btn btn-predefined">where to collect my parcel?</button>
          </div>
          <div class="col-auto">
            <button @click="setInputAndSend('where to collect my student card?')" class="btn btn-predefined">where to collect my student card?</button>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="chat-container">
      <div v-for="(m, idx) in messages" :key="idx" class="message d-flex" :class="m.from === 'user' ? 'justify-content-end' : 'justify-content-start'">
        <div class="bubble p-2 m-1" :class="m.from === 'user' ? 'user-bubble' : 'ai-bubble'">{{ m.text }}</div>
      </div>
      <div v-if="isLoading" class="d-flex justify-content-start message">
        <div class="bubble p-2 m-1 ai-bubble">...</div>
      </div>
    </div>

    <div class="input-box d-flex w-50">
      <button class="btn btn-predefined me-2">
        <i class="bi bi-mic"></i> Voice Input
      </button>
      <input v-model="input" @keyup.enter="sendMessage" class="form-control me-2" placeholder="Ask here" />
      <button @click="sendMessage" class="btn btn-primary">Send</button>
    </div>

    <div v-if="showModal" class="modal-overlay">
      <div class="custom-modal">
        <div class="modal-content">
          <p>Are you still there? You will be redirected to the home page in {{ countdown }} seconds.</p>
          <div class="d-flex justify-content-center">
            <button @click="continueChat" class="btn btn-primary me-2">Yes, continue</button>
            <button @click="endChatImmediate" class="btn btn-secondary">No, end chat</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { sendMessageToAI } from "../../services/api";
import { useRouter } from 'vue-router';

const messages = ref([]);
const input = ref("");
const isLoading = ref(false);
const showModal = ref(false);
const countdown = ref(10);
const router = useRouter();

let idleTimer;
let modalCountdownTimer;

const resetIdleTimer = () => {
  // Clear any existing timers
  clearTimeout(idleTimer);
  clearTimeout(modalCountdownTimer);
  // Hide the modal if it's visible
  showModal.value = false;
  // Start a new timer for 30 seconds
  idleTimer = setTimeout(showModalAndCountdown, 30000);
};

const showModalAndCountdown = () => {
  showModal.value = true;
  countdown.value = 10;
  modalCountdownTimer = setInterval(() => {
    countdown.value--;
    if (countdown.value <= 0) {
      clearInterval(modalCountdownTimer);
      router.push('/');
    }
  }, 1000);
};

const continueChat = () => {
  showModal.value = false;
  clearInterval(modalCountdownTimer);
  resetIdleTimer();
};

const endChatImmediate = () => {
  showModal.value = false;
  clearInterval(modalCountdownTimer);
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
    const reply = await sendMessageToAI(userMessage);
    messages.value.push({ from: "ai", text: reply });
  } catch (error) {
    console.error("Failed to get AI reply:", error);
    messages.value.push({ from: "ai", text: "Sorry, I am unable to respond at this time." });
  } finally {
    isLoading.value = false;
  }
};

const setInputAndSend = (question) => {
  resetIdleTimer();
  input.value = question;
  sendMessage();
};

const endChat = () => {
  if (window.confirm("Are you sure you want to end this chat?")) {
    messages.value = [];
    router.push('/');
  }
};

onMounted(() => {
  resetIdleTimer();
});

onUnmounted(() => {
  clearTimeout(idleTimer);
  clearInterval(modalCountdownTimer);
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
  width: 70%;
  max-width: 800px;
  min-height: 40vh;
  padding-bottom: 20px;
  overflow-y: auto;
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
}
.btn-predefined:hover {
  background-color: #dcdce6;
}
.input-box {
  margin-top: 1rem;
}
.end-chat-btn {
  z-index: 1000;
}

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
</style>
