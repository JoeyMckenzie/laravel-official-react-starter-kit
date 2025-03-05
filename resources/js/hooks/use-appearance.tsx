import { createContext, useContext, useEffect, useState } from 'react';

export type Appearance = 'dark' | 'light' | 'system';

type AppearanceProviderProps = {
    children: React.ReactNode;
    defaultAppearance?: Appearance;
    storageKey?: string;
};

type AppearanceProviderState = {
    appearance: Appearance;
    setAppearance: (appearance: Appearance) => void;
};

const initialState: AppearanceProviderState = {
    appearance: 'system',
    setAppearance: () => null,
};

const AppearanceProviderContext = createContext<AppearanceProviderState>(initialState);

/**
 * Sets a cookie with the given name and value
 */
const setCookie = (name: string, value: string, days = 365) => {
    const date = new Date();
    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
    const expires = `; expires=${date.toUTCString()}`;
    document.cookie = `${name}=${value}${expires}; path=/; SameSite=Lax`;
};

const getCookie = (name: string): string | null => {
    const cookieIdentifier = name + '=';
    const cookies = document.cookie.split(';');

    for (let i = 0; i < cookies.length; i++) {
        let currentCookie = cookies[i];
        while (currentCookie.charAt(0) === ' ') {
            currentCookie = currentCookie.substring(1, currentCookie.length);
        }

        if (currentCookie.indexOf(cookieIdentifier) === 0) {
            return currentCookie.substring(cookieIdentifier.length, currentCookie.length);
        }
    }

    return null;
};

export function AppearanceProvider({ children, defaultAppearance = 'system', storageKey = 'inertia-ui-theme', ...props }: AppearanceProviderProps) {
    const [appearance, setAppearance] = useState<Appearance>(() => {
        if (typeof window === 'undefined') {
            return defaultAppearance;
        }

        // First check localStorage (user's explicit preference)
        const storedAppearance = localStorage.getItem(storageKey) as Appearance;
        if (storedAppearance) {
            return storedAppearance;
        }

        // If no localStorage value, check if we have a cookie from previous sessions
        const cookieTheme = getCookie('appearance');
        if (cookieTheme === 'dark' || cookieTheme === 'light') {
            // If there's a theme cookie but no localStorage value,
            // we'll use the cookie value but set as explicit preference
            localStorage.setItem(storageKey, cookieTheme);
            return cookieTheme;
        }

        // Fall back to default
        return defaultAppearance;
    });

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        localStorage.setItem(storageKey, appearance);
        setAppearance(appearance);
    }, [appearance, storageKey]);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        const root = window.document.documentElement;

        if (appearance === 'system') {
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            root.classList.add(systemTheme);
            setCookie('theme', systemTheme);
            return;
        }

        root.classList.add(appearance);
        setCookie('theme', appearance);
    }, [appearance]);

    const value = {
        appearance,
        setAppearance,
    };

    return (
        <AppearanceProviderContext.Provider {...props} value={value}>
            {children}
        </AppearanceProviderContext.Provider>
    );
}

export const useAppearance = () => {
    const context = useContext(AppearanceProviderContext);

    if (context === undefined) {
        throw new Error('useAppearance must be used within an AppearanceProvider');
    }

    return context;
};
