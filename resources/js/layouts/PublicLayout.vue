<template>
    <div class="min-h-screen flex flex-col relative">
        <main class="flex-grow">
            <RouterView />
        </main>

        <Footer />
    </div>
</template>

<script setup>
import Footer from '../components/public/Footer.vue'
import { RouterView } from 'vue-router'
</script>
