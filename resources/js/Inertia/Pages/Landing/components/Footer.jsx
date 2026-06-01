import { Link } from "@inertiajs/react";
import { Separator } from "@/components/ui/separator";
import { brandLogo, footerLink } from "@/lib/marketingTheme";

function BrandLogo() {
    return (
        <div className="flex items-center gap-2.5">
            <div className={`h-8 w-8 ${brandLogo}`}>E</div>
            <span className="text-lg font-bold text-foreground">EkinDesk</span>
        </div>
    );
}

export default function Footer() {
    const year = new Date().getFullYear();

    return (
        <footer id="footer" className="py-16 px-6 bg-background border-t border-border">
            <div className="mx-auto max-w-7xl">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                    <div>
                        <BrandLogo />
                        <p className="text-muted-foreground text-sm mt-3 max-w-xs leading-relaxed">
                            Helpdesk MSP profesional para equipos de soporte IT.
                        </p>
                        <div className="mt-4 flex gap-2">
                            {["X", "in", "GH"].map((label) => (
                                <div
                                    key={label}
                                    className="flex h-8 w-8 items-center justify-center rounded-md bg-muted text-xs text-muted-foreground hover:bg-muted/80 transition-colors cursor-default"
                                    title={label}
                                >
                                    {label}
                                </div>
                            ))}
                        </div>
                    </div>

                    <div>
                        <h4 className="text-foreground font-semibold mb-4">Producto</h4>
                        <ul className="space-y-2 text-sm">
                            <li>
                                <a href="#features" className={footerLink}>
                                    Características
                                </a>
                            </li>
                            <li>
                                <a href="#pricing" className={footerLink}>
                                    Planes
                                </a>
                            </li>
                            <li>
                                <a href="#how-it-works" className={footerLink}>
                                    Cómo empezar
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="text-foreground font-semibold mb-4">Empresa</h4>
                        <ul className="space-y-2 text-sm">
                            <li>
                                <a href="#mission" className={footerLink}>
                                    Misión
                                </a>
                            </li>
                            <li>
                                <a href="#footer" className={footerLink}>
                                    Contacto
                                </a>
                            </li>
                            <li>
                                <Link href="/privacidad" className={footerLink}>
                                    Aviso de privacidad
                                </Link>
                            </li>
                            <li>
                                <Link href="/terminos" className={footerLink}>
                                    Términos
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="text-foreground font-semibold mb-4">Acceso</h4>
                        <ul className="space-y-2 text-sm">
                            <li>
                                <Link href="/login" className={footerLink}>
                                    Iniciar sesión
                                </Link>
                            </li>
                            <li>
                                <Link href="/register" className={footerLink}>
                                    Crear cuenta
                                </Link>
                            </li>
                            <li>
                                <Link href="/manual" className={footerLink}>
                                    Documentación
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <Separator className="my-8 bg-border" />

                <div className="flex flex-col sm:flex-row justify-between items-center gap-4 text-sm text-muted-foreground">
                    <p>© {year} EkinDesk. Todos los derechos reservados.</p>
                    <p>Hecho en México 🇲🇽</p>
                </div>
            </div>
        </footer>
    );
}
