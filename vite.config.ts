import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

const viteDevServerUrl = process.env.VITE_DEV_SERVER_URL;
const appUrl = process.env.APP_URL;

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
    server: {
        host: process.env.VITE_HOST ?? '127.0.0.1',
        port: Number(process.env.VITE_PORT ?? 5173),
        strictPort: true,
        // laravel-vite-plugin writes this URL to public/hot, so the Blade
        // page emits asset/HMR script tags pointing at the caddy-proxied
        // HTTPS endpoint instead of http://127.0.0.1:5173.
        origin: viteDevServerUrl,
        cors: appUrl
            ? { origin: [appUrl, `https://horizon.${new URL(appUrl).host}`] }
            : undefined,
        hmr: viteDevServerUrl
            ? {
                  host: new URL(viteDevServerUrl).host,
                  protocol: 'wss',
                  clientPort: 443,
              }
            : undefined,
    },
});
