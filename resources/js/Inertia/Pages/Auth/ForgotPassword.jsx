import { useEffect, useRef, useState } from "react";
import { Head, Link } from "@inertiajs/react";
import axios from "@/lib/axios";
import { getApiErrorMessage } from "@/lib/apiErrors";
import { ForgotPasswordBrandingPanel } from "@/components/auth/AuthBrandingPresets";
import { AuthFormAlert } from "@/components/auth/AuthFormAlert";
import { AuthFormField } from "@/components/auth/AuthFormField";
import { AuthPageHeader } from "@/components/auth/AuthPageHeader";
import { AuthSplitLayout } from "@/components/auth/AuthSplitLayout";
import { btnBrand, linkBrand } from "@/lib/marketingTheme";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Loader2 } from "lucide-react";

export default function ForgotPassword() {
    const sitekey = import.meta.env.VITE_HCAPTCHA_SITEKEY;
    const widgetId = useRef(null);
    const captchaElRef = useRef(null);
    const identifierRef = useRef(null);
    const [captchaToken, setCaptchaToken] = useState("");
    const [identifier, setIdentifier] = useState("");
    const [message, setMessage] = useState("");
    const [error, setError] = useState("");
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        axios.get("/sanctum/csrf-cookie", { withCredentials: true }).catch(() => {});
    }, []);

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

        return () => window.clearInterval(timer);
    }, [sitekey]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError("");
        setMessage("");

        if (!identifier.trim()) {
            setError("Indica tu correo o número de empleado.");
            identifierRef.current?.focus();
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
            setMessage(
                "Si el correo o número de empleado está registrado, recibirás un enlace por correo o un administrador atenderá tu solicitud y se comunicará contigo por medios empresariales."
            );
        } catch (err) {
            const retry = err?.response?.headers?.["retry-after"];
            if (err?.response?.status === 429 && retry) {
                setError(`Demasiados intentos. Espera ${retry} segundos.`);
            } else {
                setError(getApiErrorMessage(err, "No se pudo enviar la solicitud. Intenta más tarde."));
            }
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Head title="Restablecer contraseña — EkinDesk" />
            <AuthSplitLayout
                topLink={{
                    prompt: "¿Recordaste tu clave?",
                    href: "/login",
                    label: "Inicia sesión",
                }}
                brandingPanel={<ForgotPasswordBrandingPanel />}
            >
                <AuthPageHeader
                    title="Restablecer contraseña"
                    description="Indica tu correo o número de empleado. Te guiaremos para recuperar el acceso."
                />

                <form onSubmit={handleSubmit} className="space-y-4" noValidate>
                    <AuthFormField
                        id="forgot-identifier"
                        label="Correo o número de empleado"
                        hint="Si no tienes correo institucional, escribe tu número de empleado. Un administrador puede restablecer tu contraseña por WhatsApp empresarial, teléfono o en persona."
                    >
                        <Input
                            ref={identifierRef}
                            id="forgot-identifier"
                            name="identifier"
                            type="text"
                            value={identifier}
                            onChange={(e) => setIdentifier(e.target.value)}
                            placeholder="correo@empresa.com o 12345"
                            autoComplete="username"
                            disabled={loading}
                            className="h-11"
                            aria-invalid={Boolean(error)}
                        />
                    </AuthFormField>

                    {sitekey ? (
                        <div className="rounded-md border border-border/60 bg-muted/30 p-3">
                            <div className="h-captcha" ref={captchaElRef} />
                        </div>
                    ) : null}

                    <AuthFormAlert error={error} success={message} />

                    <Button
                        type="submit"
                        disabled={loading}
                        className={`h-11 w-full gap-2 rounded-lg ${btnBrand}`}
                    >
                        {loading ? <Loader2 className="h-4 w-4 animate-spin" aria-hidden /> : null}
                        <span>{loading ? "Enviando..." : "Enviar solicitud"}</span>
                    </Button>

                    <Link
                        href="/login"
                        className={`${linkBrand} inline-flex h-11 w-full items-center justify-center text-sm`}
                    >
                        Volver al inicio de sesión
                    </Link>
                </form>
            </AuthSplitLayout>
        </>
    );
}
