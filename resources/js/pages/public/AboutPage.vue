<template>
  <main class="w-full flex flex-col">
    
    <!-- ==========================================
         SECTION 1: ORGANIZATIONAL CHART
    =========================================== -->
    <section class="w-full bg-white pb-12 pt-[100px] flex flex-col items-center">
      
      <h2 class="mt-12 text-3xl font-bold text-[#121722] mb-12 tracking-wide">
        Organizational Chart
      </h2>

      <!-- Chart Container -->
      <div class="flex flex-col items-center w-full">
        
        <!-- Top Level Node -->
        <div class="flex flex-col items-center z-10">
          <!-- CHANGED: Replaced -mb-3 with mb-3 to add space! -->
          <div class="w-[76px] h-[76px] rounded-full bg-gray-200 border-2 border-gray-500 flex items-center justify-center z-10 mb-3">
            <Icon icon="ph:user" class="text-[40px] text-gray-500" />
          </div>
          <div class="w-[300px] h-[40px] bg-[#eef2ff] border border-[#828cff] rounded-full flex items-center justify-center">
            <!-- Add Name/Title Here -->
          </div>
        </div>

        <!-- Connecting Lines Structure -->
        <div class="flex flex-col items-center w-full">
          <!-- Main Vertical Drop -->
          <div class="w-[2px] h-[24px] bg-gray-500"></div>
          
          <!-- Horizontal Branch -->
          <div class="w-[340px] border-t-2 border-gray-500 flex justify-between h-[24px]">
            <!-- Left Child Drop -->
            <div class="w-[2px] h-full bg-gray-500"></div>
            <!-- Right Child Drop -->
            <div class="w-[2px] h-full bg-gray-500"></div>
          </div>
        </div>

        <!-- Second Level Nodes -->
        <div class="flex justify-center gap-[60px] w-full max-w-[700px]">
          
          <!-- Left Node -->
          <div class="flex flex-col items-center w-[280px]">
            <!-- CHANGED: Replaced -mb-3 with mb-3 to add space! -->
            <div class="w-[76px] h-[76px] rounded-full bg-gray-200 border-2 border-gray-500 flex items-center justify-center z-10 mb-3">
              <Icon icon="ph:user" class="text-[40px] text-gray-500" />
            </div>
            <div class="w-full h-[40px] bg-[#eef2ff] border border-[#828cff] rounded-full flex items-center justify-center">
               <!-- Add Name/Title Here -->
            </div>
          </div>

          <!-- Right Node -->
          <div class="flex flex-col items-center w-[280px]">
            <!-- CHANGED: Replaced -mb-3 with mb-3 to add space! -->
            <div class="w-[76px] h-[76px] rounded-full bg-gray-200 border-2 border-gray-500 flex items-center justify-center z-10 mb-3">
              <Icon icon="ph:user" class="text-[40px] text-gray-500" />
            </div>
            <div class="w-full h-[40px] bg-[#eef2ff] border border-[#828cff] rounded-full flex items-center justify-center">
               <!-- Add Name/Title Here -->
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- ==========================================
         SECTION 2: OUR FUNCTIONS
    =========================================== -->
    <section class="w-full bg-[#eef2ff] py-16 px-6 md:px-12 flex flex-col items-center">
      <h2 class="text-3xl font-bold text-[#121722] mb-6 tracking-wide">
        Our Functions
      </h2>
      
      <div class="max-w-3xl w-full text-center text-[#121722] mb-8 leading-relaxed font-medium">
        <p>
          The Legal Affairs Office shall have the main responsibility of ensuring that the
          University is legally represented and provided with legal advice and counseling, and of seeing to
          it that all contracts, deeds and conveyances, and legal documents are in order.
        </p>
      </div>

      <div class="max-w-4xl w-full text-left text-[#121722] leading-relaxed">
        <p class="mb-4 font-medium">
          The Office shall have the following duties and responsibilities:
        </p>
        
        <ul class="space-y-1 pl-1">
          <li>(a) Litigate all court cases where the interest of the University is being challenged or when the University is a party to a law suit;</li>
          <li>(b) Coordinate and work closely with the Office of the Solicitor-General for university cases;</li>
          <li>(c) Represent the University on legal matters;</li>
          <li>(d) Provide legal advice and counseling for the University;</li>
          <li>(e) Assist in any administrative investigation of the University;</li>
          <li>(f) Formulate, review, and keep records of all legal documents concerning the University; and</li>
          <li>(g) Perform other duties and responsibilities as may be assigned by the University President.</li>
        </ul>
      </div>
    </section>

  </main>
</template>

<script setup>
import { Icon } from '@iconify/vue'
</script>