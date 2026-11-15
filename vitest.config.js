import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
            '@tests': fileURLToPath(new URL('./tests/js', import.meta.url)),
        },
    },
    test: {
        environment: 'happy-dom',
        include: ['tests/js/**/*.test.js'],
        setupFiles: ['tests/js/setup.js'],
        restoreMocks: true,
        coverage: {
            provider: 'v8',
            reporter: ['text', 'lcov'],
            reportsDirectory: 'coverage',
            include: ['resources/js/**/*.{js,vue}'],
            // Entry point Inertia: dijalankan sekali saat aplikasi boot, bukan
            // unit yang bisa diuji tanpa memasang seluruh aplikasi.
            exclude: ['resources/js/app.js'],
        },
    },
});
