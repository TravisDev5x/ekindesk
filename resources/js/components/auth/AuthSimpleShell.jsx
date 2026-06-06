import { AuthBrandHomeLink } from "@/components/auth/AuthBrandHomeLink";
import { ThemeToggle } from "@/components/ThemeToggle";
import { cn } from "@/lib/utils";
import { surfaceAuth } from "@/lib/marketingTheme";

/**
 * Auth centrada sin hero (verify email, force password, invitation).
 */
export function AuthSimpleShell({ children, className, maxWidth = "max-w-md", tenant = {} }) {
    return (
        <div
            className={cn(
                surfaceAuth,
                "flex min-h-[100dvh] items-center justify-center p-6",
                className
            )}
        >
            <div className={cn("w-full", maxWidth)}>
                <div className="mb-6 flex items-center justify-between gap-4">
                    <AuthBrandHomeLink
                        tenant={tenant}
                        showName
                        markClassName="h-9 w-9"
                        nameClassName="text-lg"
                    />
                    <ThemeToggle variant="icon" />
                </div>
                {children}
            </div>
        </div>
    );
}
