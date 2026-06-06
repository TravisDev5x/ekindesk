import { Label } from "@/components/ui/label";
import { cn } from "@/lib/utils";

/**
 * Campo de formulario auth: label + control + mensaje de error.
 */
export function AuthFormField({
    id,
    label,
    error,
    hint,
    className,
    labelClassName,
    children,
}) {
    return (
        <div className={cn("space-y-2", className)}>
            <Label htmlFor={id} className={cn("text-sm", labelClassName)}>
                {label}
            </Label>
            {children}
            {error ? (
                <p className="text-xs text-destructive" role="alert">
                    {error}
                </p>
            ) : hint ? (
                <p className="text-xs text-muted-foreground">{hint}</p>
            ) : null}
        </div>
    );
}
