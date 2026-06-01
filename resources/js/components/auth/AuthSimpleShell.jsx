import { cn } from "@/lib/utils";
import { surfaceAuth } from "@/lib/marketingTheme";

/**
 * Auth centrada sin hero (verify email, force password, invitation).
 */
export function AuthSimpleShell({ children, className, maxWidth = "max-w-md" }) {
    return (
        <div
            className={cn(
                surfaceAuth,
                "flex min-h-[100dvh] items-center justify-center p-6",
                className
            )}
        >
            <div className={cn("w-full", maxWidth)}>{children}</div>
        </div>
    );
}
