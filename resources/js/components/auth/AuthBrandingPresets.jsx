import { AuthBrandingPanel } from "@/components/auth/AuthBrandingPanel";

export function RegisterBrandingPanel() {
    return (
        <AuthBrandingPanel
            badgeLabel="Registro MSP"
            title={
                <>
                    Crea tu cuenta
                    <br />
                    en minutos
                </>
            }
            description="Registra tu empresa de soporte, verifica tu correo y configura tu primer cliente. Pensado para equipos MSP que quieren operar con orden desde el día uno."
            bullets={[
                { text: "Registro en 3 minutos · Sin tarjeta de crédito." },
                { text: "Verificación por correo antes de activar la cuenta." },
                {
                    text: "Datos aislados por cliente desde el inicio.",
                    dotClassName: "bg-muted-foreground",
                },
            ]}
        />
    );
}

export function ForgotPasswordBrandingPanel() {
    return (
        <AuthBrandingPanel
            badgeLabel="Recuperación segura"
            title={
                <>
                    Recupera tu
                    <br />
                    acceso
                </>
            }
            description="Te enviaremos instrucciones al correo registrado o canal empresarial si usas número de empleado."
            bullets={[
                { text: "Enlace temporal con expiración." },
                { text: "Sin cambios en tus datos de tenant." },
                { text: "Soporte humano si no tienes correo institucional.", dotClassName: "bg-muted-foreground" },
            ]}
        />
    );
}

export function ResetPasswordBrandingPanel() {
    return (
        <AuthBrandingPanel
            badgeLabel="Nueva contraseña"
            title={
                <>
                    Define tu
                    <br />
                    clave
                </>
            }
            description="Elige una contraseña fuerte. La misma política de seguridad aplica en login y registro."
            bullets={[
                { text: "Mínimo 12 caracteres con complejidad." },
                { text: "Sesiones anteriores pueden invalidarse." },
                { text: "Tras guardar, inicia sesión de nuevo.", dotClassName: "bg-muted-foreground" },
            ]}
        />
    );
}
