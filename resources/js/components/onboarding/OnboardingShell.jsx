import { cn } from "@/lib/utils";
import { surfaceAuth } from "@/lib/marketingTheme";

/** Layout onboarding (perfil operador, primer cliente). */
export function OnboardingShell({ children, className }) {
    return (
        <div className={cn(surfaceAuth, "min-h-[100dvh] py-10 px-4", className)}>
            <div className="mx-auto max-w-2xl space-y-8">{children}</div>
        </div>
    );
}
