import { forwardRef, useState } from "react";
import { Eye, EyeOff } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { AuthFormField } from "@/components/auth/AuthFormField";
import { cn } from "@/lib/utils";

/**
 * Input de contraseña con toggle de visibilidad (altura alineada con login).
 */
export const PasswordField = forwardRef(function PasswordField(
    {
        id,
        label,
        disabled,
        autoComplete = "new-password",
        error,
        className,
        placeholder,
        required,
        name,
        ...inputProps
    },
    ref
) {
    const [visible, setVisible] = useState(false);

    return (
        <AuthFormField id={id} label={label} error={error} className={className}>
            <div className="relative">
                <Input
                    ref={ref}
                    id={id}
                    name={name}
                    type={visible ? "text" : "password"}
                    autoComplete={autoComplete}
                    disabled={disabled}
                    placeholder={placeholder}
                    required={required}
                    aria-invalid={Boolean(error)}
                    className="h-11 pr-12"
                    {...inputProps}
                />
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => setVisible((current) => !current)}
                    className="absolute right-1 top-1/2 h-9 w-9 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    disabled={disabled}
                    aria-label={visible ? "Ocultar contraseña" : "Mostrar contraseña"}
                >
                    {visible ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </Button>
            </div>
        </AuthFormField>
    );
});

/**
 * Hint de coincidencia de contraseñas (confirmación).
 */
export function PasswordMatchHint({ password, confirmation }) {
    if (!confirmation?.length) {
        return null;
    }

    const matches = password === confirmation;

    return (
        <p
            className={cn(
                "text-xs",
                matches ? "text-emerald-700 dark:text-emerald-400" : "text-destructive"
            )}
        >
            {matches ? "Las contraseñas coinciden" : "Las contraseñas no coinciden"}
        </p>
    );
}
