<template>
    <div>
          <img :src="images.logo">
          <img :src="images.lao">

        <h1>LexTrack</h1>

        <h2>Ensuring Legal Strength for Bicol University.</h2>

        <p>
            A smart, secure, and client-friendly system that empowers the Legal
            Affairs Office to manage documents efficiently while offering instant
            support through AI-driven chat.
        </p>

        <button @click="goToSubmit">
            Submit Document
        </button>

        <button @click="goToTrack">
            Track Document
        </button>
    </div>
</template>

<script setup>
import images from '../constants/images'

function goToSubmit() {
    window.location.href = "/testpage";
}

function goToTrack() {
    window.location.href = "/testpage";
}
</script>

<style scoped>

img{
    width:500px;
}

button{
    margin:10px;
}

</style>