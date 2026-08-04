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
</script>
