import { useEffect, useRef, useState } from "react";
import { Head, Link } from "@inertiajs/react";
import axios from "@/lib/axios";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
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
            <div className="flex min-h-[100dvh] flex-col items-center justify-center text-foreground relative px-4 py-6 pb-[max(2rem,calc(2rem+env(safe-area-inset-bottom)))] overflow-y-auto md:min-h-screen md:h-screen md:overflow-hidden md:pb-6">
                <div
                    className="absolute inset-0 z-0 bg-cover bg-center bg-no-repeat"
                    style={{ backgroundImage: "url(/images/auth-hero.png)" }}
                    aria-hidden
                />
                <div
                    className="absolute inset-0 z-[1] pointer-events-none backdrop-blur-[6px] sm:backdrop-blur-[8px] bg-background/40 dark:bg-background/50"
                    aria-hidden
                />

                <Card className="relative z-10 w-full max-w-[420px] shadow-2xl border-border/80 bg-card/90 dark:bg-card/85 backdrop-blur-md flex-shrink-0 my-auto md:my-0 pb-[max(1rem,env(safe-area-inset-bottom))] md:pb-6">
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
                                    className="focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-transparent md:focus-visible:ring-primary/50"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Si no tienes correo institucional, escribe tu número de empleado. Un administrador restablecerá tu contraseña y se comunicará contigo por WhatsApp empresarial, teléfono o personalmente.
                                </p>
                            </div>

                            {sitekey && (
                                <div className="border border-border/60 rounded-md p-3">
                                    <div
                                        className="h-captcha"
                                        ref={captchaElRef}
                                    />
                                </div>
                            )}

                            {message ? <p className="text-green-600 text-sm">{message}</p> : null}
                            {error ? <p className="text-red-500 text-sm">{error}</p> : null}

                            <Button type="submit" className="w-full min-h-[44px] md:min-h-0" disabled={loading}>
                                {loading ? "Enviando..." : "Enviar solicitud"}
                            </Button>
                            <Link
                                href="/login"
                                className="inline-flex w-full items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 hover:bg-accent hover:text-accent-foreground min-h-[44px] md:min-h-0"
                                aria-disabled={loading}
                            >
                                Volver
                            </Link>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
