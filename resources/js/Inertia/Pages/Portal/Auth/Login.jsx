// Portal login reutiliza la misma página de Auth/Login.
// El prop `tenant.mode === "client_portal"` activa el branding del tenant
// y redirige al usuario a "/" (portal dashboard) tras iniciar sesión.
export { default } from "@/Inertia/Pages/Auth/Login";
