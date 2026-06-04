import { useEffect, useMemo, useState } from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import axios from "@/lib/axios";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Separator } from "@/components/ui/separator";
import { ThemeToggle } from "@/components/ThemeToggle";
import { Loader2, Eye, EyeOff } from "lucide-react";
import {
    authCard,
    authPanelSide,
    brandBadgeSm,
    brandPanelGlow,
    btnBrand,
    btnBrandOutline,
    linkBrand,
    surfaceAuth,
} from "@/lib/marketingTheme";
import {
    getTenantBrandName,
    isClientPortalTenant,
    resolveTenantBrandCssVars,
} from "@/lib/tenantBranding";
import { TenantBrandLoginMark } from "@/components/TenantBrand";
import { statusDotInfo } from "@/lib/badgeStyles";
import { cn } from "@/lib/utils";

function GoogleIcon() {
    return (
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden>
            <path
                fill="#4285F4"
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
            />
            <path
                fill="#34A853"
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
            />
            <path
                fill="#FBBC05"
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
            />
            <path
                fill="#EA4335"
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
            />
        </svg>
    );
}

function LoginBrandingColumn({ tenant }) {
    const brandName = getTenantBrandName(tenant, "EkinDesk");
    const isPortal = isClientPortalTenant(tenant);
    const welcome =
        tenant?.portal_welcome_message ||
        (isPortal
            ? `Accede al portal de ${brandName}.`
            : "Inicia sesión con el correo de tu cuenta para entrar al panel de tu negocio.");

    return (
        <aside
            className={authPanelSide}
            style={resolveTenantBrandCssVars(tenant)}
        >
            <div className={brandPanelGlow} aria-hidden />

            <div className="flex items-center gap-3 relative z-10">
                <TenantBrandLoginMark tenant={tenant} />
                <span className="text-foreground font-bold text-xl tracking-tight">{brandName}</span>
            </div>

            <div className="relative z-10 my-auto">
                <div className={`inline-flex items-center gap-2 ${brandBadgeSm} mb-6`}>
                    <span className="h-2 w-2 rounded-full bg-brand animate-pulse" />
                    {isPortal ? "Portal de tu organización" : "Acceso seguro"}
                </div>

                <h2 className="text-4xl font-black text-foreground leading-tight">
                    Bienvenido
                    <br />
                    de nuevo
                </h2>

                <p className="text-muted-foreground text-base mt-4 max-w-sm leading-relaxed">{welcome}</p>

                <ul className="mt-8 space-y-3">
                    <li className="flex items-center gap-3 text-sm text-muted-foreground">
                        <span className="h-2 w-2 shrink-0 rounded-full bg-brand" />
                        Misma cuenta para todas las pantallas.
                    </li>
                    <li className="flex items-center gap-3 text-sm text-muted-foreground">
                        <span className={cn("h-2 w-2 shrink-0", statusDotInfo)} />
                        Roles y permisos según tu equipo.
                    </li>
                    <li className="flex items-center gap-3 text-sm text-muted-foreground">
                        <span className="h-2 w-2 shrink-0 rounded-full bg-muted-foreground" />
                        Tus datos seguros y aislados.
                    </li>
                </ul>
            </div>

            <div className="relative z-10 flex gap-4 text-xs text-muted-foreground">
                <Link href="/privacidad" className="hover:text-foreground transition-colors">
                    Aviso de privacidad
                </Link>
                <Link href="/terminos" className="hover:text-foreground transition-colors">
                    Términos del servicio
                </Link>
            </div>
        </aside>
    );
}

