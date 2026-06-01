import { useEffect, useRef, useState } from "react";
import { Head, Link } from "@inertiajs/react";
import axios from "@/lib/axios";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { AuthHeroShell } from "@/components/auth/AuthHeroShell";
import { authHeroCard, authLinkGhost, authMessageError, authMessageSuccess } from "@/lib/marketingTheme";

export default function ForgotPassword() {
    const sitekey = import.meta.env.VITE_HCAPTCHA_SITEKEY;
    const widgetId = useRef(null);
    const captchaElRef = useRef(null);
    const [captchaToken, setCaptchaToken] = useState("");
    const [identifier, setIdentifier] = useState("");
    const [message, setMessage] = useState("");
    const [error, setError] = useState("");
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!sitekey) return;
        const scriptId = "hcaptcha-script";
        if (!document.getElementById(scriptId)) {
            const s = document.createElement("script");
            s.id = scriptId;
            s.src = "https://js.hcaptcha.com/1/api.js?render=explicit";
            s.async = true;
            document.body.appendChild(s);
        }

        const timer = window.setInterval(() => {
            if (!captchaElRef.current || !window.hcaptcha || widgetId.current) return;
            widgetId.current = window.hcaptcha.render(captchaElRef.current, {
                sitekey,
                callback: (token) => setCaptchaToken(token),
                "error-callback": () => setCaptchaToken(""),
                "expired-callback": () => setCaptchaToken(""),
            });
        }, 150);

        return () => {
            window.clearInterval(timer);
        };
    }, [sitekey]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError("");
        setMessage("");
        if (!identifier.trim()) {
            setError("Indica tu correo o número de empleado.");
            return;
        }
        if (sitekey && !captchaToken) {
            setError("Completa el captcha.");
            return;
        }
        setLoading(true);
        try {
            await axios.post("/api/password/forgot", {
                identifier: identifier.trim(),
                hcaptcha_token: captchaToken,
            });
            setMessage("Si el correo o número de empleado está registrado, recibirás un enlace por correo o un administrador atenderá tu solicitud y se comunicará contigo por medios empresariales.");
        } catch (err) {
            const retry = err?.response?.headers?.["retry-after"];
            if (err?.response?.status === 429 && retry) {
                setError(`Demasiados intentos. Espera ${retry} segundos.`);
            } else {
                setError(err?.response?.data?.message || "No se pudo enviar la solicitud. Intenta más tarde.");
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Head title="Restablecer contraseña" />
            <AuthHeroShell>
                <Card
                    className={`${authHeroCard} flex-shrink-0 pb-[max(1rem,env(safe-area-inset-bottom))] md:pb-6`}
                >
                    <CardHeader>
                        <CardTitle className="text-center">Restablecer contraseña</CardTitle>
                    </CardHeader>
                    <CardContent className="pb-20 md:pb-6">
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="forgot-identifier">Correo o número de empleado</Label>
                                <Input
                                    id="forgot-identifier"
                                    type="text"
                                    value={identifier}
                                    onChange={(e) => setIdentifier(e.target.value)}
                                    placeholder="Ej: correo@empresa.com o 12345"
                                    autoComplete="username"
                                    disabled={loading}
                                />
                                <p className="text-xs text-muted-foreground">
                                    Si no tienes correo institucional, escribe tu número de empleado. Un administrador restablecerá tu contraseña y se comunicará contigo por WhatsApp empresarial, teléfono o personalmente.
                                </p>
                            </div>

                            {sitekey && (
                                <div className="border border-border/60 rounded-md p-3 bg-muted/30">
                                    <div className="h-captcha" ref={captchaElRef} />
                                </div>
                            )}

                            {message ? <p className={authMessageSuccess}>{message}</p> : null}
                            {error ? <p className={authMessageError}>{error}</p> : null}

                            <Button type="submit" className="w-full min-h-[44px] md:min-h-0" disabled={loading}>
                                {loading ? "Enviando..." : "Enviar solicitud"}
                            </Button>
                            <Link
                                href="/login"
                                className={authLinkGhost}
                                aria-disabled={loading}
                            >
                                Volver
                            </Link>
                        </form>
                    </CardContent>
                </Card>
            </AuthHeroShell>
        </>
    );
}
