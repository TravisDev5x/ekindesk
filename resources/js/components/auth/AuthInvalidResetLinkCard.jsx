import { Link } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { btnBrand, btnBrandOutline, linkBrand } from "@/lib/marketingTheme";

/**
 * Estado cuando el enlace de reset es inválido o expiró.
 */
export function AuthInvalidResetLinkCard() {
    return (
        <div className="space-y-4 rounded-xl border border-border/60 bg-muted/30 p-5">
            <p className="text-sm text-muted-foreground">
                Solicita un enlace nuevo desde la pantalla de recuperación o vuelve a iniciar sesión.
            </p>
            <div className="flex flex-col gap-2 sm:flex-row">
                <Button asChild className={`h-11 flex-1 rounded-lg ${btnBrand}`}>
                    <Link href="/forgot-password">Solicitar nuevo enlace</Link>
                </Button>
                <Button asChild variant="outline" className={`h-11 flex-1 rounded-lg ${btnBrandOutline}`}>
                    <Link href="/login">Ir al login</Link>
                </Button>
            </div>
            <Link href="/" className={`${linkBrand} inline-block text-sm`}>
                Volver a la landing
            </Link>
        </div>
    );
}
