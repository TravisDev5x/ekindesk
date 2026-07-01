import { Link } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import {
    brandBadge,
    brandGradientText,
    btnBrand,
    btnBrandOutline,
    heroSectionClass,
    mockAvatarRing,
    mockTicketRow,
} from "@/lib/marketingTheme";
import { badgeStatusFlat, mockTicketAccentBorder } from "@/lib/badgeStyles";

export default function Hero() {
    return (
        <section id="mission" className={heroSectionClass}>
            <div className="mx-auto grid w-full max-w-7xl gap-12 lg:grid-cols-2 lg:gap-16 items-center">
                <div>
                    <div className="flex flex-wrap items-center gap-3 mb-8">
                        <div className={`${brandBadge}`}>
                            <span className="h-2 w-2 rounded-full bg-brand animate-pulse" />
                            Listo para operar en tu empresa
                        </div>
                        <span className="inline-flex items-center gap-1.5 text-xs text-muted-foreground/70">
                            <span className="h-px w-4 bg-border" />
                            Un producto de{" "}
                            <span className="font-semibold text-foreground/60 tracking-tight">DDMA</span>
                        </span>
                    </div>

                    <h1 className="text-5xl lg:text-6xl font-black text-foreground leading-[1.1] tracking-tight">
                        Gestiona el soporte IT
                        <br />
                        <span className={brandGradientText}>de tus clientes</span>
                    </h1>

                    <p className="mt-6 text-lg text-muted-foreground max-w-lg leading-relaxed">
                        Tikara reúne en un solo lugar todo lo que necesita tu empresa de soporte:
                        tickets, clientes, técnicos y SLAs. Pensado para empresas MSP que quieren menos
                        caos y más control.
                    </p>

                    <div className="mt-10 flex flex-col sm:flex-row gap-4">
                        <Button size="lg" className={btnBrand} asChild>
                            <Link href="/register">Crear cuenta gratis</Link>
                        </Button>
                        <Button size="lg" variant="outline" className={btnBrandOutline} asChild>
                            <Link href="/login">Ya tengo cuenta</Link>
                        </Button>
                    </div>

                    <p className="mt-6 text-sm text-muted-foreground flex flex-wrap gap-x-4 gap-y-1">
                        <span>Registro en 3 minutos</span>
                        <span>·</span>
                        <span>Sin tarjeta de crédito</span>
                        <span>·</span>
                        <span>Cancela cuando quieras</span>
                    </p>
                </div>

                <div className="relative hidden lg:block">
                    <div className="absolute inset-0 -z-10 blur-3xl bg-brand/10 rounded-full scale-110" />
                    <div className="relative w-full max-w-lg ml-auto lg:rotate-1 mkt-elevated rounded-2xl p-4">
                        <div className="flex items-center gap-2 mb-4">
                            <span className="h-2 w-2 rounded-full bg-brand animate-pulse" />
                            <span className="text-muted-foreground text-sm">Panel de soporte</span>
                            <Badge className="ml-auto bg-brand/15 text-brand-muted border border-brand/20 text-xs">
                                En línea
                            </Badge>
                        </div>

                        <div className={`${mockTicketRow} ${mockTicketAccentBorder.danger}`}>
                            <div className="flex justify-between gap-2">
                                <div>
                                    <p className="text-foreground text-sm">Servidor caído — Alfa Retail CDMX</p>
                                    <p className="text-muted-foreground text-xs mt-0.5">Hace 5 min · Crítico</p>
                                </div>
                                <Badge className={badgeStatusFlat.danger}>Abierto</Badge>
                            </div>
                        </div>

                        <div className={`${mockTicketRow} ${mockTicketAccentBorder.warning}`}>
                            <div className="flex justify-between gap-2">
                                <div>
                                    <p className="text-foreground text-sm">VPN sin acceso — Beta Logística MTY</p>
                                    <p className="text-muted-foreground text-xs mt-0.5">Hace 23 min · Alto</p>
                                </div>
                                <Badge className={badgeStatusFlat.warning}>En proceso</Badge>
                            </div>
                        </div>

                        <div className={`${mockTicketRow} ${mockTicketAccentBorder.success}`}>
                            <div className="flex justify-between gap-2">
                                <div>
                                    <p className="text-foreground text-sm">Impresora offline — Gamma Servicios GDL</p>
                                    <p className="text-muted-foreground text-xs mt-0.5">Hace 1 hr · Medio</p>
                                </div>
                                <Badge className={badgeStatusFlat.success}>Resuelto</Badge>
                            </div>
                        </div>

                        <div className="flex justify-between items-center mt-3 pt-3 border-t border-border/80">
                            <span className="text-muted-foreground text-xs">3 tickets activos hoy</span>
                            <div className="flex -space-x-2">
                                <div
                                    className={`h-6 w-6 rounded-full bg-[hsl(var(--brand))] ${mockAvatarRing}`}
                                />
                                <div
                                    className={`h-6 w-6 rounded-full bg-[hsl(var(--chart-1))] ${mockAvatarRing}`}
                                />
                                <div
                                    className={`h-6 w-6 rounded-full bg-muted-foreground ${mockAvatarRing}`}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
