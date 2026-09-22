import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/public.js', 'resources/js/client-chatbot.js', 'resources/js/docx-preview.js', 'resources/css/filament/admin/theme.css'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        vue(),
        tailwindcss(),
    ],
    server: {
    host: '0.0.0.0',
    hmr: {
        host: '172.27.212.89'
    },
    watch: {
        ignored: ['**/storage/framework/views/**'],
    },
},
});
