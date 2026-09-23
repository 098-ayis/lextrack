<script setup>
import { nextTick, ref } from 'vue'

const props = defineProps({
  sessionKey: {
    type: String,
    default: ''
  }
})

const CHAT_STORAGE_KEY = 'lextrack-chatbot-session'
const WELCOME_MESSAGE = {
  role: 'assistant',
  content: 'Hello! How can I help you with LexTrack?'
}

function newConversationId() {
  return globalThis.crypto?.randomUUID?.() || `chat-${Date.now()}-${Math.random().toString(36).slice(2)}`
}

function loadChatState() {
  if (!props.sessionKey) return null

  try {
    const stored = globalThis.sessionStorage?.getItem(CHAT_STORAGE_KEY)
    const state = stored ? JSON.parse(stored) : null

    if (
      !state
      || state.sessionKey !== props.sessionKey
      || typeof state.conversationId !== 'string'
      || !Array.isArray(state.messages)
    ) {
      if (state && state.sessionKey !== props.sessionKey) {
        globalThis.sessionStorage?.removeItem(CHAT_STORAGE_KEY)
      }

      return null
    }

    const messages = state.messages.filter(
      (item) => item
        && (item.role === 'assistant' || item.role === 'user')
        && typeof item.content === 'string'
    )

    return messages.length > 0
      ? { conversationId: state.conversationId, messages }
      : null
  } catch (error) {
    console.warn('Could not restore the temporary chatbot history.', error)
    return null
  }
}

function persistChatState() {
  if (!props.sessionKey) return

  try {
    globalThis.sessionStorage?.setItem(CHAT_STORAGE_KEY, JSON.stringify({
      sessionKey: props.sessionKey,
      conversationId: conversationId.value,
      messages: messages.value.slice(-100)
    }))
  } catch (error) {
    console.warn('Could not save the temporary chatbot history.', error)
  }
}

const savedChat = loadChatState()
const message = ref('')
const loading = ref(false)
const isOpen = ref(false)
const chatMessages = ref(null)
const conversationId = ref(savedChat?.conversationId || newConversationId())
const messages = ref(savedChat?.messages || [{ ...WELCOME_MESSAGE }])

async function scrollToLatest() {
  await nextTick()

  if (chatMessages.value) {
    chatMessages.value.scrollTop = chatMessages.value.scrollHeight

    requestAnimationFrame(() => {
      if (chatMessages.value) {
        chatMessages.value.scrollTop = chatMessages.value.scrollHeight
      }
    })
  }
}

async function openChat() {
  isOpen.value = true
  await scrollToLatest()
}

async function sendMessage() {
  const text = message.value.trim()

  if (!text || loading.value) return

  messages.value.push({ role: 'user', content: text })
  persistChatState()
  await scrollToLatest()
  message.value = ''
  loading.value = true
  await scrollToLatest()

  let failureReply = 'Sorry, I could not process your request right now. Please try again.'

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
      body: JSON.stringify({
        message: text,
        conversation_id: conversationId.value
      })
    })

    const data = await response.json().catch(() => ({}))

    if (typeof data.reply === 'string' && data.reply.trim() !== '') {
      failureReply = data.reply
    }

    if (!response.ok) {
      throw new Error('CHATBOT_REQUEST_FAILED')
    }

    if (typeof data.reply !== 'string' || data.reply.trim() === '') {
      throw new Error('CHATBOT_INVALID_RESPONSE')
    }

    messages.value.push({
      role: 'assistant',
      content: data.reply
    })
    persistChatState()
    await scrollToLatest()
  } catch (error) {
    console.error('Chatbot request failed:', error)

    messages.value.push({
      role: 'assistant',
      content: failureReply
    })
    persistChatState()
    await scrollToLatest()
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="chatbot-widget">
    <section
      v-if="isOpen"
      id="lextrack-chatbot-panel"
      class="chatbot-panel"
      role="dialog"
      aria-labelledby="chatbot-title"
      aria-modal="false"
    >
      <div class="chatbot-header">
        <div class="chatbot-brand-icon" aria-hidden="true">
          <svg viewBox="0 0 32 32" fill="none">
            <path d="M16 5V8" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            <circle cx="16" cy="4" r="1.5" fill="currentColor" />
            <rect x="6" y="9" width="20" height="16" rx="6" stroke="currentColor" stroke-width="2" />
            <path d="M3.5 14v5M28.5 14v5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
            <circle cx="12" cy="16" r="1.5" fill="currentColor" />
            <circle cx="20" cy="16" r="1.5" fill="currentColor" />
            <path d="M12 21h8" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
        </div>
        <div class="chatbot-heading">
          <h3 id="chatbot-title">LexTrack Assistant</h3>
          <p class="chatbot-history-note">AI conversation history is temporary, available while you are logged in, and cleared when you log out.</p>
        </div>
      </div>

      <button
        class="chatbot-close"
        type="button"
        aria-label="Close LexTrack Assistant"
        @click="isOpen = false"
      >
        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="m6 6 12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
      </button>

      <div ref="chatMessages" class="chat-messages" aria-live="polite">
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
    </section>

    <button
      v-else
      class="chatbot-launcher"
      type="button"
      aria-label="Open LexTrack Assistant chat"
      aria-controls="lextrack-chatbot-panel"
      :aria-expanded="isOpen"
      @click="openChat"
    >
      <svg viewBox="0 0 40 40" fill="none" aria-hidden="true">
        <path d="M20 5.5v4" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
        <circle cx="20" cy="4.5" r="2" fill="currentColor" />
        <rect x="8" y="10" width="24" height="20" rx="7" stroke="currentColor" stroke-width="2.4" />
        <path d="M5 16v7M35 16v7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
        <circle cx="15" cy="19" r="2" fill="currentColor" />
        <circle cx="25" cy="19" r="2" fill="currentColor" />
        <path d="M15 25h10" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" />
      </svg>
      <span class="chatbot-launcher-tooltip">Chat with LexTrack</span>
    </button>
  </div>
</template>

<style scoped>

.chatbot-widget {
  position: fixed;
  right: 24px;
  bottom: 24px;
  z-index: 1000;
}

.chatbot-panel {
  position: relative;
  display: flex;
  flex-direction: column;
  width: min(360px, calc(100vw - 32px));
  height: min(560px, calc(100vh - 48px));
  overflow: hidden;
  color: #0f172a;
  background: #ffffff;
  border: 1px solid rgba(107, 119, 255, 0.2);
  border-radius: 18px;
  box-shadow: 0 18px 50px rgba(15, 23, 42, 0.28);
}

.chatbot-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  min-height: 88px;
  padding: 17px 54px 15px 18px;
  color: #ffffff;
  background: #0f172a;
}

