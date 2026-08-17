<template>
  <!-- CHANGED: Increased pt-28 to pt-36 to push the card even further down -->
  <main class="w-full flex flex-col items-center min-h-[80vh] pt-36 pb-16 px-6 bg-[#f4f5f7]">
    
    <!-- Main Tracking Card Container -->
    <div class="w-full max-w-3xl bg-white rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.08)] border border-gray-100 overflow-hidden mb-6">
      
      <!-- Dark Header Section Inside Card -->
      <div class="bg-[#121722] px-6 py-5 md:px-8 md:py-6">
        <h2 class="text-lg md:text-xl font-bold text-[#828cff] mb-1">
          Track your Document
        </h2>
        <p class="text-gray-300 text-xs md:text-sm">
          Enter the tracking number of your request.
        </p>
      </div>

      <!-- Form Body -->
      <div class="p-6 md:p-8 flex flex-col gap-2">
        <label class="text-sm font-semibold text-[#121722]">
          Tracking Number <span class="text-red-500">*</span>
        </label>
        
        <div class="flex flex-col md:flex-row gap-3 mt-1">
          <!-- Input Field -->
          <input 
            type="text" 
            placeholder="e.g. LAO-26-6767" 
            class="flex-1 h-[50px] px-4 bg-white border border-gray-200 rounded-xl outline-none focus:border-[#828cff] focus:ring-2 focus:ring-[#828cff]/20 text-[#334155] font-medium placeholder-gray-400 transition-all text-sm"
          />

          <!-- Track Button -->
          <button class="h-[50px] px-8 bg-[#6b77ff] hover:bg-[#828cff] text-white font-bold text-sm tracking-wider rounded-xl transition-all shadow-sm hover:shadow-md hover:-translate-y-[1px] flex items-center justify-center whitespace-nowrap">
            TRACK
          </button>
        </div>
      </div>

    </div>

    <!-- Result / Status Box -->
    <div class="w-full max-w-3xl bg-white rounded-2xl shadow-[0_4px_20px_rgba(0,0,0,0.04)] border border-gray-100 py-5 px-6 text-center text-gray-400 text-sm font-medium">
      No document found
    </div>

  </main>
</template>

<script setup>
</script>