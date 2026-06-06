import { Link } from "@inertiajs/react";
import { linkBrand } from "@/lib/marketingTheme";
import { cn } from "@/lib/utils";

export function AuthFormSection({ title, children, className }) {
    return (
        <div className={cn("space-y-4", className)}>
            {title ? (
                <p className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    {title}
                </p>
            ) : null}
            {children}
        </div>
    );
}

export function AuthBackToLoginLink({ label = "Volver al inicio de sesión", className }) {
    return (
        <Link
            href="/login"
            className={cn(
                linkBrand,
                "inline-flex h-11 w-full items-center justify-center text-sm",
                className
            )}
        >
            {label}
        </Link>
    );
}
