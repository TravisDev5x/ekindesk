import { cn } from "@/lib/utils";
import { brandLogo } from "@/lib/marketingTheme";
import {
    getTenantBrandName,
    getTenantLogoUrl,
    isClientPortalTenant,
} from "@/lib/tenantBranding";

/** Returns "TI" for Tikara's own brand, first letter for portal tenants. */
function getBrandMonogram(name, isPortal) {
    if (isPortal) return name.charAt(0).toUpperCase();
    return name.slice(0, 2).toUpperCase();
}

export function TenantBrandMark({ tenant, className, fallbackName = "Tikara" }) {
    const name = getTenantBrandName(tenant, fallbackName);
    const logoUrl = getTenantLogoUrl(tenant);
    const isPortal = isClientPortalTenant(tenant);

    if (logoUrl) {
        return (
            <img
                src={logoUrl}
                alt=""
                className={cn(
                    "rounded-xl object-cover border border-border/60 bg-card shrink-0",
                    className
                )}
            />
        );
    }

    return (
        <div
            className={cn(
                "shrink-0",
                brandLogo,
                className
            )}
        >
            <span className="text-[10px] leading-none">
                {getBrandMonogram(name, isPortal)}
            </span>
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
    const displayTitle = title ?? getTenantBrandName(tenant, "Tikara");

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

/** Marca compacta para login/auth (reutiliza tokens marketing). */
export function TenantBrandLoginMark({ tenant, className }) {
    const name = getTenantBrandName(tenant, "Tikara");
    const logoUrl = getTenantLogoUrl(tenant);
    const isPortal = isClientPortalTenant(tenant);

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
        <div className={cn("flex h-10 w-10 items-center justify-center", brandLogo, className)}>
            <span className="text-sm leading-none">
                {getBrandMonogram(name, isPortal)}
            </span>
        </div>
    );
}
