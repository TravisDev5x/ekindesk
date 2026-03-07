import { useCallback, useEffect, useState } from 'react';
import axios from '@/lib/axios';
import { useAuth } from '@/context/AuthContext';
import { useTheme as useThemeFromProvider } from '@/components/theme-provider';

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

/**
 * Hook unificado: tema (light/dark/system) desde ThemeProvider + densidad y locale con persistencia en API.
 */
export function useTheme() {
    const { theme, setTheme: setThemeFromContext, resolvedTheme, isDark } = useThemeFromProvider();
    const { user, updateUserTheme, updateUserPrefs } = useAuth();

    const [density, setDensityState] = useState(() => {
        if (typeof window === 'undefined') return DEFAULT_PREFS.ui_density;
        return user?.ui_density || localStorage.getItem('ui_density') || DEFAULT_PREFS.ui_density;
    });
    const [locale, setLocaleState] = useState(() => {
        if (typeof window === 'undefined') return DEFAULT_PREFS.locale;
        return user?.locale || localStorage.getItem(STORAGE_LOCALE) || DEFAULT_PREFS.locale;
    });

    // Sincronizar tema del backend al cargar usuario; valores antiguos se normalizan a light/dark/system
    useEffect(() => {
        if (!user?.theme) return;
        const t = user.theme;
        if (THEME_VALUES.includes(t)) {
            setThemeFromContext(t);
            localStorage.setItem('theme', t);
        } else {
            const normalized = t.includes('dark') ? 'dark' : t.includes('light') ? 'light' : 'system';
            setThemeFromContext(normalized);
            localStorage.setItem('theme', normalized);
        }
    }, [user?.theme, setThemeFromContext]);

    // Persistir tema en API cuando el usuario está logueado y cambia el tema
    const setTheme = useCallback(
        (next, opts = { persist: true }) => {
            if (!THEME_VALUES.includes(next)) return;
            setThemeFromContext(next);
            if (user && opts.persist !== false) {
                axios
                    .put('/api/profile/theme', { theme: next })
                    .then(() => updateUserTheme(next))
                    .catch(() => {});
            }
        },
        [setThemeFromContext, user, updateUserTheme]
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
            } catch (_) {}
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
        setTheme(isDark ? 'light' : 'dark');
    }, [isDark, setTheme]);

    return {
        theme,
        setTheme,
        resolvedTheme,
        isDark,
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
