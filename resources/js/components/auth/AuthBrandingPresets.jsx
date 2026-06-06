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

export function VerifyEmailBrandingPanel() {
    return (
        <AuthBrandingPanel
            badgeLabel="Verificación"
            title={
                <>
                    Confirma tu
                    <br />
                    correo
                </>
            }
            description="Validamos tu dirección para activar la cuenta y continuar con la configuración de tu empresa."
            bullets={[
                { text: "Enlace de un solo uso con expiración." },
                { text: "Tras verificar, podrás iniciar sesión." },
                { text: "Si expiró, regístrate de nuevo o pide ayuda.", dotClassName: "bg-muted-foreground" },
            ]}
        />
    );
}

export function ForceChangePasswordBrandingPanel() {
    return (
        <AuthBrandingPanel
            badgeLabel="Seguridad"
            title={
                <>
                    Actualiza tu
                    <br />
                    contraseña
                </>
            }
            description="Por política de seguridad debes definir una clave nueva antes de acceder al panel."
            bullets={[
                { text: "Misma política de complejidad que registro." },
                { text: "Tu sesión actual se mantiene activa." },
                { text: "Tras guardar, entrarás al panel principal.", dotClassName: "bg-muted-foreground" },
            ]}
        />
    );
}

export function AcceptInvitationBrandingPanel() {
    return (
        <AuthBrandingPanel
            badgeLabel="Invitación"
            title={
                <>
                    Únete a tu
                    <br />
                    equipo
                </>
            }
            description="Completa tu perfil con la invitación de tu administrador. Tus permisos se asignarán según tu rol."
            bullets={[
                { text: "Invitación personal con fecha de expiración." },
                { text: "Contraseña segura o acceso con Google." },
                { text: "Datos aislados por organización.", dotClassName: "bg-muted-foreground" },
            ]}
        />
    );
}
