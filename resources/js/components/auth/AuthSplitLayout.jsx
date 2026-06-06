import { Link } from "@inertiajs/react";
import { ThemeToggle } from "@/components/ThemeToggle";
import { TenantBrandLoginMark } from "@/components/TenantBrand";
import { authCard, linkBrand, surfaceAuth } from "@/lib/marketingTheme";
import { getTenantBrandName } from "@/lib/tenantBranding";
import { cn } from "@/lib/utils";

/**
 * Shell split auth: panel lateral + columna de formulario (login, registro).
 */
export function AuthSplitLayout({
    tenant = {},
    brandingPanel,
    children,
    formClassName,
    topLink,
}) {
    return (
        <div className={`${surfaceAuth} flex`}>
            {brandingPanel}

            <div className="flex min-h-screen flex-1 items-center justify-center bg-muted/20 p-8 lg:p-16">
                <div className={cn(authCard, formClassName)}>
                    <div className="mb-6 flex items-start justify-between">
                        <div className="flex items-center gap-3 lg:hidden">
                            <TenantBrandLoginMark tenant={tenant} className="h-9 w-9" />
                            <span className="text-lg font-bold text-foreground">
                                {getTenantBrandName(tenant, "EkinDesk")}
                            </span>
                        </div>
                        <div className="ml-auto">
                            <ThemeToggle variant="icon" />
                        </div>
                    </div>

                    {topLink ? (
                        <div className="mb-6 flex items-center justify-end text-sm">
                            {topLink.prompt ? (
                                <span className="text-muted-foreground">{topLink.prompt}</span>
                            ) : null}
                            <Link href={topLink.href} className={`${linkBrand} ml-1`}>
                                {topLink.label}
                            </Link>
                        </div>
                    ) : null}

                    {children}
                </div>
            </div>
        </div>
    );
}
