import { createApp } from 'vue'
import Chatbot from './components/public/Chatbot.vue'

const chatbotRoot = document.getElementById('client-chatbot')

if (chatbotRoot) {
    createApp(Chatbot, {
        sessionKey: chatbotRoot.dataset.sessionKey || '',
    }).mount(chatbotRoot)
}
