import { Card, CardContent } from "@/components/ui/card";
import { kpiCardSurface, kpiCardVariantAlias } from "@/lib/badgeStyles";
import { cn } from "@/lib/utils";

/**
 * Tarjeta KPI alineada con tokens shadcn (variantes semánticas).
 */
export function KpiCard({ title, value, icon: Icon, variant = "default", hint, className }) {
    const key = kpiCardVariantAlias[variant] || variant;
    const surface = kpiCardSurface[key] || kpiCardSurface.default;

    return (
        <Card
            className={cn(
                "shadow-sm transition-all hover:shadow-md border",
                surface.card,
                className
            )}
        >
            <CardContent className="p-5 flex items-center justify-between">
                <div className="space-y-1">
                    <p className="text-[10px] uppercase tracking-wider font-bold opacity-70">{title}</p>
                    <div className="text-2xl font-bold tracking-tight">{value}</div>
                    {hint ? <p className="text-[10px] opacity-70">{hint}</p> : null}
                </div>
                {Icon ? (
                    <div
                        className={cn(
                            "h-11 w-11 rounded-full flex items-center justify-center shrink-0",
                            surface.icon
                        )}
                    >
                        <Icon className="h-5 w-5" />
                    </div>
                ) : null}
            </CardContent>
        </Card>
    );
}
