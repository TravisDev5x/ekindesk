/** Rutas explícitas con Inertia::render en web.php (no catch-all SPA). */
const INERTIA_PATH_PREFIXES = [
    "/areas",
    "/priorities",
    "/impact-levels",
    "/urgency-levels",
    "/campaigns",
    "/positions",
    "/roles",
    "/sessions",
    "/sedes",
    "/ubicaciones",
    "/ticket-macros",
    "/priority-matrix",
    "/permissions",
    "/audit-command",
    "/calendario",
    "/profile",
    "/users",
    "/settings",
    "/clients",
    "/company",
    "/resolbeb",
    "/tickets/wallboard",
    "/resolbeb/estados",
    "/resolbeb/tipos",
    "/resolbeb/tickets/new",
    "/resolbeb/tickets/",
    "/resolbeb/tickets",
    "/resolbeb/mis-tickets",
    "/onboarding",
    "/force-change-password",
    "/login",
    "/register",
    "/forgot-password",
    "/reset-password",
    "/verify-email",
];

export function isExternalUrl(url) {
    if (!url || typeof url !== "string") return false;
    return (
        url.startsWith("http://") ||
        url.startsWith("https://") ||
        url.startsWith("mailto:")
    );
}

export function shouldUseInertiaLink(url, anchorLinks) {
    if (!anchorLinks || !url || isExternalUrl(url)) return false;
    return INERTIA_PATH_PREFIXES.some(
        (prefix) => url === prefix || url.startsWith(`${prefix}/`)
    );
}

/** className puede ser string (Sidebar) o función isActive => string (NavLink). */
export function resolveNavClassName(className, isActive) {
    if (typeof className === "function") return className(isActive);
    return className ?? "";
}
