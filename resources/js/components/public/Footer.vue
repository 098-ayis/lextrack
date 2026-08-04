<template>
    <footer id="forms" class="bg-[#142c47] text-gray-300 pt-11 px-6 pb-7">
        <div class="max-w-7xl mx-auto grid md:grid-cols-[2fr_1fr] gap-10">

            <!-- Left Section -->
            <div>

                <!-- Logo -->
                <div class="flex items-center gap-3 mb-4">

                    <div
                        class="w-10 h-10 rounded-full bg-white text-[#142c47] flex items-center justify-center font-bold">
                        BU
                    </div>

                    <div>
                        <small class="block text-xs uppercase tracking-wider text-gray-400">
                            Bicol
                            <span class="text-orange-400">University</span>
                        </small>

                        <h2 class="text-white font-bold text-lg">
                            Legal Office
                        </h2>
                    </div>

                </div>

                <!-- Description -->
                <p class="text-sm text-gray-400 max-w-md mb-5">
                    The Bicol University Legal Affairs Office delivers legal
                    services, including advice, representation, and document
                    review, to protect the university's interests and ensure
                    compliance with laws and policies.
                </p>

                <!-- Contact -->
                <ul class="space-y-2 text-sm text-gray-400">

                    <li>
                        📧 bu-legaloffice@bicol-u.edu.ph
                    </li>

                    <li>
                        📍 Bicol University Main Campus, Legazpi, Philippines
                    </li>

                    <li>
                        📞 (0903) 467-0961
                    </li>

                </ul>

            </div>

            <!-- Quick Links -->
            <div>

                <h3 class="text-white font-semibold mb-4">
                    Quick Links
                </h3>

                <ul class="space-y-2 text-sm">

                    <li>
                        <RouterLink
                            to="/"
                            class="hover:text-orange-400 transition">
                            Home
                        </RouterLink>
                    </li>

                    <li>
                        <RouterLink
                            to="/about"
                            class="hover:text-orange-400 transition">
                            About
                        </RouterLink>
                    </li>

                    <li>
                        <RouterLink
                            to="/track"
                            class="hover:text-orange-400 transition">
                            Track
                        </RouterLink>
                    </li>

                    <li>
                        <RouterLink
                            to="/forms"
                            class="hover:text-orange-400 transition">
                            Legal Forms
                        </RouterLink>
                    </li>

                </ul>

            </div>

        </div>

        <!-- Bottom -->
        <div
            class="max-w-7xl mx-auto border-t border-white/10 mt-8 pt-5 text-center text-xs text-gray-500">

            &copy; 2026 Bicol University Legal Affairs Office. All rights
            reserved.

        </div>
    </footer>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>