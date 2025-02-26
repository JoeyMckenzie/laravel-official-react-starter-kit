import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { AppearanceProvider } from '@/providers/appearance-provider';
import { Appearance, getAppearanceFromStorage, initializeTheme } from './hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Initialize theme immediately to prevent flash
initializeTheme();

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
    setup({ el, App, props }) {
        const root = createRoot(el);

        // Get initial appearance from Inertia props if available, otherwise from storage
        const initialAppearance = props.initialPage.props.appearance as Appearance ?? getAppearanceFromStorage();

        root.render(
            <AppearanceProvider initialAppearance={initialAppearance}>
                <App {...props} />
            </AppearanceProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});
