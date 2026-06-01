import { useState, useEffect, useMemo } from "react";
import { Head, Link, usePage } from "@inertiajs/react";
import axios from "@/lib/axios";
import { passwordWithConfirmationSchema } from "@/lib/passwordSchema";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardHeader, CardTitle, CardContent, CardDescription } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { PlanTypeBadge } from "@/components/badges/EntityBadges";
import { AuthHeroShell } from "@/components/auth/AuthHeroShell";
import { authHeroCard, authMessageError, authMessageSuccess, passwordStrengthClass } from "@/lib/marketingTheme";
import { cn } from "@/lib/utils";
import { Eye, EyeOff, Check, X } from "lucide-react";

const emptyForm = {
    first_name: "",
    paternal_last_name: "",
    maternal_last_name: "",
    email: "",
    phone: "",
    password: "",
    password_confirmation: "",
};

function formatPlanPrice(value) {
    const n = Number(value);
    if (!n) return "A medida";
    return new Intl.NumberFormat("es-MX", { style: "currency", currency: "MXN" }).format(n);
}

export default function Register() {
    const { plans = [] } = usePage().props;

    const planSlug = useMemo(() => {
        if (typeof window === "undefined") return null;
        return new URLSearchParams(window.location.search).get("plan");
    }, []);

    const selectedPlan = useMemo(() => {
        if (!planSlug || !Array.isArray(plans) || plans.length === 0) return null;
        return plans.find((p) => String(p.slug).toLowerCase() === planSlug.toLowerCase()) ?? null;
    }, [planSlug, plans]);

    useEffect(() => {
        axios.get("/sanctum/csrf-cookie", { withCredentials: true }).catch(() => {});
    }, []);

    const [form, setForm] = useState(emptyForm);
    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");
    const [loading, setLoading] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [showPasswordConfirmation, setShowPasswordConfirmation] = useState(false);

    const passwordChecks = useMemo(() => {
        const p = form.password;
        return {
            length: p.length >= 12,
            lowercase: /[a-z]/.test(p),
            uppercase: /[A-Z]/.test(p),
            number: /[0-9]/.test(p),
            special: /[^A-Za-z0-9]/.test(p),
        };
    }, [form.password]);

    const passwordsMatch =
        form.password.length > 0 &&
        form.password_confirmation.length > 0 &&
        form.password === form.password_confirmation;

    const validate = () => {
        if (!form.first_name.trim()) return "El nombre(s) es obligatorio.";
        if (!form.paternal_last_name.trim()) return "El apellido paterno es obligatorio.";
        if (!form.email.trim()) return "El correo electrónico es obligatorio.";
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) {
            return "Ingresa un correo válido.";
        }
        const passwordValidation = passwordWithConfirmationSchema.safeParse({
            password: form.password,
            password_confirmation: form.password_confirmation,
        });
        if (!passwordValidation.success) {
            const err = passwordValidation.error;
            const first = err?.issues?.[0] ?? err?.errors?.[0];
            const msg = typeof first?.message === "string" ? first.message : null;
            return msg ?? "Revisa contraseña y confirmación.";
        }
        if (form.phone && form.phone.length !== 10) return "El teléfono debe tener 10 dígitos.";
        return "";
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError("");
        setSuccess("");

        const validationError = validate();
        if (validationError) {
            setError(validationError);
            return;
        }

        setLoading(true);

        try {
            const { data } = await axios.post("/api/register", {
                first_name: form.first_name.trim(),
                paternal_last_name: form.paternal_last_name.trim(),
                maternal_last_name: form.maternal_last_name.trim() || null,
                email: form.email.trim(),
                phone: form.phone.trim() || null,
                password: form.password,
                password_confirmation: form.password_confirmation,
                plan: planSlug || undefined,
            });

            if (data?.redirect_url) {
                window.location.href = data.redirect_url;
                return;
            }

            setSuccess(data?.message || "Registro creado correctamente.");
            setForm(emptyForm);
        } catch (err) {
            const status = err?.response?.status;
            const data = err?.response?.data;
            let serverMessage = null;
            if (data && typeof data === "object") {
                if (data.errors?.root != null) serverMessage = data.errors.root;
                else if (data.message && typeof data.message === "string") serverMessage = data.message;
                else if (data.errors && typeof data.errors === "object") {
                    const first = Object.values(data.errors).flat()[0];
                    if (typeof first === "string") serverMessage = first;
                }
            }

            if (status === 429) {
                setError("Demasiados intentos. Intenta más tarde.");
            } else if (serverMessage) {
                setError(serverMessage);
            } else {
                setError("No se pudo registrar. Intenta más tarde.");
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Head title="Registro de usuario" />
            <AuthHeroShell contentClassName="max-w-[460px]">
                <Card
                    className={`${authHeroCard} max-h-[90dvh] flex flex-col overflow-hidden`}
                >
                    <CardHeader className="shrink-0">
                        <CardTitle className="text-center">Registro de operador</CardTitle>
                    </CardHeader>
                    <CardContent className="min-h-0 overflow-y-auto pb-20 md:pb-[max(1rem,env(safe-area-inset-bottom))]">
                        <form onSubmit={handleSubmit} className="space-y-4">
                            {selectedPlan && (
                                <Card className="border-primary/30 bg-primary/5 shadow-none">
                                    <CardHeader className="py-3 px-4">
                                        <div className="flex items-start justify-between gap-2">
                                            <div>
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <CardTitle className="text-base">
                                                        Plan {selectedPlan.name}
                                                    </CardTitle>
                                                    <PlanTypeBadge type={selectedPlan.type} />
                                                </div>
                                                <CardDescription className="text-xs">
                                                    {formatPlanPrice(selectedPlan.price_monthly)}/mes
                                                    {selectedPlan.trial_days > 0
                                                        ? ` · ${selectedPlan.trial_days} días de prueba`
                                                        : ""}
                                                </CardDescription>
                                            </div>
                                            {selectedPlan.highlighted && (
                                                <Badge variant="secondary">Recomendado</Badge>
                                            )}
                                        </div>
                                        <a
                                            href="/landing#pricing"
                                            className="text-xs text-primary hover:underline inline-block mt-2"
                                        >
                                            Cambiar plan
                                        </a>
                                    </CardHeader>
                                </Card>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="reg-first-name">Nombre(s)</Label>
                                <Input
                                    id="reg-first-name"
                                    value={form.first_name}
                                    onChange={(e) => setForm({ ...form, first_name: e.target.value })}
                                    placeholder="Ej. Juan Carlos"
                                    disabled={loading}
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="reg-paternal">Apellido paterno</Label>
                                <Input
                                    id="reg-paternal"
                                    value={form.paternal_last_name}
                                    onChange={(e) => setForm({ ...form, paternal_last_name: e.target.value })}
                                    placeholder="Ej. Pérez"
                                    disabled={loading}
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="reg-maternal">Apellido materno (opcional)</Label>
                                <Input
                                    id="reg-maternal"
                                    value={form.maternal_last_name}
                                    onChange={(e) => setForm({ ...form, maternal_last_name: e.target.value })}
                                    placeholder="Ej. García"
                                    disabled={loading}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="reg-email">Correo electrónico</Label>
                                <Input
                                    id="reg-email"
                                    type="email"
                                    value={form.email}
                                    onChange={(e) => setForm({ ...form, email: e.target.value })}
                                    autoComplete="email"
                                    disabled={loading}
                                    required
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="reg-phone">Teléfono (opcional)</Label>
                                <Input
                                    id="reg-phone"
                                    value={form.phone}
                                    onChange={(e) => setForm({ ...form, phone: e.target.value })}
                                    maxLength={10}
                                    disabled={loading}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="reg-password">Contraseña</Label>
                                <div className="relative">
                                    <Input
                                        id="reg-password"
                                        type={showPassword ? "text" : "password"}
                                        value={form.password}
                                        onChange={(e) => setForm({ ...form, password: e.target.value })}
                                        autoComplete="new-password"
                                        disabled={loading}
                                        className="pr-12"
                                        required
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => setShowPassword((v) => !v)}
                                        className="absolute right-2 top-1/2 -translate-y-1/2 h-8 w-8"
                                        disabled={loading}
                                    >
                                        {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                    </Button>
                                </div>
                                <div className="rounded-md border border-border/60 bg-muted/30 px-3 py-2 text-xs space-y-1.5">
                                    <p className="font-medium text-muted-foreground">Requisitos de la contraseña:</p>
                                    <ul className="space-y-1">
                                        {[
                                            ["length", "Mínimo 12 caracteres"],
                                            ["lowercase", "Al menos una minúscula"],
                                            ["uppercase", "Al menos una mayúscula"],
                                            ["number", "Al menos un número"],
                                            ["special", "Al menos un carácter especial"],
                                        ].map(([key, label]) => (
                                            <li
                                                key={key}
                                                className={
                                                    passwordChecks[key]
                                                        ? "text-emerald-600 dark:text-emerald-400 flex items-center gap-2"
                                                        : "text-muted-foreground flex items-center gap-2"
                                                }
                                            >
                                                {passwordChecks[key] ? (
                                                    <Check className="h-3.5 w-3.5 shrink-0" />
                                                ) : (
                                                    <X className="h-3.5 w-3.5 shrink-0 opacity-50" />
                                                )}
                                                {label}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="reg-password-confirmation">Confirmar contraseña</Label>
                                <div className="relative">
                                    <Input
                                        id="reg-password-confirmation"
                                        type={showPasswordConfirmation ? "text" : "password"}
                                        value={form.password_confirmation}
                                        onChange={(e) =>
                                            setForm({ ...form, password_confirmation: e.target.value })
                                        }
                                        autoComplete="new-password"
                                        disabled={loading}
                                        className="pr-12"
                                        required
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => setShowPasswordConfirmation((v) => !v)}
                                        className="absolute right-2 top-1/2 -translate-y-1/2 h-8 w-8"
                                        disabled={loading}
                                    >
                                        {showPasswordConfirmation ? (
                                            <EyeOff className="h-4 w-4" />
                                        ) : (
                                            <Eye className="h-4 w-4" />
                                        )}
                                    </Button>
                                </div>
                                {form.password_confirmation.length > 0 && (
                                    <p
                                        className={cn(
                                            "text-xs",
                                            passwordsMatch
                                                ? passwordStrengthClass(3)
                                                : passwordStrengthClass(1)
                                        )}
                                    >
                                        {passwordsMatch
                                            ? "Las contraseñas coinciden"
                                            : "Las contraseñas no coinciden"}
                                    </p>
                                )}
                            </div>

                            <p className="text-xs text-muted-foreground">
                                Recibirás un enlace de verificación en tu correo para activar la cuenta y continuar con
                                la configuración de tu empresa.
                            </p>

                            {error ? (
                                <p className={authMessageError} role="alert">
                                    {error}
                                </p>
                            ) : null}
                            {success ? <p className={authMessageSuccess}>{success}</p> : null}

                            <Button type="submit" className="w-full min-h-[44px]" disabled={loading}>
                                {loading ? "Registrando..." : "Crear cuenta"}
                            </Button>
                            <p className="text-center text-xs text-muted-foreground">
                                ¿Ya tienes cuenta?{" "}
                                <Link href="/login" className="text-primary hover:underline">
                                    Inicia sesión
                                </Link>
                            </p>
                        </form>
                    </CardContent>
                </Card>
            </AuthHeroShell>
        </>
    );
}
