<template>
  <header 
    :class="[
      'text-white w-full z-50 top-0 transition-all duration-300 fixed', 
      headerBackground
    ]"
  >
    
    <div class="max-w-[1350px] mx-auto flex items-center justify-between px-10 py-5">

      <div class="flex items-center gap-16">
        <!-- Logo -->
        <RouterLink to="/" class="flex items-center gap-4">
          <img
            :src="logo"
            alt="Bicol University Logo"
            class="w-12 h-13"
          />

          <div>
            <p class="text-[11px] tracking-wider uppercase">
              <span class="text-[#9DD9FB] font-bold">Bicol </span>
              <span class="text-orange-500 font-bold">University</span>
            </p>
            <h1 class="font-bold text-[26px] tracking-wide leading-tight">
              Legal Office
            </h1>
          </div>
        </RouterLink>

        <!-- Desktop Navigation -->
        <nav class="hidden md:flex gap-10 font-bold text-[13px] tracking-wider">
          <RouterLink
            v-for="link in links"
            :key="link.path"
            :to="link.path"
            class="uppercase transition-colors pb-1 border-b-2"
            :class="route.path === link.path ? 'text-[#6b77ff] border-[#6b77ff]' : 'text-gray-200 border-transparent hover:text-[#6b77ff]'"
          >
            {{ link.name }}
          </RouterLink>
        </nav>
        
      </div> 

      <!-- Right Side -->
      <div class="flex items-center gap-5">
        <RouterLink
          to="/login"
          class="hidden md:flex items-center justify-center bg-[#6b77ff] hover:bg-[#5a65e0] px-8 py-3 rounded-full font-bold text-[13px] tracking-wider transition"
        >
          SIGN IN
        </RouterLink> 

        <button
          @click="menuOpen = !menuOpen"
          class="md:hidden text-2xl text-gray-200"
        >
          ☰
        </button>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div
      v-if="menuOpen"
      class="md:hidden bg-[#1a2035]"
    >
      <RouterLink
        v-for="link in links"
        :key="link.path"
        :to="link.path"
        class="block px-6 py-4 hover:bg-[#252d47] font-semibold tracking-wider text-sm border-b border-gray-700"
        @click="menuOpen = false"
      >
        {{ link.name }}
      </RouterLink>
      <RouterLink
        to="/login"
        class="block px-6 py-4 hover:bg-[#252d47] font-semibold tracking-wider text-sm text-[#6b77ff]"
        @click="menuOpen = false"
      >
        SIGN IN
      </RouterLink>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import logo from '../../../images/bu-logo.png'

const route = useRoute()
const isHomePage = computed(() => route.path === '/')

const isScrolled = ref(false)

const headerBackground = computed(() => {
  if (!isHomePage.value) {
    return 'bg-[#0F172A] shadow-lg'
  }
  
  return isScrolled.value ? 'bg-[#0F172A] shadow-lg' : 'bg-transparent'
})

const handleScroll = () => {
  isScrolled.value = window.scrollY > 50
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})

const links = [
  { name: 'HOME', path: '/' },
  { name: 'ABOUT', path: '/about' },
  { name: 'TRACK', path: '/track' },
  { name: 'LEGAL FORMS', path: '/forms' }
]

const menuOpen = ref(false)
</script>