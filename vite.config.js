import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import inertia from '@inertiajs/vite';

// Sub-path deploy (mis. "/starterkit"), diisi lewat build arg ASSET_URL.
// Kosong = aplikasi di root domain, perilaku bawaan plugin.
const assetUrl = (process.env.ASSET_URL || '').replace(/\/+$/, '');

export default defineConfig(({ command }) => ({
    base: command === 'build' && assetUrl ? `${assetUrl}/build/` : undefined,
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
        vue(),
        inertia({ ssr: false }),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
}));