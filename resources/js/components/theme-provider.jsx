import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import {
    applyTheme,
    readStoredTheme,
    resolveTheme,
    THEME_VALUES,
    writeStoredTheme,
} from "@/lib/theme";

const ThemeContext = createContext(null);

export { ThemeContext };

/** @deprecated En Inertia usar InertiaThemeProvider. Mantenido para tests/herramientas. */
export function ThemeProvider({
    children,
    defaultTheme = "system",
    storageKey,
    onThemeChange = null,
}) {
    const [theme, setThemeState] = useState(() => readStoredTheme() ?? defaultTheme);

    useEffect(() => {
        applyTheme(theme);
    }, [theme]);

    useEffect(() => {
        if (theme !== "system") return undefined;

        const media = window.matchMedia("(prefers-color-scheme: dark)");
        const handler = () => applyTheme("system");
        media.addEventListener("change", handler);
        return () => media.removeEventListener("change", handler);
    }, [theme]);

    const setTheme = useCallback(
        (newTheme, options = { persist: true }) => {
            if (!THEME_VALUES.includes(newTheme)) return;

            writeStoredTheme(newTheme);
            setThemeState(newTheme);

            if (options.persist !== false && onThemeChange) {
                onThemeChange(newTheme);
            }
        },
        [onThemeChange]
    );

    const resolvedTheme = useMemo(() => resolveTheme(theme), [theme]);

    const value = useMemo(
        () => ({
            theme,
            resolvedTheme,
            setTheme,
            themes: THEME_VALUES,
            isDark: resolvedTheme === "dark",
        }),
        [theme, resolvedTheme, setTheme]
    );

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useThemeContext() {
    const ctx = useContext(ThemeContext);
    if (!ctx) {
        throw new Error("useThemeContext must be used within a ThemeProvider");
    }
    return ctx;
}

export { useThemeContext as useTheme };
