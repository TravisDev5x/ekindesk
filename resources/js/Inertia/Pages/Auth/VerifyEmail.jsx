import { useEffect, useMemo, useState } from "react";
import { Head, Link } from "@inertiajs/react";
import axios from "@/lib/axios";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { AuthSimpleShell } from "@/components/auth/AuthSimpleShell";
import { authSimpleCard } from "@/lib/marketingTheme";
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
    const title = isSuccess ? "Correo verificado" : isError ? "No se pudo verificar" : "Verificando correo";

    const statusBadgeClass = isSuccess
        ? badgeStatus.success
        : isError
          ? badgeStatus.danger
          : "border-border/60 bg-muted/50 text-muted-foreground border";

    return (
        <>
            <Head title="Verificar correo" />
            <AuthSimpleShell maxWidth="max-w-[440px]">
                <Card className={authSimpleCard}>
                    <CardHeader className="space-y-3">
                        <Badge variant="outline" className={cn("w-fit text-xs", statusBadgeClass)}>
                            {isSuccess ? "Verificación completa" : isError ? "Verificación fallida" : "Verificando"}
                        </Badge>
                        <CardTitle className="text-center">{title}</CardTitle>
                        <CardDescription className="text-center">{message}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm text-muted-foreground">
                        <p>
                            Si ya verificaste tu correo, puedes iniciar sesión con tus credenciales.
                        </p>
                        {isError ? (
                            <p>
                                El enlace puede haber expirado. Puedes intentar registrarte de nuevo o solicitar ayuda al administrador.
                            </p>
                        ) : null}
                    </CardContent>
                    <CardFooter className="flex flex-col gap-2">
                        <Button asChild className="w-full">
                            <Link href="/login">Ir a iniciar sesión</Link>
                        </Button>
                        <Button asChild variant="outline" className="w-full">
                            <Link href="/register">Crear cuenta</Link>
                        </Button>
                    </CardFooter>
                </Card>
            </AuthSimpleShell>
        </>
    );
}
