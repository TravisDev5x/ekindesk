import { AccordionSimple } from "@/components/ui/accordion";
import { badgeNeutral } from "@/lib/badgeStyles";
import { sectionDefault } from "@/lib/marketingTheme";

const FAQ_ITEMS = [
    {
        value: "1",
        title: "¿Qué es EkinDesk?",
        content:
            "EkinDesk es una plataforma de helpdesk diseñada para empresas MSP — equipos de soporte IT que atienden a múltiples clientes. Centraliza tickets, técnicos, SLAs y clientes en un solo lugar.",
    },
    {
        value: "2",
        title: "¿Puedo usar EkinDesk si solo tengo un cliente?",
        content:
            "Sí. EkinDesk funciona igual para una empresa con un cliente que para una con veinte. Empieza con el plan Starter y escala cuando lo necesites.",
    },
    {
        value: "3",
        title: "¿Cómo ven mis clientes sus tickets?",
        content:
            "Cada cliente tiene acceso a un portal propio donde pueden crear tickets, ver el estado y recibir notificaciones. Nunca ven los tickets de otros clientes.",
    },
    {
        value: "4",
        title: "¿Necesito instalar algo?",
        content:
            "No. EkinDesk es 100% web. Funciona en cualquier navegador moderno sin instalaciones adicionales.",
    },
    {
        value: "5",
        title: "¿Qué pasa cuando termina el período de prueba?",
        content:
            "Te notificaremos antes de que termine. Si decides continuar, seleccionas un plan. Si no, tu cuenta queda pausada sin cargos automáticos.",
    },
    {
        value: "6",
        title: "¿Puedo cambiar de plan después?",
        content:
            "Sí, en cualquier momento desde tu perfil de negocio. Los cambios aplican al siguiente ciclo de facturación.",
    },
    {
        value: "7",
        title: "¿Los datos de mis clientes están seguros?",
        content:
            "Sí. Cada cliente está completamente aislado. Usamos cifrado en tránsito y en reposo, y seguimos buenas prácticas de seguridad en cada capa.",
    },
    {
        value: "8",
        title: "¿Tienen soporte en español?",
        content:
            "Sí. EkinDesk está hecho en México, para el mercado hispanohablante. Soporte en español incluido en todos los planes.",
    },
];

export default function FAQ() {
    return (
        <section id="faq" className={sectionDefault}>
            <div className="mx-auto max-w-7xl">
                <div className="text-center">
                    <span className={`inline-block rounded-full px-4 py-1 text-sm mb-4 ${badgeNeutral}`}>
                        Preguntas
                    </span>
                    <h2 className="text-4xl font-bold text-foreground">Preguntas frecuentes</h2>
                </div>

                <div className="mt-12 max-w-3xl mx-auto [&_details]:mkt-elevated [&_details]:rounded-lg [&_summary]:text-foreground [&_div]:text-muted-foreground">
                    <AccordionSimple items={FAQ_ITEMS} defaultOpen="1" />
                </div>
            </div>
        </section>
    );
}
