<template>
  <main
    class="w-full flex flex-col items-center min-h-[80vh] pt-36 pb-16 px-6 bg-[#f4f5f7]"
  >
    <!-- Main Tracking Card -->
    <div
      class="w-full max-w-3xl bg-white rounded-3xl shadow-[0_10px_40px_rgba(0,0,0,0.08)]
             border border-gray-100 overflow-hidden mb-6"
    >
      <!-- Header -->
      <div class="bg-[#121722] px-6 py-5 md:px-8 md:py-6">
        <h2 class="text-lg md:text-xl font-bold text-[#828cff] mb-1">
          Track your Document
        </h2>

        <p class="text-gray-300 text-xs md:text-sm">
          Enter the tracking number of your request.
        </p>
      </div>

      <!-- Form -->
      <form
        class="p-6 md:p-8 flex flex-col gap-2"
        @submit.prevent="trackDocument"
      > 
        <label class="text-sm font-semibold text-[#121722]">
          Tracking Number
          <span class="text-red-500">*</span>
        </label>

        <div class="flex flex-col md:flex-row gap-3 mt-1">
          <!-- Input -->
          <div class="relative flex-1">
            <input
              v-model="trackingNumber"
              type="text"
              placeholder="e.g. LAO-26-6767"
              class="w-full h-[50px] px-4 pr-10 bg-white border border-gray-200
                     rounded-xl outline-none focus:border-[#828cff]
                     focus:ring-2 focus:ring-[#828cff]/20
                     text-[#334155] font-medium placeholder-gray-400
                     transition-all text-sm"
            />

            <!-- Clear button -->
            <button
              v-if="trackingNumber"
              type="button"
              @click="clearTracking"
              class="absolute right-4 top-1/2 -translate-y-1/2
                     text-gray-400 hover:text-gray-700 text-xl"
            >
              &times;
            </button>
          </div>

          <!-- Track Button -->
          <button
            type="submit"
            :disabled="loading || !trackingNumber.trim()"
            class="h-[50px] px-8 bg-[#6b77ff] hover:bg-[#828cff]
                   disabled:opacity-50 disabled:cursor-not-allowed
                   text-white font-bold text-sm tracking-wider
                   rounded-xl transition-all shadow-sm
                   hover:shadow-md hover:-translate-y-[1px]
                   flex items-center justify-center whitespace-nowrap"
          >
            {{ loading ? 'TRACKING...' : 'TRACK' }}
          </button>
        </div>
      </form>
    </div>

    <template v-if="hasSearched">

      <!-- Document Found -->
      <div
        v-if="document"
        class="w-full max-w-3xl bg-white rounded-2xl
               shadow-[0_4px_20px_rgba(0,0,0,0.04)]
               border border-gray-200 overflow-hidden"
      >
        <!-- Desktop Table -->
        <div class="hidden md:block">
          <table class="w-full">
            <thead class="bg-[#164f78] text-white">
              <tr>
                <th class="px-6 py-3 text-left text-sm font-bold">
                  LAO #
                </th>

                <th class="px-6 py-3 text-left text-sm font-bold">
                  PARTICULARS
                </th>

                <th class="px-6 py-3 text-left text-sm font-bold">
                  DATE SUBMITTED
                </th>

                <th class="px-6 py-3 text-left text-sm font-bold">
                  STATUS
                </th>
              </tr>
            </thead>

            <tbody>
              <tr>
                <td class="px-6 py-4 font-semibold text-sm">
                  {{ document.tracking_number }}
                </td>

                <td class="px-6 py-4 font-semibold text-sm">
                  {{ document.particulars }}
                </td>

                <td class="px-6 py-4 font-semibold text-sm">
                  {{ document.date_submitted }}
                </td>

                <td class="px-6 py-4">
                  <span
                    class="inline-flex px-4 py-1 rounded-full
                           bg-green-100 border border-green-500
                           text-green-800 font-semibold text-sm"
                  >
                    {{ document.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile -->
        <div class="md:hidden p-5 space-y-4">
          <div>
            <p class="text-xs text-gray-400 font-semibold">LAO #</p>
            <p class="font-semibold">
              {{ document.tracking_number }}
            </p>
          </div>

          <div>
            <p class="text-xs text-gray-400 font-semibold">
              PARTICULARS
            </p>
            <p class="font-semibold">
              {{ document.particulars }}
            </p>
          </div>

          <div>
            <p class="text-xs text-gray-400 font-semibold">
              DATE SUBMITTED
            </p>
            <p class="font-semibold">
              {{ document.date_submitted }}
            </p>
          </div>

          <div>
            <p class="text-xs text-gray-400 font-semibold mb-1">
              STATUS
            </p>

            <span
              class="inline-flex px-4 py-1 rounded-full
                     bg-green-100 border border-green-500
                     text-green-800 font-semibold text-sm"
            >
              {{ document.status }}
            </span>
          </div>
        </div>
      </div>

      <!-- No Document Found -->
      <div
        v-else
        class="w-full max-w-3xl bg-white rounded-2xl
               shadow-[0_4px_20px_rgba(0,0,0,0.04)]
               border border-gray-100 py-5 px-6
               text-center text-gray-400 text-sm font-medium"
      >
        No document found
      </div>

    </template>
  </main>
</template>

<script setup>
import { ref } from 'vue'

const trackingNumber = ref('')
const hasSearched = ref(false)
const loading = ref(false)
const document = ref(null)

const trackDocument = async () => {
  if (!trackingNumber.value.trim()) return

  loading.value = true

  // Reset previous result
  document.value = null

  try {
    /*
     * Replace this section with your actual API request.
     *
     * Example:
     *
     * const response = await axios.get(
     *   `/api/track/${trackingNumber.value}`
     * )
     *
     * document.value = response.data.document
     */

    if (trackingNumber.value.toUpperCase() === 'LAO-26-6767') {
      document.value = {
        tracking_number: 'LAO-26-6767',
        particulars: 'OJT MOA',
        date_submitted: 'June 20, 2026',
        status: 'Completed',
      }
    } else {
      document.value = null
    }
  } catch (error) {
    console.error('Tracking error:', error)

    document.value = null
  } finally {
    loading.value = false
    hasSearched.value = true
  }
}

const clearTracking = () => {
  trackingNumber.value = ''
  document.value = null
  hasSearched.value = false
}
</script>