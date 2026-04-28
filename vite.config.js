import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

//const host = "app.ailumnus.local";
export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                //'resources/js/tours/tour.js',
            ],
            refresh: true,
        }),
    ],
   server: {
    host: '0.0.0.0', // 👈 FORÇA binding externo real
    port: 5173,
    strictPort: true,
    hmr: {
        host: 'localhost',
        protocol: 'ws',
    },
    watch: {
        usePolling: true,
    },
}

});
