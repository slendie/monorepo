import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

// O laravel-vite-plugin escreve public/hot (dev) e public/build/manifest.json (build), que a Slendie lê em @vite.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/admin/main.tsx'],
            refresh: ['resources/views/**'],
        }),
        react(),
        tailwindcss(),
    ],
    test: {
        environment: 'node',
        include: ['resources/admin/**/*.test.{ts,tsx}'],
    },
});
