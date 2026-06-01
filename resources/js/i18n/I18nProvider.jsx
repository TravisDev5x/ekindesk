import React, { createContext, useCallback, useContext, useMemo, useState } from "react";
import { useTheme } from "@/hooks/useTheme";
import messages from "./messages";

export const I18nContext = createContext({
    locale: "es",
    setLocale: () => {},
    t: (key) => key,
});

const interpolate = (text, vars = {}) =>
    text.replace(/\{(\w+)\}/g, (_, k) => (vars[k] !== undefined ? vars[k] : `{${k}}`));

function buildI18nValue(locale, setLocale) {
    return {
        locale,
        setLocale,
        t: (key, vars) => {
            const entry = messages[locale]?.[key] ?? messages.es?.[key] ?? key;
            if (typeof entry === "function") return entry(vars || {});
            if (typeof entry === "string" && vars) return interpolate(entry, vars);
            return entry;
        },
    };
}

/** Locale sincronizado con useTheme / API (legacy export). */
export function I18nProvider({ children }) {
    const { locale, setLocale } = useTheme();
    const value = useMemo(() => buildI18nValue(locale, setLocale), [locale, setLocale]);
    return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>;
}

/** Inertia: sin ThemeProvider; locale desde localStorage. */
export function InertiaI18nProvider({ children }) {
    const [locale, setLocaleState] = useState(() => {
        if (typeof window === "undefined") return "es";
        return localStorage.getItem("locale") || "es";
    });

    const setLocale = useCallback((next) => {
        setLocaleState(next);
        if (typeof window !== "undefined") {
            localStorage.setItem("locale", next);
            document.documentElement.lang = next;
        }
    }, []);

    const value = useMemo(() => buildI18nValue(locale, setLocale), [locale, setLocale]);
    return <I18nContext.Provider value={value}>{children}</I18nContext.Provider>;
}

export const useI18n = () => useContext(I18nContext);
