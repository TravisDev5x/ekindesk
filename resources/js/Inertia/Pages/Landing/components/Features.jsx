import {
    Building2,
    Clock,
    LayoutDashboard,
    MapPin,
    ShieldCheck,
    Ticket,
} from "lucide-react";

const FEATURES = [
    {
        icon: Ticket,
        title: "Gestión de tickets",
        description:
            "Crea, asigna y resuelve incidencias con flujo completo de estados, prioridades e historial.",
    },
    {
        icon: Building2,
        title: "Multi-cliente aislado",
        description:
            "Cada empresa ve solo sus tickets. Soffa nunca verá lo de CentralW.",
    },
    {
        icon: MapPin,
        title: "Despacho a sede",
        description: "Asigna técnicos a la ubicación exacta donde ocurre la incidencia.",
    },
    {
        icon: Clock,
        title: "SLA configurable",
        description: "Define tiempos de respuesta y resolución por cliente, área o prioridad.",
    },
    {
        icon: LayoutDashboard,
        title: "Portal para clientes",
        description:
            "Tus clientes abren y dan seguimiento a sus tickets sin acceder a tu panel interno.",
    },
    {
        icon: ShieldCheck,
        title: "Auditoría completa",
        description: "Cada acción queda registrada. Sabe quién hizo qué y cuándo en cada ticket.",
    },
];

export default function Features() {
    return (
        <section id="features" className="py-24 px-6 bg-slate-900/50">
            <div className="mx-auto max-w-7xl">
                <div className="text-center">
                    <span className="inline-block rounded-full border border-blue-500/20 bg-blue-500/10 px-4 py-1 text-sm text-blue-400 mb-4">
                        En qué creemos
                    </span>
                    <h2 className="text-4xl font-bold text-white">
                        Todo lo que tu equipo de soporte necesita
                    </h2>
                    <p className="mt-3 text-slate-400 text-center max-w-2xl mx-auto">
                        Desde el primer ticket hasta el cierre, EkinDesk cubre cada paso del flujo MSP.
                    </p>
                </div>

                <div className="mt-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    {FEATURES.map(({ icon: Icon, title, description }) => (
                        <div
                            key={title}
                            className="group rounded-xl border border-slate-800 bg-slate-900 p-6 transition-colors hover:border-cyan-500/50"
                        >
                            <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-500/20 to-blue-500/20">
                                <Icon className="h-6 w-6 text-cyan-400" />
                            </div>
                            <h3 className="text-lg font-semibold text-white mb-2">{title}</h3>
                            <p className="text-sm text-slate-400 leading-relaxed">{description}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
