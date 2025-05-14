import { createContext, useContext, useEffect, useState } from "react";

export type Theme = "dark" | "light" | "system";

type ThemeProviderProps = {
    children: React.ReactNode;
    defaultTheme?: Theme;
    storageKey?: string;
};

type ThemeProviderState = {
    theme: Theme;
    setTheme: (theme: Theme) => void;
};

const initialState: ThemeProviderState = {
    theme: "system",
    setTheme: () => null,
};

const ThemeProviderContext = createContext<ThemeProviderState>(initialState);

function setCookie(name: string, value: string, days = 365) {
    const date = new Date();
    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
    const expires = `; expires=${date.toUTCString()}`;
    document.cookie = `${name}=${value}${expires}; path=/; SameSite=Lax`;
}

function getCookie(name: string): string | null {
    const cookieIdentifier = `${name}=`;
    const cookies = document.cookie.split(";");

    for (let i = 0; i < cookies.length; i++) {
        let currentCookie = cookies[i];
        while (currentCookie.charAt(0) === " ") {
            currentCookie = currentCookie.substring(1, currentCookie.length);
        }

        if (currentCookie.indexOf(cookieIdentifier) === 0) {
            return currentCookie.substring(
                cookieIdentifier.length,
                currentCookie.length,
            );
        }
    }

    return null;
}

function getInitialState(defaultTheme: Theme): Theme {
    if (typeof window === "undefined") {
        return defaultTheme;
    }

    const cookieTheme = getCookie("appearance");

    if (cookieTheme) {
        return cookieTheme as Theme;
    }

    return defaultTheme;
}

export function ThemeProvider({
    children,
    defaultTheme = "system",
    storageKey = "vite-ui-theme",
    ...props
}: ThemeProviderProps) {
    const [theme, setTheme] = useState<Theme>(() =>
        getInitialState(defaultTheme),
    );

    useEffect(() => {
        const root = window.document.documentElement;

        root.classList.remove("light", "dark");

        if (theme === "system") {
            const systemTheme = window.matchMedia(
                "(prefers-color-scheme: dark)",
            ).matches
                ? "dark"
                : "light";

            root.classList.add(systemTheme);
            return;
        }

        root.classList.add(theme);
    }, [theme]);

    const value = {
        theme,
        setTheme: (theme: Theme) => {
            setCookie("appearance", theme);
            setTheme(theme);
        },
    };

    return (
        <ThemeProviderContext.Provider {...props} value={value}>
            {children}
        </ThemeProviderContext.Provider>
    );
}

export function useTheme() {
    const context = useContext(ThemeProviderContext);

    if (context === undefined) {
        throw new Error("useTheme must be used within a theme provider.");
    }

    return context;
}
