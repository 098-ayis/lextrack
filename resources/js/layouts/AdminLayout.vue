<template>
  <h1>Admin Dashboard</h1>
  <button @click="logout">
    Logout
</button>
</template>

<script setup>
import { useRouter } from 'vue-router'

const router = useRouter()

async function logout() {
    await fetch('/logout', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute('content'),

            'Accept': 'application/json',
        },
    })

    router.push('/login')
}
</script>

<style>

</style>