.chatbot-brand-icon {
  display: grid;
  width: 42px;
  height: 42px;
  flex: 0 0 auto;
  place-items: center;
  color: #ffffff;
  background: #6b77ff;
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 14px;
}

.chatbot-brand-icon svg {
  width: 30px;
  height: 30px;
}

.chatbot-heading {
  min-width: 0;
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

.chatbot-status {
  display: flex;
  align-items: center;
  gap: 7px;
  margin: 5px 0 0;
  color: #cbd5e1;
  font-size: 11px;
}

.chatbot-history-note {
  max-width: 235px;
  margin: 7px 0 0;
  color: rgba(255, 255, 255, 0.72);
  font-size: 10px;
  line-height: 1.35;
}

.status-dot {
  width: 7px;
  height: 7px;
  flex: 0 0 auto;
  border-radius: 999px;
  background: #34d399;
}

.chatbot-close {
  position: absolute;
  top: 16px;
  right: 16px;
  display: grid;
  width: 34px;
  height: 34px;
  place-items: center;
  color: #e2e8f0;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.2s ease, color 0.2s ease;
}

.chatbot-close:hover {
  color: #ffffff;
  background: rgba(255, 255, 255, 0.2);
}

.chatbot-close svg {
  width: 18px;
  height: 18px;
}

.chatbot-close:focus-visible,
.chatbot-launcher:focus-visible {
  outline: 3px solid #9dd9fb;
  outline-offset: 3px;
}

.chat-messages {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 10px;
  min-height: 0;
  overflow-y: auto;
  padding: 18px;
  background: #f8fafc;
}

.chat-message {
  width: fit-content;
  max-width: 86%;
  min-width: 0;
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
  white-space: pre-wrap;
  line-height: 1.65;
  overflow-wrap: anywhere;
  word-break: normal;
  tab-size: 2;
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

.chatbot-launcher {
  position: relative;
  display: grid;
  width: 68px;
  height: 68px;
  place-items: center;
  color: #ffffff;
  background: linear-gradient(145deg, #828cff, #5966ef);
  border: 3px solid #ffffff;
  border-radius: 50%;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.3), 0 0 0 1px rgba(107, 119, 255, 0.25);
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.chatbot-launcher:hover {
  transform: translateY(-3px) scale(1.04);
  box-shadow: 0 14px 34px rgba(15, 23, 42, 0.34), 0 0 0 5px rgba(107, 119, 255, 0.18);
}

.chatbot-launcher svg {
  width: 39px;
  height: 39px;
}

.chatbot-launcher-tooltip {
  position: absolute;
  right: calc(100% + 12px);
  padding: 8px 11px;
  color: #ffffff;
  background: #0f172a;
  border-radius: 8px;
  box-shadow: 0 5px 18px rgba(15, 23, 42, 0.18);
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transform: translateX(4px);
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.chatbot-launcher:hover .chatbot-launcher-tooltip,
.chatbot-launcher:focus-visible .chatbot-launcher-tooltip {
  opacity: 1;
  transform: translateX(0);
}

@media (max-width: 480px) {
  .chatbot-widget {
    right: 16px;
    bottom: 16px;
  }

  .chatbot-panel {
    width: calc(100vw - 32px);
    height: min(560px, calc(100vh - 32px));
  }

  .chatbot-launcher {
    width: 62px;
    height: 62px;
  }
}
</style>
