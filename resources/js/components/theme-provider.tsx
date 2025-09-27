import { createContext, useContext, useEffect, useState } from 'react';

export type Theme = 'dark' | 'light' | 'system';

interface ThemeProviderProps {
    children: React.ReactNode;
    defaultTheme?: Theme;
    storageKey?: string;
}

interface ThemeProviderState {
    theme: Theme;
    setTheme: (theme: Theme) => void;
}

const initialState: ThemeProviderState = {
    theme: 'system',
    setTheme: () => null,
};

const ThemeProviderContext = createContext<ThemeProviderState | undefined>(
    initialState,
);

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge.toString()};SameSite=Lax`;
};

const getCookieValue = (name: string): string | null => {
    if (typeof document === 'undefined') {
        return null;
    }

    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) {
        return parts.pop()?.split(';').shift() ?? null;
    }
    return null;
};

const getStoredTheme = (storageKey: string, defaultTheme: Theme): Theme => {
    if (typeof window === 'undefined') {
        // For SSR, try to get theme from cookie
        const cookieTheme = getCookieValue(storageKey) as Theme | null;
        return cookieTheme ?? defaultTheme;
    }

    // Client-side: prefer localStorage, fallback to cookie, then default
    const localStorageTheme = localStorage.getItem(storageKey) as Theme | null;
    const cookieTheme = getCookieValue(storageKey) as Theme | null;

    return localStorageTheme ?? cookieTheme ?? defaultTheme;
};

const applyTheme = (theme: Theme) => {
    if (typeof window === 'undefined') {
        return;
    }

    const root = window.document.documentElement;
    root.classList.remove('light', 'dark');

    if (theme === 'system') {
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)')
            .matches
            ? 'dark'
            : 'light';

        root.classList.add(systemTheme);
        root.style.colorScheme = systemTheme;
        return;
    }

    root.classList.add(theme);
    root.style.colorScheme = theme;
};

export function ThemeProvider({
    children,
    defaultTheme = 'system',
    storageKey = 'vite-ui-theme',
    ...props
}: ThemeProviderProps) {
    const [theme, setTheme] = useState<Theme>(() =>
        getStoredTheme(storageKey, defaultTheme),
    );

    useEffect(() => {
        applyTheme(theme);
    }, [theme]);

    useEffect(() => {
        const handleSystemThemeChange = () => {
            if (theme === 'system') {
                applyTheme(theme);
            }
        };

        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        mediaQuery.addEventListener('change', handleSystemThemeChange);

        return () => {
            mediaQuery.removeEventListener('change', handleSystemThemeChange);
        };
    }, [theme]);

    const value = {
        theme,
        setTheme: (newTheme: Theme) => {
            setTheme(newTheme);

            if (typeof window !== 'undefined') {
                localStorage.setItem(storageKey, newTheme);
            }

            setCookie(storageKey, newTheme);
        },
    };

    return (
        <ThemeProviderContext.Provider {...props} value={value}>
            {children}
        </ThemeProviderContext.Provider>
    );
}

export const useTheme = () => {
    const context = useContext(ThemeProviderContext);

    if (context === undefined)
        throw new Error('useTheme must be used within a ThemeProvider');

    return context;
};

export function initializeTheme(
    storageKey = 'vite-ui-theme',
    defaultTheme: Theme = 'system',
) {
    if (typeof window === 'undefined') {
        return;
    }

    const theme = getStoredTheme(storageKey, defaultTheme);
    applyTheme(theme);

    const handleSystemThemeChange = () => {
        const currentTheme = getStoredTheme(storageKey, defaultTheme);
        if (currentTheme === 'system') {
            applyTheme(currentTheme);
        }
    };

    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    mediaQuery.addEventListener('change', handleSystemThemeChange);

    return () => {
        mediaQuery.removeEventListener('change', handleSystemThemeChange);
    };
}
