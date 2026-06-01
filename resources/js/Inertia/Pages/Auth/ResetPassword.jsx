import { useMemo, useState } from "react";
import { Head, Link } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import axios from "@/lib/axios";
import { strongPasswordSchema } from "@/lib/passwordSchema";
import { AuthHeroShell } from "@/components/auth/AuthHeroShell";
import { authHeroCard, authLinkGhost, authMessageError, authMessageSuccess } from "@/lib/marketingTheme";

export default function ResetPassword() {
    const { token, email } = useMemo(() => {
        if (typeof window === "undefined") return { token: "", email: "" };
        const params = new URLSearchParams(window.location.search);
        return {
            token: params.get("token") || "",
            email: params.get("email") || "",
        };
    }, []);

    const [password, setPassword] = useState("");
    const [confirm, setConfirm] = useState("");
    const [message, setMessage] = useState("");
    const [error, setError] = useState("");
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError("");
        setMessage("");

        if (!password || !confirm) {
            setError("La contraseña y confirmación son obligatorias.");
            return;
        }

        const pwdCheck = strongPasswordSchema.safeParse(password);
        if (!pwdCheck.success) {
            const first = pwdCheck.error?.issues?.[0] ?? pwdCheck.error?.errors?.[0];
            setError(first?.message ?? "Revisa la contraseña.");
            return;
        }

        if (password !== confirm) {
            setError("Las contraseñas no coinciden.");
            return;
        }

        setLoading(true);
        try {
            await axios.post("/api/password/reset", {
                token,
                email,
                password,
                password_confirmation: confirm,
            });
            setMessage("Contraseña actualizada, puedes iniciar sesión.");
            setTimeout(() => {
                window.location.href = "/login";
            }, 1200);
        } catch (err) {
            setError(err?.response?.data?.message || "No se pudo restablecer la contraseña.");
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Head title="Nueva contraseña" />
            <AuthHeroShell>
                <Card className={`${authHeroCard} flex-shrink-0`}>
                    <CardHeader>
                        <CardTitle className="text-center">Nueva contraseña</CardTitle>
                    </CardHeader>
                    <CardContent className="pb-20 md:pb-6">
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label>Correo</Label>
                                <Input type="email" value={email} disabled />
                            </div>
                            <div className="space-y-2">
                                <Label>Contraseña nueva</Label>
                                <Input
                                    type="password"
                                    value={password}
                                    onChange={(e) => setPassword(e.target.value)}
                                    disabled={loading}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Confirmar contraseña</Label>
                                <Input
                                    type="password"
                                    value={confirm}
                                    onChange={(e) => setConfirm(e.target.value)}
                                    disabled={loading}
                                />
                            </div>

                            {message ? <p className={authMessageSuccess}>{message}</p> : null}
                            {error ? <p className={authMessageError}>{error}</p> : null}

                            <Button
                                type="submit"
                                className="w-full min-h-[44px] md:min-h-0"
                                disabled={loading || !token || !email}
                            >
                                {loading ? "Guardando..." : "Restablecer"}
                            </Button>
                            <Link href="/login" className={authLinkGhost} aria-disabled={loading}>
                                Volver al inicio de sesión
                            </Link>
                        </form>
                    </CardContent>
                </Card>
            </AuthHeroShell>
        </>
    );
}
