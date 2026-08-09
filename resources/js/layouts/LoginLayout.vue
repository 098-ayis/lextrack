<template>
    <div class="min-h-screen flex flex-col bg-[#f5f5f7]">

        <Header />

        <main class="flex-1 flex items-center justify-center">
                <RouterView />
        </main>

    </div>
</template>

<script setup>
import Header from '../components/public/Header.vue'
</script>

