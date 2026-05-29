import { useMemo, useState } from "react";
import { CheckCircle2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Switch } from "@/components/ui/switch";
import { Separator } from "@/components/ui/separator";
import { Badge } from "@/components/ui/badge";

function formatPrice(value) {
    const n = Number(value);
    if (!n) return "0";
    return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
        maximumFractionDigits: 0,
    }).format(n);
}

function PlanTypeBadge({ type }) {
    if (type === "msp") {
        return (
            <Badge className="bg-blue-500/15 text-blue-400 border border-blue-500/20 text-xs">
                MSP
            </Badge>
        );
    }
    if (type === "inhouse") {
        return (
            <Badge className="bg-cyan-500/15 text-cyan-400 border border-cyan-500/20 text-xs">
                In-House
            </Badge>
        );
    }
    if (type === "both") {
        return (
            <Badge className="bg-slate-500/15 text-slate-400 border border-slate-500/20 text-xs">
                Flexible
            </Badge>
        );
    }
    return null;
}

function sortPlans(plans) {
    const enterprise = plans.filter((p) => p.slug === "enterprise");
    const rest = plans
        .filter((p) => p.slug !== "enterprise")
        .sort((a, b) => Number(a.price_monthly) - Number(b.price_monthly));
    return [...rest, ...enterprise];
}

function PlanPrice({ plan, billingCycle }) {
    const monthly = Number(plan.price_monthly);
    const yearly = Number(plan.price_yearly);

    if (monthly === 0) {
        return (
            <>
                <div className="text-4xl font-black text-white">Contactar</div>
                <div className="text-slate-400 text-sm mt-1">Precio a medida</div>
            </>
        );
    }

    if (billingCycle === "yearly" && yearly > 0) {
        const perMonth = Math.round(yearly / 12);
        return (
            <>
                <div className="text-4xl font-black text-white">
                    {formatPrice(perMonth)}
                    <span className="text-slate-400 text-lg font-normal">/mes</span>
                </div>
                <div className="text-slate-500 text-sm mt-1">
                    facturado anualmente ·{" "}
                    <span className="line-through">{formatPrice(monthly)}/mes</span>
                </div>
            </>
        );
    }

    return (
        <div className="text-4xl font-black text-white">
            {formatPrice(monthly)}
            <span className="text-slate-400 text-lg font-normal">/mes</span>
        </div>
    );
}

function PlanCard({ plan, billingCycle }) {
    const highlighted = Boolean(plan.highlighted);
    const isContact = Number(plan.price_monthly) === 0;

    return (
        <div
            className={`relative flex flex-col rounded-2xl border bg-slate-900 p-8 transition-all duration-200 ${
                highlighted
                    ? "border-cyan-500/70 shadow-2xl shadow-cyan-500/10"
                    : "border-slate-800"
            }`}
        >
            {highlighted && (
                <Badge className="absolute -top-3.5 left-1/2 -translate-x-1/2 whitespace-nowrap border-0 bg-gradient-to-r from-cyan-500 to-blue-600 px-4 py-1 text-xs font-bold text-white">
                    Más popular
                </Badge>
            )}

            <div className="mb-2 flex items-center justify-between gap-2">
                <span className="text-sm font-medium uppercase tracking-wider text-slate-400">
                    {plan.name}
                </span>
                <PlanTypeBadge type={plan.type} />
            </div>

            <div className="mb-2 mt-4">
                <PlanPrice plan={plan} billingCycle={billingCycle} />
            </div>

            {plan.trial_days > 0 && (
                <div className="mt-1 text-xs text-cyan-400">
                    {plan.trial_days} días gratis para empezar
                </div>
            )}

            <Separator className="my-5 bg-slate-800" />

            <div className="mb-1">
                {plan.max_clients != null && (
                    <span className="mb-1 mr-1 inline-flex rounded-full bg-slate-800 px-3 py-1 text-xs text-slate-300">
                        {plan.max_clients} clientes
                    </span>
                )}
                <span className="mb-1 mr-1 inline-flex rounded-full bg-slate-800 px-3 py-1 text-xs text-slate-300">
                    {plan.max_users ?? "∞"} usuarios
                </span>
                <span className="mb-1 mr-1 inline-flex rounded-full bg-slate-800 px-3 py-1 text-xs text-slate-300">
                    {plan.max_agents ?? "∞"} agentes
                </span>
            </div>

            <div className="mt-4 flex flex-1 flex-col">
                <ul className="space-y-2">
                    {(plan.features || []).map((feature) => (
                        <li key={feature} className="flex items-start gap-2">
                            <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0 text-cyan-400" />
                            <span className="text-sm text-slate-300">{feature}</span>
                        </li>
                    ))}
                </ul>
            </div>

            <div className="mt-auto pt-6">
                {isContact ? (
                    <Button
                        type="button"
                        variant="outline"
                        className="h-11 w-full rounded-lg border-slate-700 bg-transparent font-semibold text-slate-300 shadow-none transition-all duration-200 hover:border-cyan-500 hover:bg-transparent hover:text-cyan-400"
                        onClick={() => {
                            window.location.href = "mailto:ventas@ekindesk.com";
                        }}
                    >
                        Contactar ventas
                    </Button>
                ) : (
                    <Button
                        type="button"
                        className={`h-11 w-full rounded-lg font-semibold transition-all duration-200 ${
                            highlighted
                                ? "border-0 bg-gradient-to-r from-cyan-500 to-blue-600 text-white hover:from-cyan-400 hover:to-blue-500"
                                : "border border-slate-700 bg-transparent text-slate-300 hover:border-cyan-500 hover:text-cyan-400"
                        }`}
                        variant={highlighted ? "default" : "outline"}
                        onClick={() => {
                            window.location.href = `/register?plan=${plan.slug}`;
                        }}
                    >
                        Empezar gratis
                    </Button>
                )}
            </div>
        </div>
    );
}

