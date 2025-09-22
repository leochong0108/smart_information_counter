<template>
  <div class="chat-widget">
    <div v-for="(m, idx) in messages" :key="idx" class="message">
      <div :class="['bubble', m.from]">{{ m.text }}</div>
    </div>
    <div class="input-box">
      <input v-model="input" @keyup.enter="sendMessage" placeholder="Ask me..." />
      <button @click="sendMessage">Send</button>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { sendMessageToAI } from "../services/api";

const messages = ref([]);
const input = ref("");

const sendMessage = async () => {
  if (!input.value.trim()) return;

  // Push user message
  messages.value.push({ from: "user", text: input.value });

  // Send to backend
  const reply = await sendMessageToAI(input.value);

  // Push AI response
  messages.value.push({ from: "ai", text: reply });

  input.value = "";
};
</script>

<style scoped>
.chat-widget {
  max-width: 400px;
  margin: auto;
  border: 1px solid #ccc;
  border-radius: 12px;
  padding: 10px;
}

.bubble {
  margin: 5px 0;
  padding: 8px 12px;
  border-radius: 16px;
  max-width: 80%;
}

.user {
  background: #007bff;
  color: white;
  text-align: right;
  margin-left: auto;
}

.ai {
  background: #f1f1f1;
  text-align: left;
  margin-right: auto;
}
</style>
