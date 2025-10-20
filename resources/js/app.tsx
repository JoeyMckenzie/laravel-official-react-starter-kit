import '../css/app.css';

import { ThemeProvider } from '@/components/theme-provider';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

await createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.tsx`,
            import.meta.glob('./pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        const app = (
            <StrictMode>
                <ThemeProvider defaultTheme="system" storageKey="vite-ui-theme">
                    <App {...props} />
                </ThemeProvider>
            </StrictMode>
        );

        root.render(app);
    },
    progress: {
        color: '#4B5563',
    },
});
