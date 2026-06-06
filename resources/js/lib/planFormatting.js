/**
 * Formato de precios de planes (landing, registro).
 */

export function formatPlanPriceMonthly(value, { zeroLabel = "Contactar" } = {}) {
    const n = Number(value);
    if (!n) {
        return zeroLabel;
    }
    return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
        maximumFractionDigits: 0,
    }).format(n);
}

export function planPriceLabel(plan) {
    const monthly = Number(plan?.price_monthly);
    if (!monthly) {
        return { main: "Contactar", suffix: "Precio a medida" };
    }
    return {
        main: formatPlanPriceMonthly(monthly),
        suffix: "/mes",
    };
}
