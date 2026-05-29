import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import axios from "@/lib/axios";
import { ThemeContext } from "@/components/theme-provider";

const STORAGE_KEY = "ekindesk_theme";
const VALID = ["light", "dark", "system"];

function getSystemTheme() {
    if (typeof window === "undefined") return "light";
    return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
}

function resolveTheme(theme) {
    if (theme === "system" || !VALID.includes(theme)) return getSystemTheme();
    return theme;
}

function applyTheme(theme) {
    const resolved = resolveTheme(theme);
    const root = document.documentElement;
    root.classList.remove("light", "dark");
    if (resolved === "dark") {
        root.classList.add("dark");
        root.style.colorScheme = "dark";
    } else {
        root.style.colorScheme = "light";
    }
}

function readStoredTheme() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored && VALID.includes(stored)) return stored;
    } catch {
        // ignore
    }
    return "system";
}

/** Provee el mismo ThemeContext que la SPA para páginas Inertia. */
export function InertiaThemeProvider({ children }) {
    const [theme, setThemeState] = useState(readStoredTheme);
    const mediaRef = useRef(null);

    useEffect(() => {
        applyTheme(theme);
    }, [theme]);

    useEffect(() => {
        if (theme !== "system") {
            if (mediaRef.current) {
                const { media, handler } = mediaRef.current;
                media.removeEventListener("change", handler);
                mediaRef.current = null;
            }
            return undefined;
        }

        const media = window.matchMedia("(prefers-color-scheme: dark)");
        const handler = () => applyTheme("system");
        media.addEventListener("change", handler);
        mediaRef.current = { media, handler };

        return () => {
            media.removeEventListener("change", handler);
            mediaRef.current = null;
        };
    }, [theme]);

    const setTheme = useCallback((newTheme) => {
        if (!VALID.includes(newTheme)) return;

        try {
            localStorage.setItem(STORAGE_KEY, newTheme);
        } catch {
            // ignore
        }

        setThemeState(newTheme);

        axios.put("/api/profile/theme", { theme: newTheme }).catch(() => {});
    }, []);

    const resolvedTheme = useMemo(() => resolveTheme(theme), [theme]);

    const value = useMemo(
        () => ({
            theme,
            resolvedTheme,
            setTheme,
            themes: VALID,
            isDark: resolvedTheme === "dark",
        }),
        [theme, resolvedTheme, setTheme]
    );

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}
