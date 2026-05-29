import { useState } from "react";
import { Head, Link, useForm, router } from "@inertiajs/react";
import { useFlash } from "@/hooks/useFlash";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { ChevronDown, ChevronUp, Loader2 } from "lucide-react";

function ProgressSteps({ step }) {
    return (
        <div className="flex items-center justify-center gap-3 mb-8">
            <div className="flex items-center gap-2 opacity-60">
                <span className="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-600 text-white text-sm">
                    ✓
                </span>
                <span className="text-sm">Perfil de negocio</span>
            </div>
            <div className="h-px w-12 bg-border" />
            <div className="flex items-center gap-2">
                <span className="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-semibold">
                    {step}
                </span>
                <span className="text-sm font-medium">Primer cliente</span>
            </div>
        </div>
    );
}

export default function ClientsStep({ step = 2, operator_name, existing_clients = 0 }) {
    useFlash();
    const [sitesOpen, setSitesOpen] = useState(false);
    const [skipping, setSkipping] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        business_name: "",
        industry: "",
        phone: "",
        contact_name: "",
        contact_email: "",
        site_name: "",
        site_address: "",
        site_city: "",
    });

    const submit = (e) => {
        e.preventDefault();
        post("/onboarding/clients");
    };

    const skip = () => {
        setSkipping(true);
        router.post("/onboarding/skip", {}, {
            onFinish: () => setSkipping(false),
        });
    };

    return (
        <div className="min-h-[100dvh] bg-background py-10 px-4">
            <Head title="Agregar primer cliente" />
            <div className="mx-auto max-w-2xl space-y-6">
                <ProgressSteps step={step} />

                <div className="text-center space-y-2">
                    <h1 className="text-2xl font-bold tracking-tight">Agrega tu primer cliente</h1>
                    <p className="text-muted-foreground text-sm max-w-lg mx-auto">
                        Los clientes son las empresas a las que{" "}
                        <strong>{operator_name}</strong> presta servicios de soporte.
                    </p>
                </div>

                {existing_clients > 0 && (
                    <Card className="border-border/60 bg-muted/30">
                        <CardContent className="py-4 text-sm text-muted-foreground">
                            Ya tienes {existing_clients} cliente(s) registrado(s). Puedes agregar otro o
                            continuar al panel.
                        </CardContent>
                    </Card>
                )}

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Empresa cliente</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="business_name">Razón social / nombre</Label>
                                <Input
                                    id="business_name"
                                    value={data.business_name}
                                    onChange={(e) => setData("business_name", e.target.value)}
                                    placeholder="Soffa S.A. de C.V."
                                    required
                                />
                                {errors.business_name && (
                                    <p className="text-xs text-destructive">{errors.business_name}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="industry">Industria</Label>
                                <Input
                                    id="industry"
                                    value={data.industry}
                                    onChange={(e) => setData("industry", e.target.value)}
                                    placeholder="Retail, Manufactura, Tecnología..."
                                />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="contact_name">Contacto principal</Label>
                                    <Input
                                        id="contact_name"
                                        value={data.contact_name}
                                        onChange={(e) => setData("contact_name", e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="contact_email">Correo de contacto</Label>
                                    <Input
                                        id="contact_email"
                                        type="email"
                                        value={data.contact_email}
                                        onChange={(e) => setData("contact_email", e.target.value)}
                                        placeholder="contacto@soffa.com"
                                    />
                                    {errors.contact_email && (
                                        <p className="text-xs text-destructive">{errors.contact_email}</p>
                                    )}
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="phone">Teléfono</Label>
                                <Input
                                    id="phone"
                                    value={data.phone}
                                    onChange={(e) => setData("phone", e.target.value)}
                                    placeholder="5512345678"
                                    maxLength={10}
                                />
                                {errors.phone && (
                                    <p className="text-xs text-destructive">{errors.phone}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader
                            className="cursor-pointer flex flex-row items-center justify-between"
                            onClick={() => setSitesOpen((v) => !v)}
                        >
                            <div>
                                <CardTitle className="text-lg">Agregar sede</CardTitle>
                                <CardDescription className="text-xs">Opcional — primera ubicación</CardDescription>
                            </div>
                            {sitesOpen ? (
                                <ChevronUp className="h-5 w-5 text-muted-foreground" />
                            ) : (
                                <ChevronDown className="h-5 w-5 text-muted-foreground" />
                            )}
                        </CardHeader>
                        <div
                            className={`overflow-hidden transition-all duration-300 ${
                                sitesOpen ? "max-h-96 opacity-100" : "max-h-0 opacity-0"
                            }`}
                        >
                            <CardContent className="space-y-4 pt-0">
                                <div className="space-y-2">
                                    <Label htmlFor="site_name">Nombre de sede</Label>
                                    <Input
                                        id="site_name"
                                        value={data.site_name}
                                        onChange={(e) => setData("site_name", e.target.value)}
                                        placeholder="Oficina Central"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="site_address">Dirección</Label>
                                    <Input
                                        id="site_address"
                                        value={data.site_address}
                                        onChange={(e) => setData("site_address", e.target.value)}
                                        placeholder="Av. Insurgentes 123, Col. Roma"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="site_city">Ciudad</Label>
                                    <Input
                                        id="site_city"
                                        value={data.site_city}
                                        onChange={(e) => setData("site_city", e.target.value)}
                                        placeholder="Ciudad de México"
                                    />
                                </div>
                            </CardContent>
                        </div>
                    </Card>

                    <div className="flex flex-col-reverse sm:flex-row sm:justify-between gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={skip}
                            disabled={processing || skipping}
                        >
                            {skipping && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Omitir por ahora
                        </Button>
                        <Button type="submit" disabled={processing || skipping} className="sm:min-w-[240px]">
                            {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                            Agregar cliente y finalizar →
                        </Button>
                    </div>
                </form>

                <p className="text-center text-sm text-muted-foreground">
                    ¿Ya tienes cuenta?{" "}
                    <Link href="/login" className="text-primary hover:underline">
                        Inicia sesión
                    </Link>
                </p>
            </div>
        </div>
    );
}
