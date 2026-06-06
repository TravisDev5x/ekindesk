import { Link } from "@inertiajs/react";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { PlanTypeBadge } from "@/components/badges/EntityBadges";
import {
    brandGradientFill,
    infoBadge,
    linkBrand,
    planCard,
    planCardHighlighted,
    planChip,
} from "@/lib/marketingTheme";
import { planPriceLabel } from "@/lib/planFormatting";
import { cn } from "@/lib/utils";

/**
 * Resumen del plan en registro: estilo alineado con Pricing de la landing.
 */
export function RegisterPlanSummary({ plan, planSlug }) {
    if (!plan) {
        return (
            <div className={cn(planCard, "p-5")}>
                <span className={infoBadge}>Plan</span>
                <p className="mt-3 text-sm font-semibold text-foreground">
                    {planSlug ? "Plan no disponible" : "Sin plan seleccionado"}
                </p>
                <p className="mt-1 text-sm text-muted-foreground">
                    {planSlug
                        ? `El plan «${planSlug}» no está activo. Elige otro en la landing.`
                        : "Compara opciones en la landing y vuelve con el enlace del plan que prefieras."}
                </p>
                <Link href="/#pricing" className={`${linkBrand} mt-3 inline-block text-sm`}>
                    Ver planes
                </Link>
            </div>
        );
    }

    const highlighted = Boolean(plan.highlighted);
    const { main, suffix } = planPriceLabel(plan);

    return (
        <div className={cn("relative p-5", planCard, highlighted && planCardHighlighted)}>
            {highlighted ? (
                <Badge
                    className={cn(
                        "absolute -top-3.5 left-4 whitespace-nowrap border-0 px-3 py-0.5 text-xs font-bold text-brand-foreground",
                        brandGradientFill
                    )}
                >
                    Más popular
                </Badge>
            ) : null}

            <div className="flex flex-wrap items-center justify-between gap-2">
                <span className="text-sm font-medium uppercase tracking-wider text-muted-foreground">
                    {plan.name}
                </span>
                <PlanTypeBadge type={plan.type} />
            </div>

            <div className="mt-3 flex flex-wrap items-baseline gap-x-1">
                <span className="text-2xl font-black text-foreground">{main}</span>
                {suffix === "/mes" ? (
                    <span className="text-sm text-muted-foreground">{suffix}</span>
                ) : (
                    <span className="text-sm text-muted-foreground">{suffix}</span>
                )}
            </div>

            {plan.trial_days > 0 ? (
                <p className="mt-1 text-xs text-brand-muted">
                    {plan.trial_days} días gratis para empezar
                </p>
            ) : null}

            {(plan.max_clients != null || plan.max_users != null || plan.max_agents != null) && (
                <>
                    <Separator className="my-4 bg-border" />
                    <div className="flex flex-wrap gap-1">
                        {plan.max_clients != null ? (
                            <span className={planChip}>{plan.max_clients} clientes</span>
                        ) : null}
                        <span className={planChip}>{plan.max_users ?? "∞"} usuarios</span>
                        <span className={planChip}>{plan.max_agents ?? "∞"} agentes</span>
                    </div>
                </>
            )}

            <Link href="/#pricing" className={`${linkBrand} mt-4 inline-block text-xs`}>
                Cambiar plan
            </Link>
        </div>
    );
}

/**
 * Micro-copy de confianza (hero landing → registro).
 */
export function RegisterTrustLine({ className }) {
    return (
        <p
            className={cn(
                "flex flex-wrap gap-x-3 gap-y-1 text-sm text-muted-foreground",
                className
            )}
        >
            <span>Registro en 3 minutos</span>
            <span aria-hidden>·</span>
            <span>Sin tarjeta de crédito</span>
            <span aria-hidden>·</span>
            <span>Cancela cuando quieras</span>
        </p>
    );
}
