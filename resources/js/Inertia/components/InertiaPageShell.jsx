import { cn } from "@/lib/utils";

/**
 * Contenedor de página Inertia: animación y padding móvil.
 * El ancho lo controla AuthenticatedLayout (max-w-7xl), igual que AppLayout en SPA.
 */
export default function InertiaPageShell({ children, className }) {
    return (
        <div
            className={cn(
                "w-full space-y-6 animate-in fade-in slide-in-from-bottom-4 duration-500 pb-content-mobile",
                className
            )}
        >
            {children}
        </div>
    );
}
