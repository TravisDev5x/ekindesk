import { useEffect, useRef } from "react";
import { useAuth } from "@/context/AuthContext";
import { useThemeContext } from "@/components/theme-provider";
import { normalizeTheme } from "@/lib/theme";

/**
 * Sincroniza tema desde BD solo al iniciar sesión (user.id nuevo).
 * Evita pisar cambios locales con auth.user.theme desactualizado.
 */
export function ThemeAuthSync() {
    const { user } = useAuth();
    const { setTheme } = useThemeContext();
    const syncedUserIdRef = useRef(null);

    useEffect(() => {
        if (!user?.id || !user?.theme) return;
        if (syncedUserIdRef.current === user.id) return;

        syncedUserIdRef.current = user.id;
        setTheme(normalizeTheme(user.theme), { persist: false });
    }, [user?.id, user?.theme, setTheme]);

    useEffect(() => {
        if (!user?.id) {
            syncedUserIdRef.current = null;
        }
    }, [user?.id]);

    return null;
}
