import { useEffect, useMemo, useState } from "react";
import { Head, Link, useForm } from "@inertiajs/react";
import axios from "@/lib/axios";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Eye, EyeOff, Loader2 } from "lucide-react";
import { AuthSimpleShell } from "@/components/auth/AuthSimpleShell";
import { authSimpleCard, btnBrand, passwordStrengthClass } from "@/lib/marketingTheme";
import { cn } from "@/lib/utils";

function passwordStrength(password) {
    if (!password) return { label: "", score: 0 };
    let score = 0;
    if (password.length >= 12) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[A-Z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;
    if (score <= 2) return { label: "Débil", score: 1 };
    if (score <= 4) return { label: "Media", score: 2 };
    return { label: "Fuerte", score: 3 };
}

function formatExpiry(iso) {
    if (!iso) return "";
    try {
        return new Date(iso).toLocaleString("es-MX", {
            dateStyle: "medium",
            timeStyle: "short",
        });
    } catch {
        return iso;
    }
}

function InvitationCard({ children, title, description }) {
    return (
        <Card className={cn(authSimpleCard, "max-w-lg")}>
            <CardHeader className="space-y-2">
                <CardTitle className="text-2xl">{title}</CardTitle>
                {description ? <CardDescription>{description}</CardDescription> : null}
            </CardHeader>
            <CardContent>{children}</CardContent>
        </Card>
    );
}

export default function AcceptInvitation({
    token,
    email,
    role_name,
    client_name,
    expires_at,
    assigns_role_on_accept,
    google_enabled,
    google_url,
    error: pageError,
}) {
    const isInvalid = Boolean(pageError) || !token;

    const { data, setData, post, processing, errors, reset } = useForm({
        token: token || "",
        first_name: "",
        paternal_last_name: "",
        maternal_last_name: "",
        password: "",
        password_confirmation: "",
    });

    const [showPassword, setShowPassword] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    useEffect(() => {
        axios.get("/sanctum/csrf-cookie", { withCredentials: true }).catch(() => {});
    }, []);

    const strength = useMemo(() => passwordStrength(data.password), [data.password]);

    const handleSubmit = (e) => {
        e.preventDefault();
        post("/register/accept", {
            onSuccess: () => reset("password", "password_confirmation"),
        });
    };

    if (isInvalid) {
        return (
            <>
                <Head title="Invitación no válida" />
                <AuthSimpleShell maxWidth="max-w-md">
                    <InvitationCard title="Invitación no válida" description={pageError}>
                        <p className="text-sm text-muted-foreground mb-4">
                            Solicita una nueva invitación al administrador de tu organización.
                        </p>
                        <div className="flex flex-col gap-2 sm:flex-row">
                            <Button asChild variant="outline" className="flex-1">
                                <a href="mailto:soporte@helpdesk.local">Contactar soporte</a>
                            </Button>
                            <Button asChild className={cn("flex-1", btnBrand)}>
                                <Link href="/login">Ir al inicio de sesión</Link>
                            </Button>
                        </div>
                    </InvitationCard>
                </AuthSimpleShell>
            </>
        );
    }

    return (
        <>
            <Head title="Configura tu cuenta" />
            <AuthSimpleShell maxWidth="max-w-lg">
                <InvitationCard
                    title="Configura tu cuenta"
                    description="Completa tus datos para entrar. Un administrador te asignará el rol y permisos según tu puesto."
                >
                    <div className="rounded-lg border border-border/60 bg-muted/40 p-3 text-sm space-y-1 mb-4">
                        <p>
                            Fuiste invitado
                            {client_name ? (
                                <>
                                    {" "}
                                    a <strong>{client_name}</strong>
                                </>
                            ) : null}
                            {role_name && assigns_role_on_accept ? (
                                <>
                                    {" "}
                                    con rol sugerido <strong>{role_name}</strong>
                                </>
                            ) : null}
                            .
                        </p>
                        {!assigns_role_on_accept && (
                            <p className="text-muted-foreground text-xs">
                                Tras activar tu cuenta verás un aviso hasta que un administrador confirme tu rol.
                            </p>
                        )}
                        {expires_at && (
                            <Badge variant="outline" className="text-xs font-normal">
                                Invitación válida hasta {formatExpiry(expires_at)}
                            </Badge>
                        )}
                    </div>

                    {google_enabled && google_url ? (
                        <>
                            <Button type="button" variant="outline" className="w-full mb-4" asChild>
                                <a href={google_url}>Continuar con Google</a>
                            </Button>
                            <p className="text-center text-xs text-muted-foreground mb-4">
                                O completa el formulario con contraseña
                            </p>
                        </>
                    ) : null}

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <input type="hidden" name="token" value={data.token} readOnly />

                        <div className="space-y-2">
                            <Label htmlFor="email">Correo</Label>
                            <Input id="email" type="email" value={email} disabled readOnly className="bg-muted" />
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2">
                            <div className="space-y-2 sm:col-span-2">
                                <Label htmlFor="first_name">Nombre(s)</Label>
                                <Input
                                    id="first_name"
                                    value={data.first_name}
                                    onChange={(e) => setData("first_name", e.target.value)}
                                    required
                                    autoComplete="given-name"
                                />
                                {errors.first_name && (
                                    <p className="text-xs text-destructive">{errors.first_name}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="paternal_last_name">Apellido paterno</Label>
                                <Input
                                    id="paternal_last_name"
                                    value={data.paternal_last_name}
                                    onChange={(e) => setData("paternal_last_name", e.target.value)}
                                    required
                                    autoComplete="family-name"
                                />
                                {errors.paternal_last_name && (
                                    <p className="text-xs text-destructive">{errors.paternal_last_name}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="maternal_last_name">Apellido materno (opcional)</Label>
                                <Input
                                    id="maternal_last_name"
                                    value={data.maternal_last_name}
                                    onChange={(e) => setData("maternal_last_name", e.target.value)}
                                    autoComplete="additional-name"
                                />
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password">Contraseña</Label>
                            <div className="relative">
                                <Input
                                    id="password"
                                    type={showPassword ? "text" : "password"}
                                    value={data.password}
                                    onChange={(e) => setData("password", e.target.value)}
                                    required
                                    autoComplete="new-password"
                                    className="pr-10"
                                />
                                <button
                                    type="button"
                                    className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                    onClick={() => setShowPassword((v) => !v)}
                                    tabIndex={-1}
                                >
                                    {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                </button>
                            </div>
                            {data.password && (
                                <p className={cn("text-xs", passwordStrengthClass(strength.score))}>
                                    Seguridad: {strength.label}
                                </p>
                            )}
                            {errors.password && (
                                <p className="text-xs text-destructive">{errors.password}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="password_confirmation">Confirmar contraseña</Label>
                            <div className="relative">
                                <Input
                                    id="password_confirmation"
                                    type={showConfirm ? "text" : "password"}
                                    value={data.password_confirmation}
                                    onChange={(e) => setData("password_confirmation", e.target.value)}
                                    required
                                    autoComplete="new-password"
                                    className="pr-10"
                                />
                                <button
                                    type="button"
                                    className="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                                    onClick={() => setShowConfirm((v) => !v)}
                                    tabIndex={-1}
                                >
                                    {showConfirm ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                </button>
                            </div>
                        </div>

                        {(errors.token || errors.root) && (
                            <p className="text-sm text-destructive">{errors.token || errors.root}</p>
                        )}

                        <Button type="submit" className={cn("w-full", btnBrand)} disabled={processing}>
                            {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Crear mi cuenta
                        </Button>
                    </form>
                </InvitationCard>
            </AuthSimpleShell>
        </>
    );
}
