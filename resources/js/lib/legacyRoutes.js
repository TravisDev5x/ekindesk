/**
 * Mapa de rutas legacy del SPA anterior → rutas canónicas Inertia.
 * Usado por Sidebar/MobileBottomBar para visitas client-side sin 404.
 */

const LEGACY_PATH_MAP = {
    "/app": "/home",
    "/inicio": "/home",
    "/dashboard": "/home",
    "/resolvev1": "/resolbeb",
    "/resolvev1/dashboard": "/resolbeb",
    "/resolvev1/tickets": "/resolbeb/tickets",
    "/resolvev1/tickets/new": "/resolbeb/tickets/new",
    "/resolvev1/mis-tickets": "/resolbeb/mis-tickets",
    "/resolvev1/estados": "/resolbeb/estados",
    "/resolvev1/tipos": "/resolbeb/tipos",
    "/resolbeb/resolvev1": "/resolbeb",
    "/resolbeb/resolvev1/dashboard": "/resolbeb",
    "/resolbeb/resolvev1/tickets": "/resolbeb/tickets",
    "/resolbeb/resolvev1/tickets/new": "/resolbeb/tickets/new",
    "/resolbeb/resolvev1/mis-tickets": "/resolbeb/mis-tickets",
    "/resolbeb/resolvev1/estados": "/resolbeb/estados",
    "/resolbeb/resolvev1/tipos": "/resolbeb/tipos",
    "/tickets": "/resolbeb/tickets",
    "/tickets/new": "/resolbeb/tickets/new",
    "/mis-tickets": "/resolbeb/mis-tickets",
    "/ticket-states": "/resolbeb/estados",
    "/ticket-types": "/resolbeb/tipos",
    "/ticket-estados": "/resolbeb/estados",
    "/ticket-tipos": "/resolbeb/tipos",
    "/incidentes": "/incidents",
    "/audit-command-center": "/audit-command",
    "/audit": "/audit-command",
    "/configuracion": "/settings",
    "/usuarios": "/users",
    "/invitaciones": "/users/invitations",
    "/prioridades": "/priorities",
    "/clientes": "/clients",
};

const LEGACY_PREFIX_REWRITES = [
    [/^\/resolvev1\/tickets\/(\d+)/, "/resolbeb/tickets/$1"],
    [/^\/resolbeb\/resolvev1\/tickets\/(\d+)/, "/resolbeb/tickets/$1"],
    [/^\/tickets\/(\d+)/, "/resolbeb/tickets/$1"],
    [/^\/incidentes\/(\d+)/, "/incidents/$1"],
];

/**
 * @param {string} url Path o URL pathname (sin query)
 * @returns {string}
 */
export function normalizeLegacyAppPath(url) {
    if (!url || typeof url !== "string") return url;
    let path = url;
    try {
        if (url.startsWith("http://") || url.startsWith("https://")) {
            path = new URL(url).pathname;
        }
    } catch {
        return url;
    }

    const exact = LEGACY_PATH_MAP[path];
    if (exact) return exact;

    for (const [pattern, replacement] of LEGACY_PREFIX_REWRITES) {
        if (pattern.test(path)) {
            return path.replace(pattern, replacement);
        }
    }

    return path;
}
