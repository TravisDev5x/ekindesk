/**
 * Clases Badge y superficies KPI (tokens shadcn + semántica dark:).
 */

export const badgeNeutral =
    "border-border/60 bg-muted/50 text-muted-foreground";

export const badgeNeutralInteractive =
    "border-border/60 bg-muted/50 text-muted-foreground hover:bg-muted/60";

const withBorder = (cls) => `${cls} border`;

export const badgeStatus = {
    neutral: badgeNeutralInteractive,
    primary: withBorder("border-primary/30 bg-primary/10 text-primary hover:bg-primary/15"),
    success: withBorder(
        "border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400"
    ),
    warning: withBorder(
        "border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-400"
    ),
    danger: withBorder("border-destructive/30 bg-destructive/10 text-destructive"),
    info: withBorder("border-blue-500/20 bg-blue-500/10 text-blue-700 dark:text-blue-400"),
    brand: withBorder("border-brand/30 bg-brand/10 text-brand-muted"),
};

export const badgeStatusLegacyNeutral = badgeNeutral;

/** Prioridad ticket por level (1 = más urgente). */
export function priorityClassByLevel(level) {
    const l = Number(level);
    if (l === 1) return badgeStatus.danger;
    if (l === 2) return badgeStatus.warning;
    if (l === 3) return badgeStatus.info;
    return withBorder(badgeNeutral);
}

/** Prioridad ticket por nombre (detalle / legacy). */
export function priorityClassByName(name) {
    if (!name) return withBorder(badgeNeutral);
    const n = String(name).toLowerCase();
    if (n.includes("crític") || n.includes("critic") || n.includes("alta")) return badgeStatus.danger;
    if (n.includes("media")) return badgeStatus.warning;
    if (n.includes("baja")) return badgeStatus.info;
    return withBorder(badgeNeutral);
}

/** Estado ticket por code. */
export function ticketStateClassByCode(code) {
    const c = String(code || "").toLowerCase();
    if (["abierto", "en_progreso", "asignado"].includes(c)) return badgeStatus.info;
    if (["resuelto", "cerrado"].includes(c)) return badgeStatus.success;
    if (c.includes("cancel") || c.includes("rechaz")) return badgeStatus.danger;
    return withBorder(badgeNeutral);
}

/** Estado ticket por nombre (detalle). */
export function ticketStateClassByName(name) {
    if (!name) return withBorder(badgeNeutral);
    const n = String(name).toLowerCase();
    if (n.includes("resuelto") || n.includes("cerrado") || n.includes("cerrada")) {
        return badgeStatus.success;
    }
    if (n.includes("proceso") || n.includes("asignado") || n.includes("abierto")) {
        return badgeStatus.warning;
    }
    if (n.includes("cancelado") || n.includes("rechaz")) return badgeStatus.danger;
    return withBorder(badgeNeutral);
}

/** Severidad incidencia por level (4+ crítico). */
export function incidentSeverityClassByLevel(level) {
    const l = Number(level);
    if (!Number.isFinite(l)) return withBorder(badgeNeutral);
    if (l >= 4) return badgeStatus.danger;
    if (l >= 3) return badgeStatus.warning;
    if (l >= 2) return badgeStatus.info;
    return withBorder(badgeNeutral);
}

/** Estado incidencia. */
export function incidentStatusClassByStatus(status) {
    const code = String(status?.code || "").toLowerCase();
    if (status?.is_final) return badgeStatus.success;
    if (code.includes("cancel") || code.includes("rechaz")) return badgeStatus.danger;
    return badgeStatus.info;
}

/** Plan MSP / In-House / Flexible. */
export function planTypeClass(type) {
    if (type === "msp") return badgeStatus.info;
    if (type === "inhouse") return badgeStatus.brand;
    if (type === "both") return withBorder(badgeNeutral);
    return withBorder(badgeNeutral);
}

/** Tarjetas resumen (listados + dashboard). */
export const kpiCardSurface = {
    default: {
        card: "bg-card border-border/50 text-foreground",
        icon: "bg-muted/20 text-muted-foreground",
    },
    info: {
        card: "border-primary/25 bg-primary/5 text-foreground dark:bg-primary/10 dark:border-primary/30",
        icon: "bg-primary/15 text-primary",
    },
    danger: {
        card: "border-destructive/25 bg-destructive/5 text-foreground dark:bg-destructive/10 dark:border-destructive/30",
        icon: "bg-destructive/15 text-destructive",
    },
    warning: {
        card: "border-amber-500/25 bg-amber-500/5 text-foreground dark:bg-amber-500/10 dark:border-amber-500/30",
        icon: "bg-amber-500/15 text-amber-700 dark:text-amber-400",
    },
    success: {
        card: "border-emerald-500/25 bg-emerald-500/5 text-foreground dark:bg-emerald-500/10 dark:border-emerald-500/30",
        icon: "bg-emerald-500/15 text-emerald-700 dark:text-emerald-400",
    },
    muted: {
        card: "bg-muted/50 border-border text-foreground",
        icon: "bg-muted text-muted-foreground",
    },
    accent: {
        card: "border-violet-500/25 bg-violet-500/5 text-foreground dark:bg-violet-500/10 dark:border-violet-500/30",
        icon: "bg-violet-500/15 text-violet-700 dark:text-violet-400",
    },
};

