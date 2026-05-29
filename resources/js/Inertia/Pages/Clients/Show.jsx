import { Head, Link, router } from "@inertiajs/react";
import { useFlash } from "@/hooks/useFlash";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Separator } from "@/components/ui/separator";
import { ExternalLink, Pencil, Trash2 } from "lucide-react";

function initials(name) {
    const parts = String(name || "").trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return "??";
    return (parts[0][0] + (parts[1]?.[0] || "")).toUpperCase();
}

export default function Show({ client, tickets_summary, sites }) {
    useFlash();
    const name = client.business_name || client.name;

    const confirmDelete = () => {
        if (!window.confirm(`¿Eliminar a ${name}? Esta acción no se puede deshacer.`)) return;
        router.delete(`/clients/${client.id}`);
    };

    return (
        <AuthenticatedLayout title={name}>
            <Head title={name} />

            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-1">
                    <CardContent className="pt-6 space-y-4">
                        <div className="flex flex-col items-center text-center gap-3">
                            {client.logo_path ? (
                                <img
                                    src={`/storage/${client.logo_path.replace(/^\//, "")}`}
                                    alt=""
                                    className="h-16 w-16 rounded-lg object-cover border"
                                />
                            ) : (
                                <div className="h-16 w-16 rounded-lg bg-muted flex items-center justify-center text-xl font-bold">
                                    {initials(name)}
                                </div>
                            )}
                            <h2 className="text-xl font-bold">{name}</h2>
                            <div className="flex flex-wrap gap-2 justify-center">
                                {client.industry && (
                                    <Badge variant="secondary">{client.industry}</Badge>
                                )}
                                <Badge variant={client.is_active ? "secondary" : "outline"}>
                                    {client.is_active ? "Activo" : "Inactivo"}
                                </Badge>
                            </div>
                        </div>
                        <Separator />
                        <dl className="space-y-2 text-sm">
                            {client.contact_name && (
                                <div>
                                    <dt className="text-muted-foreground text-xs">Contacto</dt>
                                    <dd>{client.contact_name}</dd>
                                </div>
                            )}
                            {client.contact_email && (
                                <div>
                                    <dt className="text-muted-foreground text-xs">Correo</dt>
                                    <dd>
                                        <a
                                            href={`mailto:${client.contact_email}`}
                                            className="text-primary hover:underline"
                                        >
                                            {client.contact_email}
                                        </a>
                                    </dd>
                                </div>
                            )}
                            {client.contact_phone && (
                                <div>
                                    <dt className="text-muted-foreground text-xs">Teléfono</dt>
                                    <dd>{client.contact_phone}</dd>
                                </div>
                            )}
                            {client.website && (
                                <div>
                                    <dt className="text-muted-foreground text-xs">Web</dt>
                                    <dd>
                                        <a
                                            href={client.website}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-primary hover:underline inline-flex items-center gap-1"
                                        >
                                            {client.website}
                                            <ExternalLink className="h-3 w-3" />
                                        </a>
                                    </dd>
                                </div>
                            )}
                        </dl>
                        <Separator />
                        <div className="flex flex-col gap-2">
                            <Button asChild variant="outline" className="w-full">
                                <Link href={`/clients/${client.id}/edit`}>
                                    <Pencil className="h-4 w-4 mr-2" />
                                    Editar
                                </Link>
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                className="w-full"
                                onClick={confirmDelete}
                            >
                                <Trash2 className="h-4 w-4 mr-2" />
                                Eliminar
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <div className="lg:col-span-2 space-y-6">
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Tickets abiertos
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold">{tickets_summary.open}</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Tickets cerrados
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold">{tickets_summary.closed}</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">
                                    Vencidos
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-bold text-destructive">
                                    {tickets_summary.overdue}
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Sedes registradas</CardTitle>
                            <Badge variant="outline">{sites?.length ?? 0}</Badge>
                        </CardHeader>
                        <CardContent>
                            {!sites?.length ? (
                                <div className="text-sm text-muted-foreground space-y-3">
                                    <p>Sin sedes registradas</p>
                                    <Button asChild size="sm" variant="outline">
                                        <Link href={`/clients/${client.id}/edit`}>
                                            Agregar sede
                                        </Link>
                                    </Button>
                                </div>
                            ) : (
                                <ul className="space-y-3">
                                    {sites.map((site) => (
                                        <li
                                            key={site.id}
                                            className="border-b border-border/40 pb-3 last:border-0"
                                        >
                                            <p className="font-medium">{site.name}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {[site.address, site.city]
                                                    .filter(Boolean)
                                                    .join(", ") || "—"}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Tickets recientes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-sm text-muted-foreground italic">
                                Próximamente — integración con módulo de tickets
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
