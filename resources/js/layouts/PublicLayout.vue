<template>
    <div class="min-h-screen flex flex-col relative">
        <Header />

        <main class="flex-grow">
            <RouterView />
        </main>

        <Footer />
    </div>
</template>

<script setup>
import Header from '../components/public/Header.vue'
import Footer from '../components/public/Footer.vue'
import { RouterView } from 'vue-router'
import { onBeforeUnmount, onMounted } from 'vue'

const botpressInjectUrl = 'https://cdn.botpress.cloud/webchat/v5.0/inject.js'
const botpressConfigUrl = 'https://files.bpcontent.cloud/2026/09/09/12/20260909121205-OT8PRP1L.js'
let botpressActive = false

const removeBotpressUi = () => {
    window.botpress?.close?.()
    document.querySelectorAll('.bpWebchat, .bpFab').forEach((element) => element.remove())
}

onMounted(() => {
    botpressActive = true
    const injectScript = document.createElement('script')

    injectScript.id = 'lextrack-botpress-inject'
    injectScript.src = botpressInjectUrl

    injectScript.addEventListener('load', () => {
        if (!botpressActive) return

        const configScript = document.createElement('script')
        configScript.id = 'lextrack-botpress-config'
        configScript.src = botpressConfigUrl
        configScript.async = false
        configScript.defer = true
        configScript.addEventListener('load', () => {
            if (!botpressActive) removeBotpressUi()
        }, { once: true })
        document.head.appendChild(configScript)
    }, { once: true })

    document.head.appendChild(injectScript)
})

onBeforeUnmount(() => {
    botpressActive = false
    removeBotpressUi()
    document.getElementById('lextrack-botpress-inject')?.remove()
    document.getElementById('lextrack-botpress-config')?.remove()
})
</script>
