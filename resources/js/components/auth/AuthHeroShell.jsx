import { cn } from "@/lib/utils";
import { surfaceAuth } from "@/lib/marketingTheme";

const HERO_IMAGE = "/images/auth-hero.png";

/**
 * Contenedor auth con imagen de fondo (forgot, reset, register).
 * Envuelve un Card hijo; no altera textos del formulario.
 */
export function AuthHeroShell({ children, className, contentClassName }) {
    return (
        <div
            className={cn(
                surfaceAuth,
                "flex min-h-[100dvh] flex-col items-center justify-center relative px-4 py-6",
                "pb-[max(2rem,calc(2rem+env(safe-area-inset-bottom)))] overflow-y-auto",
                "md:min-h-screen md:overflow-hidden md:pb-6",
                className
            )}
        >
            <div
                className="absolute inset-0 z-0 bg-cover bg-center bg-no-repeat"
                style={{ backgroundImage: `url(${HERO_IMAGE})` }}
                aria-hidden
            />
            <div
                className="absolute inset-0 z-[1] pointer-events-none backdrop-blur-[6px] sm:backdrop-blur-[8px] bg-background/40 dark:bg-background/50"
                aria-hidden
            />
            <div
                className={cn(
                    "relative z-10 flex-shrink-0 my-auto md:my-0 w-full max-w-[420px]",
                    contentClassName
                )}
            >
                {children}
            </div>
        </div>
    );
}
