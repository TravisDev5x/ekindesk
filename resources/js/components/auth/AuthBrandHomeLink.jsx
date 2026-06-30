import { Link } from "@inertiajs/react";
import { TenantBrandLoginMark } from "@/components/TenantBrand";
import { getTenantBrandName } from "@/lib/tenantBranding";
import { cn } from "@/lib/utils";

/**
 * Logo de auth (icono E) + nombre opcional → landing pública (/).
 */
export function AuthBrandHomeLink({
    tenant = {},
    showName = false,
    markClassName,
    className,
    nameClassName,
}) {
    const brandName = getTenantBrandName(tenant, "Tikara");

    return (
        <Link
            href="/"
            className={cn(
                "inline-flex shrink-0 items-center gap-3 rounded-lg transition-opacity hover:opacity-90",
                "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background",
                className
            )}
            aria-label={`${brandName} — ir al inicio`}
        >
            <TenantBrandLoginMark tenant={tenant} className={markClassName} />
            {showName ? (
                <span
                    className={cn(
                        "text-xl font-bold tracking-tight text-foreground",
                        nameClassName
                    )}
                >
                    {brandName}
                </span>
            ) : null}
        </Link>
    );
}
