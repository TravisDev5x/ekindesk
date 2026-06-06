import { useState } from "react";
import { Head, usePage } from "@inertiajs/react";
import axios from "@/lib/axios";
import { strongPasswordSchema } from "@/lib/passwordSchema";
import { ForceChangePasswordBrandingPanel } from "@/components/auth/AuthBrandingPresets";
import { AuthFormAlert } from "@/components/auth/AuthFormAlert";
import { AuthFormSection } from "@/components/auth/AuthFormSection";
import { AuthPageHeader } from "@/components/auth/AuthPageHeader";
import { AuthSplitLayout } from "@/components/auth/AuthSplitLayout";
import { PasswordField, PasswordMatchHint } from "@/components/auth/PasswordField";
import { PasswordRequirements } from "@/components/auth/PasswordRequirements";
import { btnBrand } from "@/lib/marketingTheme";
import { Button } from "@/components/ui/button";
import { Loader2 } from "lucide-react";

export default function ForceChangePassword() {
    const { auth } = usePage().props;
    const user = auth?.user;
    const [current, setCurrent] = useState("");
    const [password, setPassword] = useState("");
    const [confirm, setConfirm] = useState("");
    const [error, setError] = useState("");
    const [message, setMessage] = useState("");
    const [loading, setLoading] = useState(false);

    const submit = async (e) => {
        e.preventDefault();
        setError("");
        setMessage("");
        if (!current || !password || !confirm) {
            setError("Todos los campos son obligatorios.");
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
            await axios.put("/api/profile/password", {
                current_password: current,
                password,
                password_confirmation: confirm,
            });
            setMessage("Contraseña actualizada. Redirigiendo...");
            setTimeout(() => {
                window.location.href = "/";
            }, 1000);
        } catch (err) {
            setError(err?.response?.data?.message || "No se pudo actualizar la contraseña.");
        } finally {
            setLoading(false);
        }
    };

    const description = user?.name
        ? `${user.name}, por seguridad debes cambiar la contraseña antes de continuar.`
        : "Por seguridad debes cambiar la contraseña antes de continuar.";

    return (
        <>
            <Head title="Actualizar contraseña" />
            <AuthSplitLayout
                formClassName="max-w-lg"
                brandingPanel={<ForceChangePasswordBrandingPanel />}
            >
                <AuthPageHeader title="Actualiza tu contraseña" description={description} />

                <form onSubmit={submit} className="space-y-6" noValidate>
                    <AuthFormSection title="Seguridad">
                        <PasswordField
                            id="force-current-password"
                            label="Contraseña actual"
                            autoComplete="current-password"
                            disabled={loading}
                            value={current}
                            onChange={(e) => setCurrent(e.target.value)}
                        />

                        <PasswordField
                            id="force-new-password"
                            label="Nueva contraseña"
                            autoComplete="new-password"
                            disabled={loading}
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                        />
                        <PasswordRequirements password={password} />

                        <div className="space-y-2">
                            <PasswordField
                                id="force-confirm-password"
                                label="Confirmar contraseña"
                                autoComplete="new-password"
                                disabled={loading}
                                value={confirm}
                                onChange={(e) => setConfirm(e.target.value)}
                            />
                            <PasswordMatchHint password={password} confirmation={confirm} />
                        </div>
                    </AuthFormSection>

                    <AuthFormAlert error={error} success={message} />

                    <Button
                        type="submit"
                        className={`h-11 w-full gap-2 rounded-lg ${btnBrand}`}
                        disabled={loading}
                    >
                        {loading ? <Loader2 className="h-4 w-4 animate-spin" aria-hidden /> : null}
                        <span>{loading ? "Guardando..." : "Guardar y continuar"}</span>
                    </Button>
                </form>
            </AuthSplitLayout>
        </>
    );
}