export default function Login() {
    const { tenant = {}, authProviders = {}, flash = {} } = usePage().props;
    const pageTitle = useMemo(() => {
        if (tenant?.mode === "client_portal" && tenant?.name) {
            return `Iniciar sesión — ${tenant.name}`;
        }
        return "Iniciar sesión — EkinDesk";
    }, [tenant]);

    const [form, setForm] = useState({ email: "", password: "" });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");
    const [showPassword, setShowPassword] = useState(false);
    const [remember, setRemember] = useState(() => {
        if (typeof localStorage === "undefined") return false;
        const saved = localStorage.getItem("login.remember");
        const savedEmail =
            localStorage.getItem("login.email") || localStorage.getItem("login.identifier") || "";
        if (saved === "1" && savedEmail) {
            setTimeout(() => setForm((f) => ({ ...f, email: savedEmail })), 0);
            return true;
        }
        return false;
    });

    useEffect(() => {
        axios.get("/sanctum/csrf-cookie", { withCredentials: true }).catch(() => {});
    }, []);

    useEffect(() => {
        if (typeof flash?.error === "string" && flash.error) {
            setError(flash.error);
        }
    }, [flash?.error]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError("");

        const email = form.email.trim();
        if (!email) {
            setError("El correo electrónico es obligatorio.");
            return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            setError("Ingresa un correo electrónico válido.");
            return;
        }
        if (!form.password) {
            setError("La contraseña es obligatoria.");
            return;
        }

        setLoading(true);

        try {
            const { data } = await axios.post("/api/login", {
                identifier: email,
                password: form.password,
            });
            if (remember) {
                localStorage.setItem("login.remember", "1");
                localStorage.setItem("login.email", email);
                localStorage.removeItem("login.identifier");
            } else {
                localStorage.removeItem("login.remember");
                localStorage.removeItem("login.email");
                localStorage.removeItem("login.identifier");
            }
            const userTheme = data?.user?.theme;
            if (userTheme && ["light", "dark", "system"].includes(userTheme)) {
                localStorage.setItem("ekindesk_theme", userTheme);
                const resolved =
                    userTheme === "system"
                        ? window.matchMedia("(prefers-color-scheme: dark)").matches
                            ? "dark"
                            : "light"
                        : userTheme;
                const root = document.documentElement;
                root.classList.remove("light", "dark");
                root.classList.add(resolved);
                root.style.colorScheme = resolved;
            }
            window.location.href = data?.onboarding_redirect || "/home";
        } catch (err) {
            const status = err?.response?.status;
            const serverMessage = err?.response?.data?.errors?.root;
            if ((status === 422 || status === 403) && typeof serverMessage === "string") {
                setError(serverMessage);
            } else if (status === 429) {
                setError("Demasiados intentos. Intenta de nuevo en unos segundos.");
            } else if (status >= 500) {
                setError("Error del servidor. Intenta nuevamente.");
            } else {
                setError("No se pudo iniciar sesión.");
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Head title={pageTitle} />
            <div className={`${surfaceAuth} flex`}>
                <LoginBrandingColumn tenant={tenant} />

                <div className="flex-1 flex items-center justify-center p-8 lg:p-16 bg-muted/20 min-h-screen">
                    <div className={authCard}>
                        <div className="flex justify-between items-start mb-6">
                            <div className="lg:hidden flex items-center gap-3">
                                <TenantBrandLoginMark tenant={tenant} className="h-9 w-9" />
                                <span className="text-foreground font-bold text-lg">
                                    {getTenantBrandName(tenant, "EkinDesk")}
                                </span>
                            </div>
                            <div className="ml-auto">
                                <ThemeToggle variant="icon" />
                            </div>
                        </div>

                        {tenant?.mode !== "client_portal" ? (
                            <div className="flex justify-end items-center text-sm mb-6">
                                <span className="text-muted-foreground">¿Aún no tienes cuenta?</span>
                                <Link href="/register" className={`${linkBrand} ml-1`}>
                                    Crear cuenta gratis
                                </Link>
                            </div>
                        ) : null}

                        <div>
                            {authProviders?.google ? (
                                <Button
                                    type="button"
                                    variant="outline"
                                    className={`w-full h-11 ${btnBrandOutline}`}
                                    asChild
                                >
                                    <a href="/auth/google/redirect?intent=login">
                                        <GoogleIcon />
                                        <span className="ml-2">Continuar con Google</span>
                                    </a>
                                </Button>
                            ) : (
                                <>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className={`w-full h-11 ${btnBrandOutline}`}
                                        disabled
                                    >
                                        <GoogleIcon />
                                        <span className="ml-2">Continuar con Google</span>
                                    </Button>
                                    <p className="text-muted-foreground text-xs text-center mt-1">
                                        Próximamente disponible
                                    </p>
                                </>
                            )}
                        </div>

                        <div className="relative my-6">
                            <Separator className="bg-border" />
                            <span className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 bg-card text-muted-foreground text-xs px-3 whitespace-nowrap">
                                O CON CORREO Y CONTRASEÑA
                            </span>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="login-email" className="text-sm mb-1.5 block">
                                    Correo electrónico
                                </Label>
                                <Input
                                    id="login-email"
                                    type="email"
                                    inputMode="email"
                                    autoComplete="email"
                                    placeholder="tu@empresa.com"
                                    value={form.email}
                                    onChange={(e) =>
                                        setForm((prev) => ({ ...prev, email: e.target.value }))
                                    }
                                    autoFocus
                                    disabled={loading}
                                    aria-invalid={Boolean(error)}
                                    className="h-11"
                                />
                            </div>

                            <div>
                                <Label htmlFor="login-password" className="text-sm mb-1.5 block">
                                    Contraseña
                                </Label>
                                <div className="relative">
                                    <Input
                                        id="login-password"
                                        type={showPassword ? "text" : "password"}
                                        value={form.password}
                                        onChange={(e) =>
                                            setForm((prev) => ({ ...prev, password: e.target.value }))
                                        }
                                        autoComplete="current-password"
                                        disabled={loading}
                                        aria-invalid={Boolean(error)}
                                        className="h-11 pr-12"
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => setShowPassword((v) => !v)}
                                        className="absolute right-1 top-1/2 -translate-y-1/2 h-9 w-9 text-muted-foreground hover:text-foreground"
                                        disabled={loading}
                                        aria-label={
                                            showPassword ? "Ocultar contraseña" : "Mostrar contraseña"
                                        }
                                    >
                                        {showPassword ? (
                                            <EyeOff className="h-4 w-4" />
                                        ) : (
                                            <Eye className="h-4 w-4" />
                                        )}
                                    </Button>
                                </div>
                            </div>

                            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-3 mb-6">
                                <label className="flex cursor-pointer items-center" htmlFor="remember">
                                    <Checkbox
                                        id="remember"
                                        checked={remember}
                                        onCheckedChange={(v) => setRemember(Boolean(v))}
                                        disabled={loading}
                                    />
                                    <span className="text-muted-foreground text-sm ml-2 select-none">
                                        Mantener sesión en este dispositivo
                                    </span>
                                </label>
                                <Link href="/forgot-password" className={`${linkBrand} text-sm shrink-0`}>
                                    ¿Olvidaste tu contraseña?
                                </Link>
                            </div>

                            {error ? (
                                <p className="text-destructive text-xs" role="alert" aria-live="polite">
                                    {error}
                                </p>
                            ) : null}

                            <Button
                                type="submit"
                                disabled={loading}
                                className={`w-full h-11 gap-2 rounded-lg ${btnBrand}`}
                            >
                                {loading ? (
                                    <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
                                ) : null}
                                <span>{loading ? "Entrando..." : "Entrar al panel"}</span>
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
