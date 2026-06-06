import { useEffect, useMemo, useState } from "react";
import { Head, Link } from "@inertiajs/react";
import axios from "@/lib/axios";
import { VerifyEmailBrandingPanel } from "@/components/auth/AuthBrandingPresets";
import { AuthPageHeader } from "@/components/auth/AuthPageHeader";
import { AuthSplitLayout } from "@/components/auth/AuthSplitLayout";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { btnBrand, btnBrandOutline } from "@/lib/marketingTheme";
import { badgeStatus } from "@/lib/badgeStyles";
import { cn } from "@/lib/utils";

const STATUS = {
    idle: "idle",
    loading: "loading",
    success: "success",
    error: "error",
};

export default function VerifyEmail() {
    const token = useMemo(() => {
        if (typeof window === "undefined") return "";
        return (new URLSearchParams(window.location.search).get("token") || "").trim();
    }, []);
    const [status, setStatus] = useState(STATUS.loading);
    const [message, setMessage] = useState("Validando tu correo...");

    useEffect(() => {
        let active = true;
        if (!token) {
            setStatus(STATUS.error);
            setMessage("El enlace de verificación no es válido.");
            return () => {};
        }

        setStatus(STATUS.loading);
        setMessage("Validando tu correo...");

        axios
            .get("/api/register/verify", { params: { token } })
            .then((res) => {
                if (!active) return;
                setStatus(STATUS.success);
                setMessage(res?.data?.message || "Correo verificado. Ya puedes iniciar sesión.");
                if (res?.data?.onboarding_redirect) {
                    window.location.href = res.data.onboarding_redirect;
                }
            })
            .catch((err) => {
                if (!active) return;
                const serverMessage = err?.response?.data?.message;
                setStatus(STATUS.error);
                setMessage(serverMessage || "No se pudo verificar el correo.");
            });

        return () => {
            active = false;
        };
    }, [token]);

    const isSuccess = status === STATUS.success;
    const isError = status === STATUS.error;
    const isLoading = status === STATUS.loading;
    const title = isSuccess
        ? "Correo verificado"
        : isError
          ? "No se pudo verificar"
          : "Verificando correo";

    const statusBadgeClass = isSuccess
        ? badgeStatus.success
        : isError
          ? badgeStatus.danger
          : "border-border/60 bg-muted/50 text-muted-foreground border";

    return (
        <>
            <Head title="Verificar correo" />
            <AuthSplitLayout
                topLink={{
                    prompt: "¿Ya verificaste?",
                    href: "/login",
                    label: "Inicia sesión",
                }}
                brandingPanel={<VerifyEmailBrandingPanel />}
            >
                <AuthPageHeader title={title} description={message}>
                    <Badge
                        variant="outline"
                        className={cn("mt-3 w-fit text-xs", statusBadgeClass)}
                    >
                        {isSuccess
                            ? "Verificación completa"
                            : isError
                              ? "Verificación fallida"
                              : "Verificando"}
                    </Badge>
                </AuthPageHeader>

                <div className="space-y-6">
                    <p className="text-sm text-muted-foreground">
                        Si ya verificaste tu correo, puedes iniciar sesión con tus credenciales.
                    </p>

                    {isError ? (
                        <p className="text-sm text-muted-foreground">
                            El enlace puede haber expirado. Puedes intentar registrarte de nuevo o
                            solicitar ayuda al administrador.
                        </p>
                    ) : null}

                    <div className="flex flex-col gap-2">
                        <Button asChild className={`h-11 w-full rounded-lg ${btnBrand}`}>
                            <Link href="/login">Ir a iniciar sesión</Link>
                        </Button>
                        <Button
                            asChild
                            variant="outline"
                            className={`h-11 w-full rounded-lg ${btnBrandOutline}`}
                            disabled={isLoading}
                        >
                            <Link href="/register">Crear cuenta</Link>
                        </Button>
                    </div>
                </div>
            </AuthSplitLayout>
        </>
    );
}
