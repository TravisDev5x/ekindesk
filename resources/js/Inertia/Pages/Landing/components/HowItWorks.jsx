const STEPS = [
    {
        n: "1",
        title: "Crea tu cuenta",
        desc: "Regístrate con el correo de tu empresa en menos de 3 minutos.",
    },
    {
        n: "2",
        title: "Agrega tus clientes",
        desc: "Registra las empresas a las que prestas soporte y sus sedes.",
    },
    {
        n: "3",
        title: "Invita a tu equipo",
        desc: "Envía invitaciones a tus técnicos y agentes de soporte.",
    },
    {
        n: "4",
        title: "Empieza a operar",
        desc: "Recibe tickets, asigna técnicos y resuelve incidencias.",
    },
];

export default function HowItWorks() {
    return (
        <section id="how-it-works" className="py-24 px-6 bg-slate-950">
            <div className="mx-auto max-w-7xl">
                <div className="text-center mb-16">
                    <span className="inline-block rounded-full border border-cyan-500/20 bg-cyan-500/10 px-4 py-1 text-sm text-cyan-400 mb-4">
                        Cómo empezar
                    </span>
                    <h2 className="text-4xl font-bold text-white">Operativo en minutos</h2>
                    <p className="mt-3 text-slate-400">Sin instalaciones. Sin configuraciones complejas.</p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-4 gap-10 md:gap-6">
                    {STEPS.map((step, index) => (
                        <div key={step.n} className="relative text-center md:text-left">
                            {index < STEPS.length - 1 && (
                                <div
                                    className="hidden md:block absolute top-8 left-[calc(50%+2rem)] right-0 h-px bg-gradient-to-r from-cyan-500/50 to-transparent"
                                    aria-hidden
                                />
                            )}
                            <span className="text-6xl font-black bg-gradient-to-b from-cyan-400 to-blue-600 bg-clip-text text-transparent">
                                {step.n}
                            </span>
                            <h3 className="text-white font-semibold mt-3">{step.title}</h3>
                            <p className="text-slate-400 text-sm mt-1 leading-relaxed">{step.desc}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
