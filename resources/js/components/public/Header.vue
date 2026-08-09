<template>
  <header class="bg-[#1b3a5c] text-white shadow-md fixed w-full z-50 top-0">
    <div class="max-w-7xl mx-auto flex items-center justify-between px-6 py-4">

      <!-- Logo -->
      <RouterLink to="/" class="flex items-center gap-3">
        <img
          :src="logo"
          alt="Bicol University Logo"
          class="w-10 h-11"
        />

        <div>
          <p class="text-xs tracking-wider">
            <span class="text-[#9DD9FB] font-bold">Bicol </span>
            <span class="text-orange-400">University</span>
          </p>

          <h1 class="font-bold text-lg">
            Legal Office
          </h1>
        </div>
      </RouterLink>

      <!-- Desktop Navigation -->
      <nav class="hidden md:block flex gap-8 font-semibold text-sm space-x-6">
        <RouterLink
          v-for="link in links"
          :key="link.path"
          :to="link.path"
          class="hover:text-orange-400 transition-colors duration-200"
          :class="{ 'text-orange-400': route.path === link.path }"
        >
          {{ link.name }}
        </RouterLink>
        <!--<ul class="flex gap-8 font-semibold text-sm">
          <li>
            <RouterLink
              to="/"
              class="hover:text-orange-400"
              active-class="text-orange-400"
            >
              HOME
            </RouterLink>
          </li>

          <li>
            <RouterLink
              to="/about"
              class="hover:text-orange-400"
              active-class="text-orange-400"
            >
              ABOUT
            </RouterLink>
          </li>

          <li>
            <RouterLink
              to="/track"
              class="hover:text-orange-400"
              active-class="text-orange-400"
            >
              TRACK
            </RouterLink>
          </li>

          <li>
            <RouterLink
              to="/forms"
              class="hover:text-orange-400"
              active-class="text-orange-400"
            >
              LEGAL FORMS
            </RouterLink>
          </li>
        </ul>-->
      </nav>

      <!-- Right Side -->
      <div class="flex items-center gap-5">
        <div class="hidden md:block h-6 border-l border-white/30"></div>

        <RouterLink
          to="/login"
          class="hidden md:flex items-center gap-2 hover:text-orange-400"
        >

          <span class="font-semibold text-sm">
            SIGN IN
          </span>
        </RouterLink> 

        <!-- Mobile Button -->
        <button
          @click="menuOpen = !menuOpen"
          class="md:hidden text-2xl"
        >
          ☰
        </button>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div
      v-if="menuOpen"
      class="md:hidden bg-[#142c47]"
    >
      <RouterLink
        to="/hero"
        class="block px-6 py-3 hover:bg-[#1b3a5c]"
        @click="menuOpen = false"
      >
        Home
      </RouterLink>

      <RouterLink
        to="/about"
        class="block px-6 py-3 hover:bg-[#1b3a5c]"
        @click="menuOpen = false"
      >
        About
      </RouterLink>

      <RouterLink
        to="/track"
        class="block px-6 py-3 hover:bg-[#1b3a5c]"
        @click="menuOpen = false"
      >
        Track
      </RouterLink>

      <RouterLink
        to="/forms"
        class="block px-6 py-3 hover:bg-[#1b3a5c]"
        @click="menuOpen = false"
      >
        Legal Forms
      </RouterLink>
    </div>
  </header>
</template>

<script setup>
import { ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import logo from '../../../images/bu-logo.png'

const route = useRoute()

const links = [
  { name: 'HOME', path: '/' },
  { name: 'ABOUT', path: '/about' },
  { name: 'TRACK', path: '/track' },
  { name: 'LEGAL FORMS', path: '/forms' }
]

const menuOpen = ref(false)
</script>