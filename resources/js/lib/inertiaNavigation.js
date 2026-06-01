/** Rutas con Inertia::render en routes/web.php. */
const INERTIA_PATH_PREFIXES = [
    "/home",
    "/manual",
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
    "/incidents",
    "/incidents/",
    "/incident-types",
    "/incident-severities",
    "/incident-statuses",
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

export function shouldUseInertiaLink(url) {
    if (!url || isExternalUrl(url)) return false;
    return INERTIA_PATH_PREFIXES.some(
        (prefix) => url === prefix || url.startsWith(`${prefix}/`)
    );
}

/** className puede ser string o función isActive => string. */
export function resolveNavClassName(className, isActive) {
    if (typeof className === "function") return className(isActive);
    return className ?? "";
}
