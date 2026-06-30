import { useEffect, useMemo, useState } from "react";
import { Head } from "@inertiajs/react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import axios from "@/lib/axios";
import { getApiErrorMessage } from "@/lib/apiErrors";
import { focusFirstFormError } from "@/lib/focusFirstFormError";
import { resetPasswordFormSchema } from "@/lib/passwordSchema";
import { ResetPasswordBrandingPanel } from "@/components/auth/AuthBrandingPresets";
import { AuthInvalidResetLinkCard } from "@/components/auth/AuthInvalidResetLinkCard";
import { AuthBackToLoginLink, AuthFormSection } from "@/components/auth/AuthFormSection";
import { AuthFormAlert } from "@/components/auth/AuthFormAlert";
import { AuthFormField } from "@/components/auth/AuthFormField";
import { AuthPageHeader } from "@/components/auth/AuthPageHeader";
import { AuthSplitLayout } from "@/components/auth/AuthSplitLayout";
import { PasswordField, PasswordMatchHint } from "@/components/auth/PasswordField";
import { PasswordRequirements } from "@/components/auth/PasswordRequirements";
import { btnBrand } from "@/lib/marketingTheme";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Loader2 } from "lucide-react";

const RESET_FIELD_ORDER = ["password", "password_confirmation"];

export default function ResetPassword() {
    const { token, email } = useMemo(() => {
        if (typeof window === "undefined") return { token: "", email: "" };
        const params = new URLSearchParams(window.location.search);
        return {
            token: params.get("token") || "",
            email: params.get("email") || "",
        };
    }, []);

    const [success, setSuccess] = useState("");
    const [serverError, setServerError] = useState("");

    const {
        register,
        handleSubmit,
        watch,
        formState: { errors, isSubmitting },
    } = useForm({
        resolver: zodResolver(resetPasswordFormSchema),
        defaultValues: {
            password: "",
            password_confirmation: "",
        },
        mode: "onBlur",
    });

    const password = watch("password");
    const passwordConfirmation = watch("password_confirmation");
    const loading = isSubmitting;
    const invalidLink = !token || !email;
    const formError = serverError || errors.root?.message;

    useEffect(() => {
        axios.get("/sanctum/csrf-cookie", { withCredentials: true }).catch(() => {});
    }, []);

    const onSubmit = async (values) => {
        setServerError("");
        setSuccess("");

        try {
            await axios.post("/api/password/reset", {
                token,
                email,
                password: values.password,
                password_confirmation: values.password_confirmation,
            });
            setSuccess("Contraseña actualizada. Redirigiendo al inicio de sesión…");
            window.setTimeout(() => {
                window.location.href = "/login";
            }, 1200);
        } catch (err) {
            setServerError(getApiErrorMessage(err, "No se pudo restablecer la contraseña."));
        }
    };

    return (
        <>
            <Head title="Nueva contraseña — Tikara" />
            <AuthSplitLayout
                formClassName="max-w-lg"
                topLink={{
                    prompt: "¿Ya la recuerdas?",
                    href: "/login",
                    label: "Inicia sesión",
                }}
                brandingPanel={<ResetPasswordBrandingPanel />}
            >
                <AuthPageHeader
                    title="Nueva contraseña"
                    description="Define una contraseña segura para tu cuenta."
                />

                {invalidLink ? (
                    <div className="space-y-6">
                        <AuthFormAlert error="El enlace de restablecimiento no es válido o expiró." />
                        <AuthInvalidResetLinkCard />
                        <AuthBackToLoginLink />
                    </div>
                ) : (
                    <form
                        onSubmit={handleSubmit(onSubmit, (fieldErrors) =>
                            focusFirstFormError(fieldErrors, RESET_FIELD_ORDER)
                        )}
                        className="space-y-6"
                        noValidate
                    >
                        <AuthFormSection title="Cuenta">
                            <AuthFormField id="reset-email" label="Correo electrónico">
                                <Input
                                    id="reset-email"
                                    type="email"
                                    value={email}
                                    disabled
                                    readOnly
                                    className="h-11 bg-muted"
                                />
                            </AuthFormField>
                        </AuthFormSection>

                        <AuthFormSection title="Acceso">
                            <PasswordField
                                id="reset-password"
                                label="Contraseña nueva"
                                disabled={loading}
                                error={errors.password?.message}
                                autoComplete="new-password"
                                {...register("password")}
                            />
                            <PasswordRequirements password={password} />

                            <div className="space-y-2">
                                <PasswordField
                                    id="reset-password-confirmation"
                                    label="Confirmar contraseña"
                                    disabled={loading}
                                    error={errors.password_confirmation?.message}
                                    autoComplete="new-password"
                                    {...register("password_confirmation")}
                                />
                                <PasswordMatchHint
                                    password={password}
                                    confirmation={passwordConfirmation}
                                />
                            </div>
                        </AuthFormSection>

                        <AuthFormAlert error={formError} success={success} />

                        <Button
                            type="submit"
                            disabled={loading}
                            className={`h-11 w-full gap-2 rounded-lg ${btnBrand}`}
                        >
                            {loading ? (
                                <Loader2 className="h-4 w-4 animate-spin" aria-hidden />
                            ) : null}
                            <span>{loading ? "Guardando..." : "Restablecer contraseña"}</span>
                        </Button>

                        <AuthBackToLoginLink />
                    </form>
                )}
            </AuthSplitLayout>
        </>
    );
}
