import { Link } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";

export default function Hero() {
    return (
        <section
            id="mission"
            className="relative min-h-screen flex items-center pt-24 pb-16 px-6 bg-slate-950"
            style={{
                backgroundImage:
                    "radial-gradient(circle, rgba(6,182,212,0.06) 1px, transparent 1px)",
                backgroundSize: "32px 32px",
            }}
        >
            <div className="mx-auto grid w-full max-w-7xl gap-12 lg:grid-cols-2 lg:gap-16 items-center">
                <div>
                    <div className="inline-flex items-center gap-2 rounded-full border border-cyan-500/30 bg-cyan-500/10 px-4 py-1.5 text-sm text-cyan-400 mb-8">
                        <span className="h-2 w-2 rounded-full bg-cyan-400 animate-pulse" />
                        Listo para operar en tu empresa
                    </div>

                    <h1 className="text-5xl lg:text-6xl font-black text-white leading-[1.1] tracking-tight">
                        Gestiona el soporte IT
                        <br />
                        <span className="bg-gradient-to-r from-cyan-400 to-blue-500 bg-clip-text text-transparent">
                            de tus clientes
                        </span>
                    </h1>

                    <p className="mt-6 text-lg text-slate-400 max-w-lg leading-relaxed">
                        EkinDesk reúne en un solo lugar todo lo que necesita tu empresa de soporte:
                        tickets, clientes, técnicos y SLAs. Pensado para empresas MSP que quieren menos
                        caos y más control.
                    </p>

                    <div className="mt-10 flex flex-col sm:flex-row gap-4">
                        <Button
                            size="lg"
                            className="bg-gradient-to-r from-cyan-500 to-blue-600 font-semibold text-white hover:from-cyan-400 hover:to-blue-500 border-0"
                            asChild
                        >
                            <Link href="/register">Crear cuenta gratis</Link>
                        </Button>
                        <Button
                            size="lg"
                            variant="outline"
                            className="border-slate-600 text-slate-300 hover:border-cyan-500 hover:text-cyan-400 bg-transparent"
                            asChild
                        >
                            <Link href="/login">Ya tengo cuenta</Link>
                        </Button>
                    </div>

                    <p className="mt-6 text-sm text-slate-500 flex flex-wrap gap-x-4 gap-y-1">
                        <span>Registro en 3 minutos</span>
                        <span>·</span>
                        <span>Sin tarjeta de crédito</span>
                        <span>·</span>
                        <span>Cancela cuando quieras</span>
                    </p>
                </div>

                <div className="relative hidden lg:block">
                    <div className="absolute inset-0 -z-10 blur-3xl bg-cyan-500/10 rounded-full scale-110" />
                    <div className="relative w-full max-w-lg ml-auto lg:rotate-1 shadow-2xl shadow-cyan-500/10 rounded-2xl border border-slate-700/50 bg-slate-900 p-4">
                        <div className="flex items-center gap-2 mb-4">
                            <span className="h-2 w-2 rounded-full bg-cyan-400 animate-pulse" />
                            <span className="text-slate-300 text-sm">Panel de soporte</span>
                            <Badge className="ml-auto bg-cyan-500/20 text-cyan-400 border-0 text-xs">
                                En línea
                            </Badge>
                        </div>

                        <div className="rounded-lg bg-slate-800/60 p-3 mb-2 border-l-2 border-l-red-500">
                            <div className="flex justify-between gap-2">
                                <div>
                                    <p className="text-slate-200 text-sm">Servidor caído — Soffa CDMX</p>
                                    <p className="text-slate-500 text-xs mt-0.5">Hace 5 min · Crítico</p>
                                </div>
                                <Badge className="bg-red-500/20 text-red-400 border-0 shrink-0">Abierto</Badge>
                            </div>
                        </div>

                        <div className="rounded-lg bg-slate-800/60 p-3 mb-2 border-l-2 border-l-yellow-500">
                            <div className="flex justify-between gap-2">
                                <div>
                                    <p className="text-slate-200 text-sm">VPN sin acceso — CentralW MTY</p>
                                    <p className="text-slate-500 text-xs mt-0.5">Hace 23 min · Alto</p>
                                </div>
                                <Badge className="bg-yellow-500/20 text-yellow-400 border-0 shrink-0">
                                    En proceso
                                </Badge>
                            </div>
                        </div>

                        <div className="rounded-lg bg-slate-800/60 p-3 mb-2 border-l-2 border-l-green-500">
                            <div className="flex justify-between gap-2">
                                <div>
                                    <p className="text-slate-200 text-sm">Impresora offline — Soffa GDL</p>
                                    <p className="text-slate-500 text-xs mt-0.5">Hace 1 hr · Medio</p>
                                </div>
                                <Badge className="bg-green-500/20 text-green-400 border-0 shrink-0">
                                    Resuelto
                                </Badge>
                            </div>
                        </div>

                        <div className="flex justify-between items-center mt-3 pt-3 border-t border-slate-700/50">
                            <span className="text-slate-500 text-xs">3 tickets activos hoy</span>
                            <div className="flex -space-x-2">
                                <div className="h-6 w-6 rounded-full bg-cyan-600 border-2 border-slate-900" />
                                <div className="h-6 w-6 rounded-full bg-blue-600 border-2 border-slate-900" />
                                <div className="h-6 w-6 rounded-full bg-slate-600 border-2 border-slate-900" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
