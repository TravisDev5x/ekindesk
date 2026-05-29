import { useCallback, useEffect, useState } from 'react';
import axios from '@/lib/axios';
import { useAuth } from '@/context/AuthContext';
import { useThemeContext } from '@/components/theme-provider';

const STORAGE_LOCALE = 'locale';
const THEME_VALUES = ['light', 'dark', 'system'];

export const DEFAULT_PREFS = {
    theme: 'system',
    ui_density: 'normal',
    sidebar_state: 'collapsed',
    sidebar_hover_preview: true,
    locale: 'es',
};

const setRootLocale = (locale) => {
    if (typeof document !== 'undefined') {
        document.documentElement.lang = locale || DEFAULT_PREFS.locale;
    }
};

function normalizeTheme(value) {
    if (THEME_VALUES.includes(value)) return value;
    if (typeof value === 'string' && value.includes('dark')) return 'dark';
    if (typeof value === 'string' && value.includes('light')) return 'light';
    return 'system';
}

/**
 * Hook unificado SPA: tema (ThemeProvider) + densidad + locale con persistencia API.
 */
export function useTheme() {
    const ctx = useThemeContext();
    const { user, updateUserTheme, updateUserPrefs } = useAuth();

    const [density, setDensityState] = useState(() => {
        if (typeof window === 'undefined') return DEFAULT_PREFS.ui_density;
        return user?.ui_density || localStorage.getItem('ui_density') || DEFAULT_PREFS.ui_density;
    });
    const [locale, setLocaleState] = useState(() => {
        if (typeof window === 'undefined') return DEFAULT_PREFS.locale;
        return user?.locale || localStorage.getItem(STORAGE_LOCALE) || DEFAULT_PREFS.locale;
    });

    useEffect(() => {
        if (!user?.theme) return;
        const normalized = normalizeTheme(user.theme);
        if (ctx.theme !== normalized) {
            ctx.setTheme(normalized, { persist: false });
        }
    }, [user?.theme, ctx.theme, ctx.setTheme]);

    const setTheme = useCallback(
        (next, opts = { persist: true }) => {
            if (!THEME_VALUES.includes(next)) return;
            ctx.setTheme(next, { persist: false });
            if (user && opts.persist !== false) {
                axios
                    .put('/api/profile/theme', { theme: next })
                    .then(() => updateUserTheme(next))
                    .catch(() => {});
            }
        },
        [ctx, user, updateUserTheme]
    );

    useEffect(() => {
        if (user?.ui_density && user.ui_density !== density) {
            setDensityState(user.ui_density);
        }
        document.documentElement.dataset.uiDensity = density;
    }, [user?.ui_density, density]);

    useEffect(() => {
        if (user?.locale && user.locale !== locale) {
            setLocaleState(user.locale);
            setRootLocale(user.locale);
        } else {
            setRootLocale(locale);
        }
    }, [user?.locale, locale]);

    const persistPreferences = useCallback(
        async (payload) => {
            if (!user) return;
            try {
                await axios.put('/api/profile/preferences', payload);
                updateUserPrefs(payload);
            } catch {
                // ignore
            }
        },
        [user, updateUserPrefs]
    );

    const applyDensity = useCallback(
        (next, opts = { persist: true }) => {
            if (!['normal', 'compact'].includes(next)) return;
            setDensityState(next);
            localStorage.setItem('ui_density', next);
            document.documentElement.dataset.uiDensity = next;
            if (user && opts.persist !== false) {
                persistPreferences({ ui_density: next });
            }
        },
        [user, persistPreferences]
    );

    const applyLocale = useCallback(
        (next, opts = { persist: true }) => {
            const allowed = ['es', 'en', 'ja', 'de', 'zh', 'fr'];
            if (!allowed.includes(next)) return;
            setLocaleState(next);
            setRootLocale(next);
            localStorage.setItem(STORAGE_LOCALE, next);
            if (user && opts.persist !== false) {
                persistPreferences({ locale: next });
            }
        },
        [user, persistPreferences]
    );

    const toggleTheme = useCallback(() => {
        const order = THEME_VALUES;
        const idx = order.indexOf(ctx.theme);
        const next = order[(idx + 1) % order.length];
        setTheme(next);
    }, [ctx.theme, setTheme]);

    return {
        theme: ctx.theme,
        setTheme,
        resolvedTheme: ctx.resolvedTheme,
        isDark: ctx.isDark,
        toggleTheme,
        cycleLight: () => setTheme('light'),
        cycleDark: () => setTheme('dark'),
        density,
        setDensity: (next, opts) => applyDensity(next, opts),
        locale,
        setLocale: (next, opts) => applyLocale(next, opts),
        themes: THEME_VALUES,
        defaults: DEFAULT_PREFS,
    };
}

export function useAuthTheme() {
    const ctx = useThemeContext();

    const setThemeAndPersist = useCallback(
        async (newTheme) => {
            ctx.setTheme(newTheme);
            try {
                await axios.put('/api/profile/theme', { theme: newTheme });
            } catch (err) {
                console.warn('Theme persistence failed:', err);
            }
        },
        [ctx]
    );

    return {
        ...ctx,
        setTheme: setThemeAndPersist,
    };
}

export default useTheme;
