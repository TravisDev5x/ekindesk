import { cn } from "@/lib/utils";

/**
 * Encabezado de página auth (título + descripción opcional).
 */
export function AuthPageHeader({ title, description, className, children }) {
    return (
        <div className={cn("mb-6", className)}>
            <h1 className="text-2xl font-bold tracking-tight text-foreground">{title}</h1>
            {description ? (
                <p className="mt-1 text-sm leading-relaxed text-muted-foreground">{description}</p>
            ) : null}
            {children}
        </div>
    );
}
