import React, { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react';

const STORAGE_KEY = 'theme';

/** @type {'light' | 'dark' | 'system'} */
const THEME_VALUES = ['light', 'dark', 'system'];

const ThemeContext = createContext(undefined);

function getSystemTheme() {
  if (typeof window === 'undefined') return 'light';
  return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function getStoredTheme() {
  if (typeof window === 'undefined') return null;
  const stored = localStorage.getItem(STORAGE_KEY);
  if (THEME_VALUES.includes(stored)) return stored;
  return null;
}

/**
 * Resuelve el tema efectivo: 'light' o 'dark'.
 * @param {'light' | 'dark' | 'system'} theme
 * @returns {'light' | 'dark'}
 */
function resolveTheme(theme) {
  if (theme === 'system') return getSystemTheme();
  return theme;
}

/**
 * Aplica la clase `dark` en document.documentElement según el tema resuelto.
 * Debe ejecutarse de forma síncrona para evitar flicker.
 */
function applyResolvedToDom(resolved) {
  const root = document.documentElement;
  if (resolved === 'dark') {
    root.classList.add('dark');
    root.style.colorScheme = 'dark';
  } else {
    root.classList.remove('dark');
    root.style.colorScheme = 'light';
  }
}

export function ThemeProvider({ children, defaultTheme = 'system', storageKey = STORAGE_KEY }) {
  const [theme, setThemeState] = useState(() => {
    const stored = getStoredTheme();
    if (stored) return stored;
    return defaultTheme;
  });

  const resolvedTheme = useMemo(() => resolveTheme(theme), [theme]);

  const [systemTheme, setSystemTheme] = useState(getSystemTheme);

  // Sincronizar tema resuelto con el DOM (clase dark en <html>)
  useEffect(() => {
    const resolved = theme === 'system' ? systemTheme : theme;
    applyResolvedToDom(resolved);
    // Quitar data-theme-init tras el primer pintado para permitir transiciones
    const root = document.documentElement;
    if (root.dataset.themeInit) {
      requestAnimationFrame(() => {
        delete root.dataset.themeInit;
      });
    }
  }, [theme, systemTheme]);

  // Escuchar cambios de prefers-color-scheme cuando theme === 'system'
  useEffect(() => {
    const mq = window.matchMedia('(prefers-color-scheme: dark)');
    const handler = (e) => setSystemTheme(e.matches ? 'dark' : 'light');
    handler(mq);
    mq.addEventListener('change', handler);
    return () => mq.removeEventListener('change', handler);
  }, []);

  const setTheme = useCallback(
    /** @param {'light' | 'dark' | 'system'} next */
    (next) => {
      if (!THEME_VALUES.includes(next)) return;
      setThemeState(next);
      localStorage.setItem(storageKey, next);
    },
    [storageKey]
  );

  const value = useMemo(
    () => ({
      theme,
      setTheme,
      resolvedTheme,
      isDark: resolvedTheme === 'dark',
    }),
    [theme, setTheme, resolvedTheme]
  );

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme() {
  const ctx = useContext(ThemeContext);
  if (ctx === undefined) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }
  return ctx;
}

/**
 * Script inline para ejecutar en <head> antes de React (anti-flicker).
 * Lee localStorage y matchMedia y aplica la clase `dark` en <html> de inmediato.
 */
export function getThemeInitScript(storageKey = STORAGE_KEY) {
  return `
(function() {
  try {
    var stored = localStorage.getItem('${storageKey}');
    var theme = stored === 'dark' || stored === 'light' || stored === 'system' ? stored : 'system';
    var resolved = theme === 'system'
      ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
      : theme;
    var root = document.documentElement;
    root.dataset.themeInit = '1';
    if (resolved === 'dark') {
      root.classList.add('dark');
      root.style.colorScheme = 'dark';
    } else {
      root.classList.remove('dark');
      root.style.colorScheme = 'light';
    }
  } catch (e) {}
})();
`.trim();
}
