import { Check, X } from "lucide-react";
import { getPasswordChecks, PASSWORD_REQUIREMENT_ITEMS } from "@/lib/passwordSchema";
import { cn } from "@/lib/utils";

/**
 * Checklist de requisitos de contraseña (registro, invitaciones).
 */
export function PasswordRequirements({ password, className }) {
    const checks = getPasswordChecks(password);

    return (
        <div
            className={cn(
                "space-y-1.5 rounded-md border border-border/60 bg-muted/30 px-3 py-2 text-xs",
                className
            )}
        >
            <p className="font-medium text-muted-foreground">Requisitos de la contraseña:</p>
            <ul className="space-y-1">
                {PASSWORD_REQUIREMENT_ITEMS.map(({ key, label }) => {
                    const passed = checks[key];
                    return (
                        <li
                            key={key}
                            className={cn(
                                "flex items-center gap-2",
                                passed
                                    ? "text-emerald-700 dark:text-emerald-400"
                                    : "text-muted-foreground"
                            )}
                        >
                            {passed ? (
                                <Check className="h-3.5 w-3.5 shrink-0" />
                            ) : (
                                <X className="h-3.5 w-3.5 shrink-0 opacity-50" />
                            )}
                            {label}
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
