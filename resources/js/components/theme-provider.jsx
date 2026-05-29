import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

const STORAGE_KEY = 'ekindesk_theme';
const LEGACY_STORAGE_KEY = 'theme';
const VALID_THEMES = ['light', 'dark', 'system'];

const ThemeContext = createContext(null);

export { ThemeContext };

function getSystemTheme() {
    if (typeof window === 'undefined') return 'light';
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function resolveTheme(theme) {
    if (theme === 'system') return getSystemTheme();
    if (VALID_THEMES.includes(theme)) return theme;
    return getSystemTheme();
}

function applyTheme(resolved) {
    const root = document.documentElement;
    root.classList.remove('light', 'dark');
    if (resolved === 'dark') {
        root.classList.add('dark');
        root.style.colorScheme = 'dark';
    } else {
        root.style.colorScheme = 'light';
    }
}

function readStoredTheme(storageKey) {
    try {
        const stored = localStorage.getItem(storageKey) ?? localStorage.getItem(LEGACY_STORAGE_KEY);
        if (stored && VALID_THEMES.includes(stored)) {
            if (storageKey !== LEGACY_STORAGE_KEY && localStorage.getItem(LEGACY_STORAGE_KEY)) {
                localStorage.setItem(storageKey, stored);
                localStorage.removeItem(LEGACY_STORAGE_KEY);
            }
            return stored;
        }
    } catch {
        // ignore
    }
    return null;
}

export function ThemeProvider({
    children,
    defaultTheme = 'system',
    storageKey = STORAGE_KEY,
    onThemeChange = null,
}) {
    const [theme, setThemeState] = useState(() => readStoredTheme(storageKey) ?? defaultTheme);

    useEffect(() => {
        applyTheme(resolveTheme(theme));
    }, [theme]);

    useEffect(() => {
        if (theme !== 'system') return undefined;

        const media = window.matchMedia('(prefers-color-scheme: dark)');
        const handler = () => applyTheme(resolveTheme('system'));
        media.addEventListener('change', handler);
        return () => media.removeEventListener('change', handler);
    }, [theme]);

    const setTheme = useCallback(
        (newTheme, options = { persist: true }) => {
            if (!VALID_THEMES.includes(newTheme)) return;

            try {
                localStorage.setItem(storageKey, newTheme);
            } catch {
                // ignore
            }

            setThemeState(newTheme);

            if (options.persist !== false && onThemeChange) {
                onThemeChange(newTheme);
            }
        },
        [storageKey, onThemeChange]
    );

    const resolvedTheme = useMemo(() => resolveTheme(theme), [theme]);

    const value = useMemo(
        () => ({
            theme,
            resolvedTheme,
            setTheme,
            themes: VALID_THEMES,
            isDark: resolvedTheme === 'dark',
        }),
        [theme, resolvedTheme, setTheme]
    );

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useThemeContext() {
    const ctx = useContext(ThemeContext);
    if (!ctx) {
        throw new Error('useThemeContext must be used within ThemeProvider');
    }
    return ctx;
}

export { useThemeContext as useTheme };
