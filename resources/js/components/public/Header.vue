<template>
  <header 
    :class="[
      'text-white w-full z-50 top-0 transition-all duration-300 fixed', 
      headerBackground
    ]"
  >
    
    <div class="max-w-[1350px] mx-auto flex min-w-0 items-center justify-between gap-4 px-4 py-3 sm:px-6 sm:py-4 md:px-10 md:py-5">

      <div class="flex min-w-0 items-center gap-4 md:gap-16">
        <!-- Logo -->
        <RouterLink to="/" class="flex min-w-0 items-center gap-2 sm:gap-4">
          <img
            :src="logo"
            alt="Bicol University Logo"
            class="h-10 w-10 shrink-0 sm:h-12 sm:w-12"
          />

          <div class="min-w-0">
            <p class="truncate text-[9px] tracking-wider uppercase sm:text-[11px]">
              <span class="text-[#9DD9FB] font-bold">Bicol </span>
              <span class="text-orange-500 font-bold">University</span>
            </p>
            <h1 class="truncate font-bold text-lg tracking-wide leading-tight sm:text-[26px]">
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
      <div class="flex shrink-0 items-center gap-3 sm:gap-5">
        <RouterLink
          to="/login"
          class="hidden md:flex items-center justify-center bg-[#6b77ff] hover:bg-[#5a65e0] px-8 py-3 rounded-full font-bold text-[13px] tracking-wider transition"
        >
          SIGN IN
        </RouterLink> 

        <button
          @click="menuOpen = !menuOpen"
          :aria-expanded="menuOpen"
          aria-label="Toggle navigation menu"
          class="md:hidden flex h-10 w-10 items-center justify-center rounded-lg text-2xl leading-none text-gray-200 hover:bg-white/10"
        >
          <span aria-hidden="true">{{ menuOpen ? '×' : '☰' }}</span>
        </button>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div
      v-if="menuOpen"
      class="md:hidden border-t border-white/10 bg-[#1a2035]"
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
]

const menuOpen = ref(false)
</script>
