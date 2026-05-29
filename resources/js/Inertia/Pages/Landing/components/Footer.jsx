import { Link } from "@inertiajs/react";
import { Separator } from "@/components/ui/separator";

function BrandLogo() {
    return (
        <div className="flex items-center gap-2.5">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-400 to-blue-600 text-sm font-black text-white">
                E
            </div>
            <span className="text-lg font-bold text-white">EkinDesk</span>
        </div>
    );
}

export default function Footer() {
    const year = new Date().getFullYear();

    return (
        <footer id="footer" className="py-16 px-6 bg-slate-950 border-t border-slate-800/50">
            <div className="mx-auto max-w-7xl">
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10">
                    <div>
                        <BrandLogo />
                        <p className="text-slate-400 text-sm mt-3 max-w-xs leading-relaxed">
                            Helpdesk MSP profesional para equipos de soporte IT.
                        </p>
                        <div className="mt-4 flex gap-2">
                            {["X", "in", "GH"].map((label) => (
                                <div
                                    key={label}
                                    className="flex h-8 w-8 items-center justify-center rounded-md bg-slate-800 text-xs text-slate-500 hover:bg-slate-700 transition-colors cursor-default"
                                    title={label}
                                >
                                    {label}
                                </div>
                            ))}
                        </div>
                    </div>

                    <div>
                        <h4 className="text-slate-200 font-semibold mb-4">Producto</h4>
                        <ul className="space-y-2 text-sm">
                            <li>
                                <a href="#features" className="text-slate-400 hover:text-white transition-colors">
                                    Características
                                </a>
                            </li>
                            <li>
                                <a href="#pricing" className="text-slate-400 hover:text-white transition-colors">
                                    Planes
                                </a>
                            </li>
                            <li>
                                <a
                                    href="#how-it-works"
                                    className="text-slate-400 hover:text-white transition-colors"
                                >
                                    Cómo empezar
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="text-slate-200 font-semibold mb-4">Empresa</h4>
                        <ul className="space-y-2 text-sm">
                            <li>
                                <a href="#mission" className="text-slate-400 hover:text-white transition-colors">
                                    Misión
                                </a>
                            </li>
                            <li>
                                <a href="#footer" className="text-slate-400 hover:text-white transition-colors">
                                    Contacto
                                </a>
                            </li>
                            <li>
                                <Link href="/privacidad" className="text-slate-400 hover:text-white transition-colors">
                                    Aviso de privacidad
                                </Link>
                            </li>
                            <li>
                                <Link href="/terminos" className="text-slate-400 hover:text-white transition-colors">
                                    Términos
                                </Link>
                            </li>
                        </ul>
                    </div>

                    <div>
                        <h4 className="text-slate-200 font-semibold mb-4">Acceso</h4>
                        <ul className="space-y-2 text-sm">
                            <li>
                                <Link href="/login" className="text-slate-400 hover:text-white transition-colors">
                                    Iniciar sesión
                                </Link>
                            </li>
                            <li>
                                <Link href="/register" className="text-slate-400 hover:text-white transition-colors">
                                    Crear cuenta
                                </Link>
                            </li>
                            <li>
                                <Link href="/manual" className="text-slate-400 hover:text-white transition-colors">
                                    Documentación
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <Separator className="my-8 bg-slate-800" />

                <div className="flex flex-col sm:flex-row justify-between items-center gap-4 text-sm text-slate-500">
                    <p>© {year} EkinDesk. Todos los derechos reservados.</p>
                    <p>Hecho en México 🇲🇽</p>
                </div>
            </div>
        </footer>
    );
}
