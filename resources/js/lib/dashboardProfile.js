/**
 * Perfil de dashboard según rol Spatie + permisos de tickets.
 *
 * - solicitante: usuario normal (mis tickets, respuestas, calendario)
 * - soporte: L1/L2/L3 y rol soporte (cola del área / solicitantes)
 * - supervisor: gerente, supervisor (todos los tickets + quién atiende)
 * - admin: administración completa
 */
export function userHasRole(user, roleNames) {
    const names = Array.isArray(roleNames) ? roleNames : [roleNames];
    const roles = user?.roles ?? [];
    return roles.some((r) => {
        const key = typeof r === "string" ? r : r?.name ?? r?.slug ?? "";
        return names.includes(key);
    });
}

export function getDashboardProfile(user, can) {
    const canFn = typeof can === "function" ? can : () => false;

    if (canFn("users.manage") || userHasRole(user, ["admin"])) {
        return "admin";
    }

    if (
        canFn("tickets.manage_all") ||
        userHasRole(user, ["gerente", "supervisor"])
    ) {
        return "supervisor";
    }

    if (
        canFn("tickets.view_area") ||
        userHasRole(user, ["soporte", "soporte_n1", "soporte_n2", "soporte_n3", "consultor"])
    ) {
        return "soporte";
    }

    if (canFn("tickets.view_own") || canFn("tickets.create")) {
        return "solicitante";
    }

    if (canFn("tickets.manage_all")) {
        return "admin";
    }

    return "solicitante";
}

export function dashboardProfileLabel(profile) {
    const labels = {
        solicitante: "Solicitante",
        soporte: "Soporte",
        supervisor: "Supervisión",
        admin: "Administración",
    };
    return labels[profile] ?? profile;
}