/** Alias legacy SummaryCard (Resolbeb Index). */
export const kpiCardVariantAlias = {
    default: "default",
    blue: "info",
    red: "danger",
    slate: "muted",
    violet: "accent",
};

/** Estado de usuario (listado / tarjetas). */
export function userStatusClass(status) {
    const map = {
        active: `${badgeStatus.success} hover:bg-emerald-500/25 dark:hover:bg-emerald-500/20`,
        pending_admin: `${badgeStatus.warning} hover:bg-amber-500/25 dark:hover:bg-amber-500/20`,
        pending_email: `${badgeStatus.info} hover:bg-blue-500/25 dark:hover:bg-blue-500/20`,
        blocked: badgeNeutralInteractive,
    };
    return map[status] || map.blocked;
}

/** Estado invitación (pending / accepted / expired). */
export function invitationStatusClass(status) {
    if (status === "accepted") return badgeStatus.success;
    if (status === "pending") return badgeStatus.warning;
    if (status === "expired") return badgeStatus.danger;
    return withBorder(badgeNeutral);
}

/** Iconos de acción en tablas (ghost, size icon). */
export const tableActionIcon = {
    base: "h-8 w-8",
    view: "text-primary hover:bg-primary/10 hover:text-primary",
    restore: "text-emerald-700 dark:text-emerald-400 hover:bg-emerald-500/10",
    approve:
        "text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 hover:bg-emerald-500/15 dark:bg-emerald-500/15",
    edit: "text-muted-foreground hover:text-foreground hover:bg-muted",
    blacklistOn: "text-amber-700 dark:text-amber-400 hover:bg-amber-500/10",
    blacklistOff: "text-muted-foreground hover:bg-destructive/10 hover:text-destructive",
    delete: "text-destructive hover:bg-destructive/10",
};

/** Contadores en tarjetas resumen (invitaciones, etc.). */
export const statValue = {
    default: "text-2xl font-bold text-foreground",
    success: "text-2xl font-bold text-emerald-700 dark:text-emerald-400",
    muted: "text-2xl font-bold text-muted-foreground",
};

/** Badge activo/inactivo (clientes, sedes). */
export const clientActiveBadge = badgeStatus.success;
export const clientInactiveBadge = withBorder(badgeNeutral);

/** Texto de aviso secundario (settings, alertas). */
export const hintWarning = "text-amber-700 dark:text-amber-400";

/** Tarjetas métricas inline (HomeDashboard SummaryMetric). */
export const metricCardVariant = {
    default: {
        card: "border-border/50 bg-card hover:bg-accent/5",
        icon: kpiCardSurface.default.icon,
    },
    success: {
        card: kpiCardSurface.success.card,
        icon: kpiCardSurface.success.icon,
    },
    warning: {
        card: kpiCardSurface.warning.card,
        icon: kpiCardSurface.warning.icon,
    },
    destructive: {
        card: kpiCardSurface.danger.card,
        icon: kpiCardSurface.danger.icon,
    },
    info: {
        card: kpiCardSurface.info.card,
        icon: kpiCardSurface.info.icon,
    },
};

/** Avisos amber (filtros vacíos, perfil sin área, contraseña). */
export const noticeWarningBox =
    "rounded-lg border border-amber-500/25 bg-amber-500/5 text-amber-800 dark:text-amber-200";

export const noticeWarningRow =
    "rounded-lg border border-amber-500/25 bg-amber-500/5 text-amber-800 dark:text-amber-200 flex flex-wrap items-center gap-2 px-4 py-3";

export const noticeWarningBanner =
    "w-full border-b border-amber-500/20 bg-amber-500/10 text-amber-600 dark:text-amber-500 text-sm px-6 py-2 flex items-center justify-center gap-3 z-50";

export const noticeWarningBtnOutline = "border-amber-500/50 hover:bg-amber-500/10";

/** Panel / bloque de nota (detalle ticket, alertas). */
export const noticeWarningPanel = `${noticeWarningBox} p-3 text-sm`;

/** Ítem de menú destructivo (cerrar sesión). */
export const menuItemDestructive =
    "cursor-pointer text-destructive focus:text-destructive focus:bg-destructive/10 gap-2";

/** Cabecera de diálogo éxito (ticket creado). */
export const dialogSuccessHeader =
    "p-5 pb-2 bg-emerald-500/10 border-b border-emerald-500/20";

export const dialogSuccessTitle =
    "text-lg flex items-center gap-2 text-emerald-700 dark:text-emerald-400";
