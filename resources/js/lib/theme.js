/** Preferencia de tema: light | dark | system (sigue OS). */
export const THEME_STORAGE_KEY = "tikara_theme";
export const THEME_LEGACY_KEY = "theme";
export const THEME_VALUES = ["light", "dark", "system"];

export function getSystemTheme() {
    if (typeof window === "undefined") return "light";
    return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
}

export function normalizeTheme(value) {
    if (THEME_VALUES.includes(value)) return value;
    if (typeof value === "string" && value.includes("dark")) return "dark";
    if (typeof value === "string" && value.includes("light")) return "light";
    return "system";
}

export function resolveTheme(theme) {
    const normalized = normalizeTheme(theme);
    if (normalized === "system") return getSystemTheme();
    return normalized;
}

export function readStoredTheme() {
    if (typeof window === "undefined") return "system";
    try {
        const stored =
            localStorage.getItem(THEME_STORAGE_KEY) ?? localStorage.getItem(THEME_LEGACY_KEY);
        if (stored && THEME_VALUES.includes(stored)) {
            if (localStorage.getItem(THEME_LEGACY_KEY) && !localStorage.getItem(THEME_STORAGE_KEY)) {
                localStorage.setItem(THEME_STORAGE_KEY, stored);
                localStorage.removeItem(THEME_LEGACY_KEY);
            }
            return stored;
        }
    } catch {
        // ignore
    }
    return "system";
}

export function writeStoredTheme(theme) {
    if (!THEME_VALUES.includes(theme)) return;
    try {
        localStorage.setItem(THEME_STORAGE_KEY, theme);
        localStorage.removeItem(THEME_LEGACY_KEY);
    } catch {
        // ignore
    }
}

/** Aplica clase .dark/.light y color-scheme en <html>. */
export function applyTheme(theme, { switching = true } = {}) {
    if (typeof document === "undefined") return resolveTheme(theme);

    const resolved = resolveTheme(theme);
    const root = document.documentElement;

    if (switching) {
        root.dataset.themeSwitching = "1";
    }

    root.classList.remove("light", "dark");
    if (resolved === "dark") {
        root.classList.add("dark");
        root.style.colorScheme = "dark";
    } else {
        root.classList.add("light");
        root.style.colorScheme = "light";
    }

    if (switching) {
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                delete root.dataset.themeSwitching;
            });
        });
    }

    return resolved;
}
