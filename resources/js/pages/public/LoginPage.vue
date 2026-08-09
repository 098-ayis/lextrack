<template>
     <div class="w-full flex items-center justify-center">

        <div
            class="w-full max-w-[530px] bg-white rounded-2xl
            shadow-[0_4px_5px_rgba(0,0,0,0.25)]
            px-12 py-8"
        >

            <a
                href="/"
                class="flex items-center gap-3 text-gray-500
                text-[16px] hover:text-gray-700 transition mb-12"
            >
            <Icon
                icon="lucide:arrow-left"
                class="w-5 h-5"
            />

            <span>Back to Home</span>
            </a>

            <div class="flex items-center gap-4 mb-6 text-[#f58220]">
                 <Icon
                    icon="lucide:graduation-cap"
                    class="w-10 h-10"
                />

                <div>
                    <h2 class="text-[17px] font-bold text-gray-900">
                        Faculty, BU Personnel & Students
                    </h2>

                    <p class="text-[16px] text-gray-400">
                        Use your BU email to sign in using the button below.
                    </p>
                </div>
            </div>
    
            <a 
                href="/auth/google"
                class="w-full h-[47px] border-2 border-[#ff7625]
                rounded-xl flex items-center justify-center gap-3
                text-[#f47721] text-[16px] font-medium
                hover:bg-[#fff5ee] transition"
            >
                <Icon
                    icon="logos:google-icon"
                    class="w-6 h-6"
                />
                Continue with Google
            </a>

            <p class="text-center text-[15px] text-gray-400 mt-2">
                Not registered?
                <a
                    href="/track"
                    class="hover:text-gray-600 underline"
                >
                    Click here to track your documents.
                </a>
            </p>

        </div>
    </div>
</template>

<script setup>
import { Icon } from '@iconify/vue'

</script>

<style>

</style>