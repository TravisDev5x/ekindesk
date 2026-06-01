import { useMemo, useState } from "react";
import { Head, Link, useForm } from "@inertiajs/react";
import { useFlash } from "@/hooks/useFlash";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Loader2 } from "lucide-react";
import { OnboardingShell } from "@/components/onboarding/OnboardingShell";
import { OnboardingStepIndicator } from "@/components/onboarding/OnboardingStepIndicator";
import { linkBrand } from "@/lib/marketingTheme";
import { badgeStatus } from "@/lib/badgeStyles";
import { cn } from "@/lib/utils";

function formatMoney(value) {
    const n = Number(value);
    if (!n) return "Contactar ventas";
    return new Intl.NumberFormat("es-MX", { style: "currency", currency: "MXN" }).format(n);
}

export default function OperatorProfile({ plans = [], selectedPlan = null, step = 1 }) {
    useFlash();
    const initialPlanId = selectedPlan?.id ? String(selectedPlan.id) : "";

    const { data, setData, post, processing, errors } = useForm({
        business_name: "",
        rfc: "",
        phone: "",
        website: "",
        address: "",
        city: "",
        country: "MX",
        plan_id: initialPlanId,
        logo: null,
    });

    const [logoPreview, setLogoPreview] = useState(null);

    const activePlan = useMemo(() => {
        if (!data.plan_id) return selectedPlan;
        return plans.find((p) => String(p.id) === String(data.plan_id)) ?? selectedPlan;
    }, [data.plan_id, plans, selectedPlan]);

    const handleLogoChange = (e) => {
        const file = e.target.files?.[0];
        setData("logo", file || null);
        if (!file) {
            setLogoPreview(null);
            return;
        }
        const reader = new FileReader();
        reader.onload = () => setLogoPreview(reader.result);
        reader.readAsDataURL(file);
    };

    const submit = (e) => {
        e.preventDefault();
        post("/onboarding", { forceFormData: true });
    };

    return (
        <OnboardingShell>
            <Head title="Configura tu perfil de negocio" />
            <OnboardingStepIndicator currentStep={step} />

                <div className="text-center space-y-2">
                    <h1 className="text-2xl font-bold tracking-tight">Configura tu perfil de negocio</h1>
                    <p className="text-muted-foreground text-sm">
                        Esta información representa a tu empresa en EkinDesk
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Información de la empresa</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="business_name">Razón social / nombre comercial</Label>
                                <Input
                                    id="business_name"
                                    value={data.business_name}
                                    onChange={(e) => setData("business_name", e.target.value)}
                                    placeholder="TiMagicx S.A. de C.V."
                                    required
                                />
                                {errors.business_name && (
                                    <p className="text-xs text-destructive">{errors.business_name}</p>
                                )}
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="rfc">RFC (opcional)</Label>
                                    <Input
                                        id="rfc"
                                        value={data.rfc}
                                        onChange={(e) => setData("rfc", e.target.value.toUpperCase())}
                                        placeholder="ABC123456789"
                                    />
                                    {errors.rfc && <p className="text-xs text-destructive">{errors.rfc}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="phone">Teléfono (opcional)</Label>
                                    <Input
                                        id="phone"
                                        value={data.phone}
                                        onChange={(e) => setData("phone", e.target.value)}
                                        placeholder="5512345678"
                                        maxLength={10}
                                    />
                                    {errors.phone && <p className="text-xs text-destructive">{errors.phone}</p>}
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="website">Sitio web (opcional)</Label>
                                <Input
                                    id="website"
                                    type="url"
                                    value={data.website}
                                    onChange={(e) => setData("website", e.target.value)}
                                    placeholder="https://timagicx.com"
                                />
                                {errors.website && <p className="text-xs text-destructive">{errors.website}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Dirección</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="address">Dirección</Label>
                                <Input
                                    id="address"
                                    value={data.address}
                                    onChange={(e) => setData("address", e.target.value)}
                                    placeholder="Calle, número, colonia"
                                    required
                                />
                                {errors.address && <p className="text-xs text-destructive">{errors.address}</p>}
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="city">Ciudad</Label>
                                    <Input
                                        id="city"
                                        value={data.city}
                                        onChange={(e) => setData("city", e.target.value)}
                                        placeholder="Ciudad de México"
                                        required
                                    />
                                    {errors.city && <p className="text-xs text-destructive">{errors.city}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label>País</Label>
                                    <Select
                                        value={data.country}
                                        onValueChange={(v) => setData("country", v)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="MX">México</SelectItem>
                                            <SelectItem value="US">Estados Unidos</SelectItem>
                                            <SelectItem value="ES">España</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Logo (opcional)</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <Input type="file" accept="image/*" onChange={handleLogoChange} />
                            {logoPreview && (
                                <img
                                    src={logoPreview}
                                    alt="Vista previa"
                                    className="h-16 w-16 rounded-md border object-cover"
                                />
                            )}
                            <button
                                type="button"
                                className="text-xs text-muted-foreground underline"
                                onClick={() => {
                                    setData("logo", null);
                                    setLogoPreview(null);
                                }}
                            >
                                Omitir por ahora
                            </button>
                            {errors.logo && <p className="text-xs text-destructive">{errors.logo}</p>}
                        </CardContent>
                    </Card>

                    {(activePlan || plans.length > 0) && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-lg">Plan seleccionado</CardTitle>
                                <CardDescription>
                                    <a
                                        href="/landing#pricing"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-primary hover:underline"
                                    >
                                        Ver todos los planes
                                    </a>
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {activePlan ? (
                                    <div className="rounded-lg border border-border p-4 space-y-2">
                                        <div className="flex items-center justify-between gap-2">
                                            <p className="font-semibold">{activePlan.name}</p>
                                            {activePlan.highlighted && (
                                                <Badge
                                                    variant="outline"
                                                    className={cn("text-xs", badgeStatus.brand)}
                                                >
                                                    Recomendado
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            {formatMoney(activePlan.price_monthly)} / mes
                                        </p>
                                        {activePlan.trial_days > 0 && (
                                            <p className="text-xs text-muted-foreground">
                                                Prueba gratis {activePlan.trial_days} días
                                            </p>
                                        )}
                                    </div>
                                ) : null}
                                {plans.length > 0 && (
                                    <div className="space-y-2">
                                        <Label>Cambiar plan</Label>
                                        <Select
                                            value={data.plan_id || "none"}
                                            onValueChange={(v) =>
                                                setData("plan_id", v === "none" ? "" : v)
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Selecciona un plan" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">Sin plan por ahora</SelectItem>
                                                {plans.map((plan) => (
                                                    <SelectItem key={plan.id} value={String(plan.id)}>
                                                        {plan.name} — {formatMoney(plan.price_monthly)}/mes
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                )}
                                <input type="hidden" name="plan_id" value={data.plan_id} readOnly />
                            </CardContent>
                        </Card>
                    )}

                    <Separator />

                    <Button type="submit" className="w-full" disabled={processing}>
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        Continuar →
                    </Button>

                    <p className="text-center text-sm text-muted-foreground">
                        ¿Ya tienes cuenta?{" "}
                        <Link href="/login" className={linkBrand}>
                            Inicia sesión
                        </Link>
                    </p>
                </form>
        </OnboardingShell>
    );
}
