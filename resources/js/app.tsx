import '../css/app.css';

import { AppearanceProvider } from '@/hooks/use-appearance';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot, hydrateRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const Provider = () => (
            <AppearanceProvider defaultAppearance="system" storageKey="inertia-ui-theme">
                <App {...props} />
            </AppearanceProvider>
        );

        if (import.meta.env.SSR) {
            hydrateRoot(el, <Provider />);
        } else {
            createRoot(el).render(<Provider />);
        }
    },
    progress: {
        color: '#4B5563',
    },
});
