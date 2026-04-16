import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: ['resources/js/app.ts', 'resources/css/inertia.css'],
            buildDirectory: 'build',
            publicDirectory: 'public',
        }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: ['**/vendor/**'],
        },
    },
})
