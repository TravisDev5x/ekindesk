import { useState } from "react";
import { useSearchParams, useNavigate } from "react-router-dom";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import axios from "@/lib/axios";
import { strongPasswordSchema } from "@/lib/passwordSchema";
export default function ResetPassword() {
    const [params] = useSearchParams();
    const navigate = useNavigate();
    const token = params.get("token") || "";
    const email = params.get("email") || "";
    const [password, setPassword] = useState("");
    const [confirm, setConfirm] = useState("");
    const [message, setMessage] = useState("");
    const [error, setError] = useState("");
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError("");
        setMessage("");

        if (!password || !confirm) return setError("La contraseña y confirmación son obligatorias.");
        const pwdCheck = strongPasswordSchema.safeParse(password);
        if (!pwdCheck.success) {
            const first = pwdCheck.error?.issues?.[0] ?? pwdCheck.error?.errors?.[0];
            return setError(first?.message ?? "Revisa la contraseña.");
        }
        if (password !== confirm) return setError("Las contraseñas no coinciden.");

        setLoading(true);
        try {
            await axios.post("/api/password/reset", {
                token,
                email,
                password,
                password_confirmation: confirm,
            });
            setMessage("Contraseña actualizada, puedes iniciar sesión.");
            setTimeout(() => navigate("/login"), 1200);
        } catch (err) {
            setError(err?.response?.data?.message || "No se pudo restablecer la contraseña.");
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="flex min-h-[100dvh] flex-col items-center justify-center text-foreground relative px-4 py-6 pb-[max(2rem,calc(2rem+env(safe-area-inset-bottom)))] overflow-y-auto md:h-screen md:overflow-hidden md:pb-6">
            {/* Fondo: imagen a pantalla completa (fixed para que siempre cubra el viewport) */}
            <div
                className="fixed inset-0 z-0 bg-cover bg-center bg-no-repeat bg-muted"
                style={{ backgroundImage: "url(/images/auth-hero.png)" }}
                aria-hidden
            />
            {/* Capa mica: blur suave + tinte */}
            <div
                className="fixed inset-0 z-[1] pointer-events-none backdrop-blur-[6px] sm:backdrop-blur-[8px] bg-background/40 dark:bg-background/50"
                aria-hidden
            />
            <Card className="relative z-10 w-full max-w-[420px] shadow-2xl border-border/80 bg-card/80 dark:bg-card/70 backdrop-blur-md flex-shrink-0">
                <CardHeader>
                    <CardTitle className="text-center">Nueva contraseña</CardTitle>
                </CardHeader>
                <CardContent className="pb-20 md:pb-6">
                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div className="space-y-2">
                            <Label>Correo</Label>
                            <Input type="email" value={email} disabled className="focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-transparent md:focus-visible:ring-primary/50" />
                        </div>
                        <div className="space-y-2">
                            <Label>Contraseña nueva</Label>
                            <Input type="password" value={password} onChange={(e) => setPassword(e.target.value)} disabled={loading} className="focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-transparent md:focus-visible:ring-primary/50" />
                        </div>
                        <div className="space-y-2">
                            <Label>Confirmar contraseña</Label>
                            <Input type="password" value={confirm} onChange={(e) => setConfirm(e.target.value)} disabled={loading} className="focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-transparent md:focus-visible:ring-primary/50" />
                        </div>
                        {message && <p className="text-green-600 text-sm">{message}</p>}
                        {error && <p className="text-red-500 text-sm">{error}</p>}
                        <Button type="submit" className="w-full min-h-[44px] md:min-h-0" disabled={loading || !token || !email}>
                            {loading ? "Guardando..." : "Restablecer"}
                        </Button>
                        <Button type="button" variant="ghost" className="w-full min-h-[44px] md:min-h-0" onClick={() => navigate("/login")} disabled={loading}>
                            Volver al inicio de sesión
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}
