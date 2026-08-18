<template>
  <!-- Full screen wrapper to center the card -->
  <div class="min-h-[80vh] w-full flex items-center justify-center p-4 md:p-8">

    <!-- Main Card Container -->
    <div 
      class="relative w-full max-w-[750px] min-h-[420px] bg-white rounded-3xl 
             shadow-[0_10px_40px_rgba(0,0,0,0.15)] flex flex-col md:flex-row overflow-hidden"
    >
      
      <!-- ==========================================
           THE MAGIC CURVE (Desktop Only)
      =========================================== -->
      <div 
        class="hidden md:block absolute top-1/2 -translate-y-1/2 -left-[35%] w-[80%] aspect-square 
               bg-[#121722] rounded-full border-[8px] border-[#6b77ff] z-0 pointer-events-none"
      ></div>

      <!-- ==========================================
           LEFT SIDE (Dark Panel)
      =========================================== -->
      <div class="relative z-10 w-full md:w-1/2 pt-6 px-6 pb-0 md:pt-8 md:px-8 md:pb-0 flex flex-col bg-[#121722] md:bg-transparent min-h-[300px] md:min-h-full">

        <router-link 
          to="/" 
          class="relative z-50 flex items-center gap-2 text-white/80 hover:text-white transition w-fit mt-2 md:mt-0"
        >
          <Icon icon="lucide:arrow-left" class="w-4 h-4" />
          <span class="text-sm font-medium">Back to Home</span>
        </router-link>

        <!-- Torch Graphic -->
        <div class="flex-1 flex items-end justify-center -ml-0 md:-ml-12 mt-6 md:mt-0 pointer-events-none">
          <img 
            src="/resources/images/bu-torchv2.png" 
            alt="BU Torch" 
            class="h-[280px] md:h-[350px] w-auto object-contain object-bottom drop-shadow-2xl scale-[1.15] origin-bottom pointer-events-none" 
          />
        </div>
      </div>

      <!-- ==========================================
           RIGHT SIDE (White Panel & Form)
      =========================================== -->
      <div class="relative z-10 w-full md:w-1/2 p-6 md:py-8 md:pl-0 md:pr-16 flex flex-col justify-center items-center">

        <!-- Title -->
        <div class="text-center mb-8">
          <h1 class="text-2xl md:text-3xl font-black text-[#121722] tracking-tight mb-1">
            BU LEGAL AFFAIRS
          </h1>
          <h2 class="text-2xl md:text-3xl font-black text-[#6b77ff] tracking-tight">
            OFFICE PORTAL
          </h2>
        </div>

        <!-- Google Login Button -->
        <a 
          href="/auth/google"
          class="w-auto px-8 h-[50px] border-2 border-[#a5b4fc] bg-white 
                 hover:bg-[#828cff] hover:border-[#828cff] hover:text-white rounded-xl flex items-center justify-center gap-3 
                 text-[#334155] text-[14px] font-semibold transition-all duration-300
                 shadow-sm hover:shadow-md hover:-translate-y-[1px] whitespace-nowrap"
        >
          <Icon icon="logos:google-icon" class="w-[20px] h-[20px] flex-shrink-0" />
          <span>Continue with Google (@bicol-u.edu.ph)</span>
        </a>
        
        <!-- Subtext -->
        <p class="text-[12px] text-gray-400 mt-6 text-center">
          Not registered?
          <br class="block md:hidden" />
          <a href="/track" class="text-gray-500 hover:text-[#6b77ff] underline underline-offset-2 transition-colors ml-1">
            Click here to track your documents.
          </a>
        </p>

      </div>

    </div>
  </div>
</template>

<script setup>
import { Icon } from '@iconify/vue'
</script>
<style scoped>
</style>