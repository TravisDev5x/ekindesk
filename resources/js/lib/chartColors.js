/**
 * Colores de gráficos alineados con tokens shadcn (--chart-1 … --chart-8).
 * Colores de gráficos vía tokens --chart-N (siguen tema claro/oscuro).
 */
const CHART_VAR_NAMES = [
    "--chart-1",
    "--chart-2",
    "--chart-3",
    "--chart-4",
    "--chart-5",
    "--chart-6",
    "--chart-7",
    "--chart-8",
];

export function chartColor(index) {
    const name = CHART_VAR_NAMES[index % CHART_VAR_NAMES.length];
    return `hsl(var(${name}))`;
}

export function chartColors(count = CHART_VAR_NAMES.length) {
    return Array.from({ length: count }, (_, i) => chartColor(i));
}

/** Paleta para barra apilada de estados (DashboardStackedBar / HomeDashboard). */
export const stateDistributionColors = chartColors();

/** Barras simples (MetricList, CardBarChart, etc.) — alineado con --primary. */
export const primaryBarColor = "hsl(var(--primary))";

/** Relleno de barra de progreso (listados, Top Estados). */
export function chartProgressStyle(percent, index = 0) {
    const pct = Math.max(0, Math.min(100, Number(percent) || 0));
    return { width: `${pct}%`, backgroundColor: chartColor(index) };
}

export default chartColors;
