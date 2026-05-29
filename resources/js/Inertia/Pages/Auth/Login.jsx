import { useEffect, useState } from "react";
import { Head, Link } from "@inertiajs/react";
import axios from "@/lib/axios";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Separator } from "@/components/ui/separator";
import { Loader2, Eye, EyeOff } from "lucide-react";

const PATTERN_STYLE = {
    backgroundImage: "radial-gradient(circle, rgba(6,182,212,0.06) 1px, transparent 1px)",
    backgroundSize: "32px 32px",
};

const INPUT_CLASS =
    "h-11 w-full rounded-lg border-slate-700 bg-slate-800 text-slate-100 placeholder:text-slate-500 focus-visible:border-cyan-500 focus-visible:ring-1 focus-visible:ring-cyan-500/20";

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

function LoginBrandingColumn() {
    return (
        <aside
            className="hidden lg:flex lg:w-2/5 flex-col justify-between p-12 relative overflow-hidden bg-slate-950"
            style={PATTERN_STYLE}
        >
            <div
                className="absolute -top-20 -left-20 w-96 h-96 rounded-full blur-3xl bg-gradient-to-br from-cyan-500/10 to-blue-600/10 -z-10 pointer-events-none"
                aria-hidden
            />

            <div className="flex items-center gap-3 relative z-10">
                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 shadow-lg shadow-cyan-500/20">
                    <span className="text-white font-black text-lg">E</span>
                </div>
                <span className="text-white font-bold text-xl tracking-tight">EkinDesk</span>
            </div>

            <div className="relative z-10 my-auto">
                <div className="inline-flex items-center gap-2 rounded-full border border-cyan-500/20 bg-cyan-500/10 px-4 py-1 text-sm text-cyan-400 mb-6">
                    <span className="h-2 w-2 rounded-full bg-cyan-400 animate-pulse" />
                    Acceso seguro
                </div>

                <h2 className="text-4xl font-black text-white leading-tight">
                    Bienvenido
                    <br />
                    de nuevo
                </h2>

                <p className="text-slate-400 text-base mt-4 max-w-sm leading-relaxed">
                    Inicia sesión con el correo de tu cuenta para entrar al panel de tu negocio.
                </p>

                <ul className="mt-8 space-y-3">
                    <li className="flex items-center gap-3 text-sm text-slate-300">
                        <span className="h-2 w-2 shrink-0 rounded-full bg-cyan-400" />
                        Misma cuenta para todas las pantallas.
                    </li>
                    <li className="flex items-center gap-3 text-sm text-slate-300">
                        <span className="h-2 w-2 shrink-0 rounded-full bg-blue-400" />
                        Roles y permisos según tu equipo.
                    </li>
                    <li className="flex items-center gap-3 text-sm text-slate-300">
                        <span className="h-2 w-2 shrink-0 rounded-full bg-slate-400" />
                        Tus datos seguros y aislados.
                    </li>
                </ul>
            </div>

            <div className="relative z-10 flex gap-4 text-xs text-slate-500">
                <Link href="/privacidad" className="hover:text-slate-300 transition-colors">
                    Aviso de privacidad
                </Link>
                <Link href="/terminos" className="hover:text-slate-300 transition-colors">
                    Términos del servicio
                </Link>
            </div>
        </aside>
    );
}

export default function Login() {
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
            }
            window.location.href = data?.onboarding_redirect || "/";
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
            <Head title="Iniciar sesión — EkinDesk" />
            <div className="min-h-screen flex bg-slate-950 text-slate-100">
                <LoginBrandingColumn />

                <div className="flex-1 flex items-center justify-center p-8 lg:p-16 bg-slate-900/50 min-h-screen">
                    <div className="w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">
                        <div className="lg:hidden flex items-center gap-3 mb-8">
                            <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600">
                                <span className="text-white font-black">E</span>
                            </div>
                            <span className="text-white font-bold text-lg">EkinDesk</span>
                        </div>

                        <div className="flex justify-end items-center text-sm mb-6">
                            <span className="text-slate-400">¿Aún no tienes cuenta?</span>
                            <Link
                                href="/register"
                                className="text-cyan-400 hover:text-cyan-300 font-semibold ml-1"
                            >
                                Crear cuenta gratis
                            </Link>
                        </div>

                        <div>
                            <Button
                                type="button"
                                variant="outline"
                                className="w-full h-11 border-slate-700 text-slate-300 hover:border-slate-600 hover:bg-slate-800 bg-transparent"
                                onClick={(e) => e.preventDefault()}
                            >
                                <GoogleIcon />
                                <span className="ml-2">Continuar con Google</span>
                            </Button>
                            <p className="text-slate-600 text-xs text-center mt-1">
                                Próximamente disponible
                            </p>
                        </div>

                        <div className="relative my-6">
                            <Separator className="bg-slate-800" />
                            <span className="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 bg-slate-900 text-slate-500 text-xs px-3 whitespace-nowrap">
                                O CON CORREO Y CONTRASEÑA
                            </span>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="login-email" className="text-slate-300 text-sm mb-1.5 block">
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
                                    className={INPUT_CLASS}
                                />
                            </div>

                            <div>
                                <Label
                                    htmlFor="login-password"
                                    className="text-slate-300 text-sm mb-1.5 block"
                                >
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
                                        className={`${INPUT_CLASS} pr-12`}
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => setShowPassword((v) => !v)}
                                        className="absolute right-1 top-1/2 -translate-y-1/2 h-9 w-9 text-slate-400 hover:text-slate-200 hover:bg-slate-800"
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
                                <label
                                    className="flex cursor-pointer items-center"
                                    htmlFor="remember"
                                >
                                    <Checkbox
                                        id="remember"
                                        checked={remember}
                                        onCheckedChange={(v) => setRemember(Boolean(v))}
                                        disabled={loading}
                                        className="border-slate-600 data-[state=checked]:bg-cyan-500 data-[state=checked]:border-cyan-500"
                                    />
                                    <span className="text-slate-400 text-sm ml-2 select-none">
                                        Mantener sesión en este dispositivo
                                    </span>
                                </label>
                                <Link
                                    href="/forgot-password"
                                    className="text-cyan-400 hover:text-cyan-300 text-sm shrink-0"
                                >
                                    ¿Olvidaste tu contraseña?
                                </Link>
                            </div>

                            {error ? (
                                <p className="text-red-400 text-xs" role="alert" aria-live="polite">
                                    {error}
                                </p>
                            ) : null}

                            <Button
                                type="submit"
                                disabled={loading}
                                className="w-full h-11 gap-2 bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-white font-semibold rounded-lg border-0 transition-all duration-200"
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
