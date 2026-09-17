<template>
    <div class="min-h-screen flex flex-col relative">
        <Header />

        <main class="flex-grow">
            <RouterView />
        </main>

        <Chatbot />

        <Footer />
    </div>
</template>

<script setup>
import Header from '../components/public/Header.vue'
import Footer from '../components/public/Footer.vue'
import Chatbot from '../components/public/Chatbot.vue'
import { RouterView } from 'vue-router'
</script>
