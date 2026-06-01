import { cn } from "@/lib/utils";

/**
 * @param {{ step: number; label: string; status: 'done' | 'active' | 'upcoming' }} item
 */
function StepItem({ step, label, status }) {
    const dotClass =
        status === "done"
            ? "bg-primary/15 text-primary border border-primary/30 font-semibold"
            : status === "active"
              ? "bg-primary text-primary-foreground font-semibold"
              : "border border-border text-muted-foreground";

    const labelClass =
        status === "active" ? "text-sm font-medium text-foreground" : "text-sm text-muted-foreground";

    const wrapOpacity = status === "upcoming" ? "opacity-50" : "";

    return (
        <div className={cn("flex items-center gap-2", wrapOpacity)}>
            <span
                className={cn(
                    "flex h-8 w-8 items-center justify-center rounded-full text-sm shrink-0",
                    dotClass
                )}
            >
                {status === "done" ? "✓" : step}
            </span>
            <span className={labelClass}>{label}</span>
        </div>
    );
}

/**
 * Indicador de pasos onboarding (2 pasos por defecto).
 */
export function OnboardingStepIndicator({ currentStep = 1 }) {
    const step1Status = currentStep > 1 ? "done" : currentStep === 1 ? "active" : "upcoming";
    const step2Status = currentStep >= 2 ? "active" : "upcoming";

    return (
        <div className="flex items-center justify-center gap-3">
            <StepItem step={1} label="Perfil de negocio" status={step1Status} />
            <div className="h-px w-12 bg-border shrink-0" aria-hidden />
            <StepItem step={2} label="Primer cliente" status={step2Status} />
        </div>
    );
}
