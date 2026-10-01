import { createApp } from 'vue'
import Chatbot from './components/public/Chatbot.vue'

const chatbotRoot = document.getElementById('client-chatbot')

if (chatbotRoot) {
    let prohibitedTerms = []

    try {
        const parsedTerms = JSON.parse(chatbotRoot.dataset.prohibitedTerms || '[]')
        prohibitedTerms = Array.isArray(parsedTerms) ? parsedTerms : []
    } catch {
        prohibitedTerms = []
    }

    createApp(Chatbot, {
        sessionKey: chatbotRoot.dataset.sessionKey || '',
        prohibitedTerms,
    }).mount(chatbotRoot)
}
