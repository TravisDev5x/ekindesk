import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import axios from "@/lib/axios";
import { notify } from "@/lib/notify";

function showSessionFlash(flash) {
    const hasFlash = Object.values(flash ?? {}).some(Boolean);
    if (!hasFlash) return;
    if (flash.success) notify.success(flash.success);
    if (flash.error) notify.error(flash.error);
    if (flash.info) notify.info(flash.info);
    if (flash.warning) notify.warning(flash.warning);
}

const AuthContext = createContext(null);

/** Rutas donde NUNCA se debe llamar /check-auth (siempre 401; no es un bug). */
const GUEST_ONLY_PATHS = [
    "/login",
    "/register",
    "/register/accept",
    "/forgot-password",
    "/reset-password",
    "/verify-email",
];

function isGuestOnlyPath() {
    const path = typeof window !== "undefined" ? window.location.pathname : "";
    return GUEST_ONLY_PATHS.some((p) => path === p || path.startsWith(p + "/"));
}

export const AuthProvider = ({ children }) => {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (isGuestOnlyPath()) {
            setUser(null);
            setLoading(false);
            return;
        }
        axios
            .get("/check-auth", { withCredentials: true })
            .then((res) => {
                const payload = res.data;
                if (payload?.user) {
                    setUser({
                        ...payload.user,
                        roles: payload.roles || [],
                        permissions: payload.permissions || [],
                        onboarding_redirect: payload.onboarding_redirect ?? null,
                    });
                    window.__auth_user_id = payload.user.id;
                    showSessionFlash(payload.flash);
                } else {
                    setUser(null);
                    delete window.__auth_user_id;
                }
            })
            .catch(() => setUser(null))
            .finally(() => setLoading(false));
    }, []);

    const login = useCallback(async (credentials) => {
        await axios.get('/sanctum/csrf-cookie');
        const { data } = await axios.post('/api/login', credentials);
        setUser({
            ...data.user,
            roles: data.roles || [],
            permissions: data.permissions || [],
            onboarding_redirect: data.onboarding_redirect ?? null,
        });
        window.__auth_user_id = data.user?.id;
        window.location.href = data.onboarding_redirect || "/home";
    }, []);

    const logout = useCallback(async () => {
        try {
            await axios.get('/sanctum/csrf-cookie');
            await axios.post('/api/logout');
        } catch (error) {
            console.error('Logout error', error);
        } finally {
            setUser(null);
            delete window.__auth_user_id;
            window.dispatchEvent(new CustomEvent('navigate-to-login'));
        }
    }, []);

    const updateUserPrefs = useCallback((prefs) => {
        setUser((prev) => (prev ? { ...prev, ...prefs } : prev));
    }, []);

    const refreshUser = useCallback(() => {
        return axios.get("/check-auth", { withCredentials: true }).then((res) => {
            const payload = res.data;
            if (payload?.user) {
                setUser({
                    ...payload.user,
                    roles: payload.roles || [],
                    permissions: payload.permissions || [],
                    onboarding_redirect: payload.onboarding_redirect ?? null,
                });
                showSessionFlash(payload.flash);
            } else {
                setUser(null);
            }
        }).catch(() => setUser(null));
    }, []);

    const updateUserTheme = useCallback((theme) => {
        setUser((prev) => (prev ? { ...prev, theme } : prev));
    }, []);

    const can = useCallback((permission) => {
        if (!permission) return false;
        return Boolean(user?.permissions?.includes(permission));
    }, [user?.permissions]);

    const hasRole = useCallback((role) => {
        if (!role) return false;
        return Boolean(user?.roles?.includes(role));
    }, [user?.roles]);

    const value = useMemo(() => ({
        user,
        loading,
        login,
        logout,
        updateUserTheme,
        updateUserPrefs,
        refreshUser,
        can,
        hasRole,
    }), [user, loading, login, logout, updateUserTheme, updateUserPrefs, refreshUser, can, hasRole]);

    return (
        <AuthContext.Provider value={value}>
            {!loading && children}
        </AuthContext.Provider>
    );
};

export const useAuth = () => {
    const ctx = useContext(AuthContext);
    if (!ctx) {
        throw new Error("useAuth must be used within AuthProvider");
    }
    return ctx;
};