export default function Pricing({ plans = [] }) {
    const [activeTab, setActiveTab] = useState("msp");
    const [billingCycle, setBillingCycle] = useState("monthly");
    const isYearly = billingCycle === "yearly";

    const mspPlans = useMemo(
        () =>
            sortPlans(plans.filter((p) => p.type === "msp" || p.type === "both")),
        [plans]
    );
    const inhousePlans = useMemo(
        () =>
            sortPlans(plans.filter((p) => p.type === "inhouse" || p.type === "both")),
        [plans]
    );

    const activePlans = activeTab === "msp" ? mspPlans : inhousePlans;

    return (
        <section id="pricing" className="bg-slate-900/50 px-6 py-24">
            <div className="mx-auto max-w-7xl">
                <div className="text-center">
                    <span className="mb-4 inline-block rounded-full border border-blue-500/20 bg-blue-500/10 px-4 py-1 text-sm text-blue-400">
                        Planes
                    </span>
                    <h2 className="text-center text-4xl font-bold text-white">
                        Precios simples, sin sorpresas
                    </h2>
                    <p className="mx-auto mt-3 max-w-xl text-center text-slate-400">
                        Empieza gratis 14 días. Sin tarjeta de crédito.
                    </p>
                </div>

                <div className="mt-10 flex justify-center">
                    <div className="inline-flex gap-1 rounded-xl border border-slate-700/50 bg-slate-800/60 p-1.5">
                        <button
                            type="button"
                            onClick={() => setActiveTab("msp")}
                            className={
                                activeTab === "msp"
                                    ? "rounded-lg bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white shadow-md ring-1 ring-slate-600/50 transition-all duration-200"
                                    : "cursor-pointer rounded-lg px-5 py-2.5 text-sm text-slate-500 transition-all duration-200 hover:text-slate-300"
                            }
                        >
                            <div className="text-center">
                                <div className="font-semibold">Para empresas MSP</div>
                                <div className="mt-0.5 text-xs opacity-70">
                                    Presta soporte a tus clientes
                                </div>
                            </div>
                        </button>
                        <button
                            type="button"
                            onClick={() => setActiveTab("inhouse")}
                            className={
                                activeTab === "inhouse"
                                    ? "rounded-lg bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white shadow-md ring-1 ring-slate-600/50 transition-all duration-200"
                                    : "cursor-pointer rounded-lg px-5 py-2.5 text-sm text-slate-500 transition-all duration-200 hover:text-slate-300"
                            }
                        >
                            <div className="text-center">
                                <div className="font-semibold">Para uso interno</div>
                                <div className="mt-0.5 text-xs opacity-70">Gestiona tu IT interno</div>
                            </div>
                        </button>
                    </div>
                </div>

                <p className="mt-3 text-center text-sm text-slate-500">
                    {activeTab === "msp"
                        ? "Gestiona múltiples empresas desde un solo panel"
                        : "Tu equipo IT y tus empleados en un solo lugar"}
                </p>

                <div className="mt-8 flex items-center justify-center gap-3">
                    <span className="text-sm text-slate-400">Mensual</span>
                    <Switch
                        checked={isYearly}
                        onCheckedChange={(checked) =>
                            setBillingCycle(checked ? "yearly" : "monthly")
                        }
                        className="data-[state=checked]:bg-cyan-500"
                    />
                    <span className="text-sm text-slate-400">Anual</span>
                    {isYearly && (
                        <Badge className="border border-cyan-500/30 bg-cyan-500/20 text-xs text-cyan-400">
                            2 meses gratis
                        </Badge>
                    )}
                </div>

                <div className="mt-12 grid grid-cols-1 items-stretch gap-6 lg:grid-cols-3">
                    {activePlans.map((plan) => (
                        <PlanCard
                            key={`${activeTab}-${plan.id}`}
                            plan={plan}
                            billingCycle={billingCycle}
                        />
                    ))}
                </div>
            </div>
        </section>
    );
}
