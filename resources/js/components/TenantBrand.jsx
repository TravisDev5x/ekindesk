import { ShieldCheck } from "lucide-react";
import { cn } from "@/lib/utils";
import { brandLogo } from "@/lib/marketingTheme";
import {
    getTenantBrandName,
    getTenantLogoUrl,
    isClientPortalTenant,
} from "@/lib/tenantBranding";

export function TenantBrandMark({ tenant, className, fallbackName = "EkinDesk" }) {
    const name = getTenantBrandName(tenant, fallbackName);
    const logoUrl = getTenantLogoUrl(tenant);
    const isPortal = isClientPortalTenant(tenant);

    if (logoUrl) {
        return (
            <img
                src={logoUrl}
                alt=""
                className={cn(
                    "rounded-lg object-cover border border-border/60 bg-card shrink-0",
                    className
                )}
            />
        );
    }

    if (isPortal) {
        return (
            <div
                className={cn(
                    "flex items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-md shrink-0 font-bold text-sm",
                    className
                )}
            >
                {name.charAt(0).toUpperCase()}
            </div>
        );
    }

    return (
        <div
            className={cn(
                "flex items-center justify-center rounded-lg bg-primary text-primary-foreground shadow-md shrink-0",
                className
            )}
        >
            <ShieldCheck className="h-4 w-4" />
        </div>
    );
}

export function TenantBrandHeader({
    tenant,
    title,
    subtitle,
    className,
    titleClassName,
    subtitleClassName,
    markClassName = "h-8 w-8",
}) {
    const displayTitle = title ?? getTenantBrandName(tenant, "EkinDesk");

    return (
        <div className={cn("flex items-center gap-2 overflow-hidden min-w-0", className)}>
            <TenantBrandMark tenant={tenant} className={markClassName} />
            <div className="flex flex-col min-w-0">
                <span
                    className={cn(
                        "text-sm font-bold leading-none tracking-tight truncate",
                        titleClassName
                    )}
                >
                    {displayTitle}
                </span>
                {subtitle ? (
                    <span
                        className={cn(
                            "text-[10px] text-muted-foreground font-medium uppercase tracking-widest mt-0.5 opacity-80 truncate",
                            subtitleClassName
                        )}
                    >
                        {subtitle}
                    </span>
                ) : null}
            </div>
        </div>
    );
}

/** Marca compacta para login (reutiliza tokens marketing). */
export function TenantBrandLoginMark({ tenant, className }) {
    const name = getTenantBrandName(tenant, "EkinDesk");
    const logoUrl = getTenantLogoUrl(tenant);

    if (logoUrl) {
        return (
            <img
                src={logoUrl}
                alt=""
                className={cn(
                    "h-10 w-10 rounded-xl object-cover border border-border/60",
                    className
                )}
            />
        );
    }

    return (
        <div className={cn("h-10 w-10 rounded-xl", brandLogo, className)}>
            <span className="font-black text-lg">{name.charAt(0).toUpperCase()}</span>
        </div>
    );
}
