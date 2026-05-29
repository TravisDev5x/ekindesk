import { useState } from "react";
import { Head, Link, router, useForm } from "@inertiajs/react";
import { useFlash } from "@/hooks/useFlash";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { storageUrl } from "@/lib/storage";
import { ArrowLeft, Loader2 } from "lucide-react";

const COUNTRIES = [
    { value: "MX", label: "México" },
    { value: "US", label: "Estados Unidos" },
    { value: "ES", label: "España" },
];

export default function CompanyEdit({ profile }) {
    useFlash();

    const { data, setData, put, processing, errors } = useForm({
        business_name: profile.business_name ?? "",
        rfc: profile.rfc ?? "",
        phone: profile.phone ?? "",
        website: profile.website ?? "",
        address: profile.address ?? "",
        city: profile.city ?? "",
        country: profile.country ?? "MX",
        logo: null,
    });

    const [logoPreview, setLogoPreview] = useState(storageUrl(profile.logo_path));

    const onLogoChange = (e) => {
        const file = e.target.files?.[0];
        setData("logo", file || null);
        if (!file) {
            setLogoPreview(storageUrl(profile.logo_path));
            return;
        }
        const reader = new FileReader();
        reader.onload = () => setLogoPreview(reader.result);
        reader.readAsDataURL(file);
    };

    const deleteLogo = () => {
        router.delete("/company/logo", {
            preserveScroll: true,
            onSuccess: () => {
                setLogoPreview(null);
                setData("logo", null);
            },
        });
    };

    const submit = (e) => {
        e.preventDefault();
        put("/company", { forceFormData: true });
    };

    return (
        <>
            <Head title="Editar perfil de empresa" />

            <Link
                href="/company"
                className="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground mb-6"
            >
                <ArrowLeft className="h-4 w-4" />
                Volver a mi empresa
            </Link>

            <form onSubmit={submit} className="space-y-6 max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Identidad de la empresa</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="business_name">Razón social / nombre</Label>
                            <Input
                                id="business_name"
                                value={data.business_name}
                                onChange={(e) => setData("business_name", e.target.value)}
                                required
                            />
                            {errors.business_name && (
                                <p className="text-xs text-destructive">{errors.business_name}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="rfc">RFC (opcional)</Label>
                            <Input
                                id="rfc"
                                value={data.rfc}
                                onChange={(e) => setData("rfc", e.target.value.toUpperCase())}
                                maxLength={13}
                                placeholder="RFC (opcional)"
                            />
                            {errors.rfc && (
                                <p className="text-xs text-destructive">{errors.rfc}</p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Contacto</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="phone">Teléfono</Label>
                            <Input
                                id="phone"
                                value={data.phone}
                                onChange={(e) => setData("phone", e.target.value)}
                                placeholder="10 dígitos"
                                maxLength={10}
                            />
                            {errors.phone && (
                                <p className="text-xs text-destructive">{errors.phone}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="website">Sitio web</Label>
                            <Input
                                id="website"
                                type="url"
                                value={data.website}
                                onChange={(e) => setData("website", e.target.value)}
                                placeholder="https://..."
                            />
                            {errors.website && (
                                <p className="text-xs text-destructive">{errors.website}</p>
                            )}
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
                                required
                            />
                            {errors.address && (
                                <p className="text-xs text-destructive">{errors.address}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="city">Ciudad</Label>
                            <Input
                                id="city"
                                value={data.city}
                                onChange={(e) => setData("city", e.target.value)}
                                required
                            />
                            {errors.city && (
                                <p className="text-xs text-destructive">{errors.city}</p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="country">País</Label>
                            <Select
                                value={data.country}
                                onValueChange={(v) => setData("country", v)}
                            >
                                <SelectTrigger id="country">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {COUNTRIES.map((c) => (
                                        <SelectItem key={c.value} value={c.value}>
                                            {c.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {errors.country && (
                                <p className="text-xs text-destructive">{errors.country}</p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Logotipo</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {logoPreview && (
                            <div className="flex items-center gap-4">
                                <img
                                    src={logoPreview}
                                    alt=""
                                    className="h-20 w-20 rounded-lg object-cover border border-border"
                                />
                                <Button type="button" variant="destructive" size="sm" onClick={deleteLogo}>
                                    Eliminar logo
                                </Button>
                            </div>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="logo">Subir imagen</Label>
                            <Input
                                id="logo"
                                type="file"
                                accept="image/*"
                                onChange={onLogoChange}
                            />
                            <p className="text-xs text-muted-foreground">
                                Tamaño máximo 2MB. PNG, JPG, WebP.
                            </p>
                            {errors.logo && (
                                <p className="text-xs text-destructive">{errors.logo}</p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <div className="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end pb-8">
                    <Button type="button" variant="outline" asChild>
                        <Link href="/company">Cancelar</Link>
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        {processing ? "Guardando..." : "Guardar cambios"}
                    </Button>
                </div>
            </form>
        </>
    );
}

CompanyEdit.layout = (page) => <AuthenticatedLayout>{page}</AuthenticatedLayout>;
