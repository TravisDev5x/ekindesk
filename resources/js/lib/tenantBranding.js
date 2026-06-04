/**
 * Branding de tenant (portal cliente) compartido entre login y layout autenticado.
 */

export function isClientPortalTenant(tenant) {
    return tenant?.mode === "client_portal" && Boolean(tenant?.name);
}

export function getTenantBrandName(tenant, fallback = "EkinDesk") {
    if (isClientPortalTenant(tenant)) {
        return tenant.name;
    }

    return fallback;
}

export function getTenantLogoUrl(tenant) {
    if (!tenant?.logo_path) {
        return null;
    }

    return `/storage/${tenant.logo_path}`;
}

/** Convierte #RRGGBB a componentes HSL para var(--brand) (sin hsl()). */
export function hexToHslComponents(hex) {
    const normalized = hex.replace("#", "").trim();
    if (normalized.length !== 6) {
        return null;
    }

    const r = parseInt(normalized.slice(0, 2), 16) / 255;
    const g = parseInt(normalized.slice(2, 4), 16) / 255;
    const b = parseInt(normalized.slice(4, 6), 16) / 255;

    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const delta = max - min;

    let h = 0;
    if (delta !== 0) {
        if (max === r) {
            h = ((g - b) / delta) % 6;
        } else if (max === g) {
            h = (b - r) / delta + 2;
        } else {
            h = (r - g) / delta + 4;
        }
    }
    h = Math.round(h * 60);
    if (h < 0) {
        h += 360;
    }

    const l = (max + min) / 2;
    const s = delta === 0 ? 0 : delta / (1 - Math.abs(2 * l - 1));

    return `${h} ${Math.round(s * 100)}% ${Math.round(l * 100)}%`;
}

/**
 * @returns {Record<string, string>|undefined}
 */
export function resolveTenantBrandCssVars(tenant) {
    if (!isClientPortalTenant(tenant) || !tenant?.portal_primary_color) {
        return undefined;
    }

    const color = String(tenant.portal_primary_color).trim();

    if (/^#?[0-9a-f]{6}$/i.test(color)) {
        const hsl = hexToHslComponents(color.startsWith("#") ? color : `#${color}`);
        return hsl ? { "--brand": hsl } : undefined;
    }

    if (color.startsWith("hsl(")) {
        const inner = color.slice(4, -1).trim();
        return { "--brand": inner };
    }

    return { "--brand": color };
}
