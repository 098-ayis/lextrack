<script setup>
import { ref } from 'vue'

const message = ref('')
const loading = ref(false)
const messages = ref([
  {
    role: 'assistant',
    content: 'Hello! How can I help you with LexTrack?'
  }
])

async function sendMessage() {
  const text = message.value.trim()

  if (!text || loading.value) return

  messages.value.push({ role: 'user', content: text })
  message.value = ''
  loading.value = true

  try {
    const token = document.querySelector(
      'meta[name="csrf-token"]'
    )?.content

    const response = await fetch('/chatbot/message', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': token ?? ''
      },
      body: JSON.stringify({ message: text })
    })

    if (!response.ok) {
      throw new Error(`Chatbot request failed: ${response.status}`)
    }

    const data = await response.json()

    messages.value.push({
      role: 'assistant',
      content: data.reply
    })
  } catch (error) {
    console.error('Chatbot request failed:', error)

    messages.value.push({
      role: 'assistant',
      content: 'Sorry, I could not process your request. Please try again.'
    })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="chatbot">
    <div class="chatbot-header">
      <div>
        <p class="chatbot-eyebrow">BU LEGAL AFFAIRS</p>
        <h3>LexTrack Assistant</h3>
      </div>
      <span class="status-dot" aria-label="Assistant online"></span>
    </div>

    <div class="chat-messages" aria-live="polite">
      <div
        v-for="(item, index) in messages"
        :key="index"
        :class="['chat-message', item.role]"
      >
        {{ item.content }}
      </div>

      <p v-if="loading">Assistant is typing...</p>
    </div>

    <form class="chat-form" @submit.prevent="sendMessage">
      <input
        v-model="message"
        type="text"
        placeholder="Ask a question..."
        maxlength="1000"
        :disabled="loading"
        aria-label="Message LexTrack Assistant"
      />

      <button type="submit" :disabled="loading || !message.trim()">
        <span>{{ loading ? 'Sending…' : 'Send' }}</span>
      </button>
    </form>
  </div>
</template>

<style scoped>
.chatbot {
  position: fixed;
  right: 24px;
  bottom: 24px;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  width: min(360px, calc(100vw - 32px));
  max-height: min(560px, calc(100vh - 48px));
  overflow: hidden;
  color: #0f172a;
  background: #ffffff;
  border: 1px solid rgba(107, 119, 255, 0.2);
  border-radius: 18px;
  box-shadow: 0 18px 50px rgba(15, 23, 42, 0.28);
}

.chatbot-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 20px;
  color: #ffffff;
  background: #0f172a;
}

.chatbot-eyebrow {
  margin: 0 0 3px;
  color: #9dd9fb;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.12em;
}

.chatbot h3 {
  margin: 0;
  font-size: 17px;
  font-weight: 700;
  letter-spacing: 0.01em;
}

.status-dot {
  width: 10px;
  height: 10px;
  flex: 0 0 auto;
  border: 2px solid #0f172a;
  border-radius: 999px;
  background: #34d399;
  box-shadow: 0 0 0 3px rgba(52, 211, 153, 0.18);
}

.chat-messages {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 10px;
  min-height: 130px;
  max-height: 360px;
  overflow-y: auto;
  padding: 18px;
  background: #f8fafc;
}

.chat-message {
  width: fit-content;
  max-width: 86%;
  padding: 10px 13px;
  border-radius: 14px;
  font-size: 13px;
  line-height: 1.55;
  overflow-wrap: anywhere;
}

.chat-message.assistant {
  align-self: flex-start;
  color: #334155;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-bottom-left-radius: 4px;
}

.chat-message.user {
  align-self: flex-end;
  color: #ffffff;
  background: #6b77ff;
  border-bottom-right-radius: 4px;
}

.chat-messages > p {
  align-self: flex-start;
  margin: 0;
  color: #64748b;
  font-size: 12px;
  font-style: italic;
}

.chat-form {
  display: flex;
  gap: 8px;
  padding: 14px;
  background: #ffffff;
  border-top: 1px solid #e2e8f0;
}

.chat-form input {
  min-width: 0;
  flex: 1;
  padding: 11px 12px;
  color: #0f172a;
  background: #f8fafc;
  border: 1px solid #cbd5e1;
  border-radius: 9px;
  outline: none;
  font: inherit;
  font-size: 13px;
}

.chat-form input:focus {
  border-color: #6b77ff;
  box-shadow: 0 0 0 3px rgba(107, 119, 255, 0.15);
}

.chat-form input:disabled {
  cursor: wait;
  opacity: 0.7;
}

.chat-form button {
  flex: 0 0 auto;
  padding: 0 14px;
  color: #ffffff;
  background: #6b77ff;
  border: 0;
  border-radius: 9px;
  font: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s ease, transform 0.2s ease;
}

.chat-form button:hover:not(:disabled) {
  background: #5a65e0;
  transform: translateY(-1px);
}

.chat-form button:disabled {
  cursor: not-allowed;
  opacity: 0.55;
}

@media (max-width: 480px) {
  .chatbot {
    right: 16px;
    bottom: 16px;
    width: calc(100vw - 32px);
    max-height: calc(100vh - 32px);
  }

  .chat-messages {
    max-height: min(360px, 45vh);
  }
}
</style>
