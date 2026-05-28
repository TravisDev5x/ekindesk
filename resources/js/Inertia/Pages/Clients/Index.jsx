import { Head, Link, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Building2, Eye, MoreHorizontal, Pencil, Plus, Trash2 } from "lucide-react";

function initials(name) {
    const parts = String(name || "").trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return "??";
    return (parts[0][0] + (parts[1]?.[0] || "")).toUpperCase();
}

function logoUrl(path) {
    if (!path) return null;
    return `/storage/${path.replace(/^\//, "")}`;
}

export default function Index({ clients, total }) {
    const rows = clients?.data ?? [];

    const confirmDelete = (client) => {
        if (
            !window.confirm(
                `¿Eliminar a ${client.business_name || client.name}? Esta acción no se puede deshacer.`
            )
        ) {
            return;
        }
        router.delete(`/clients/${client.id}`);
    };

    return (
        <AuthenticatedLayout title="Clientes">
            <Head title="Clientes" />

            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div className="flex items-center gap-3">
                    <h1 className="text-2xl font-bold">Clientes</h1>
                    <Badge variant="secondary">{total} total</Badge>
                </div>
                <Button asChild>
                    <Link href="/clients/create">
                        <Plus className="h-4 w-4 mr-2" />
                        Nuevo cliente
                    </Link>
                </Button>
            </div>

            {rows.length === 0 ? (
                <Card className="border-dashed">
                    <CardContent className="flex flex-col items-center justify-center py-16 text-center">
                        <Building2 className="h-12 w-12 text-muted-foreground mb-4" />
                        <h2 className="text-lg font-semibold">Sin clientes registrados</h2>
                        <p className="text-sm text-muted-foreground mt-2 mb-6 max-w-sm">
                            Agrega el primer cliente al que prestas servicios de soporte.
                        </p>
                        <Button asChild>
                            <Link href="/clients/create">Nuevo cliente</Link>
                        </Button>
                    </CardContent>
                </Card>
            ) : (
                <>
                    <Card>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Cliente</TableHead>
                                    <TableHead>Industria</TableHead>
                                    <TableHead>Sedes</TableHead>
                                    <TableHead>Tickets</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead className="text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {rows.map((client) => (
                                    <TableRow key={client.id}>
                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                {client.logo_path ? (
                                                    <img
                                                        src={logoUrl(client.logo_path)}
                                                        alt=""
                                                        className="h-9 w-9 rounded-md object-cover border"
                                                    />
                                                ) : (
                                                    <div className="h-9 w-9 rounded-md bg-muted flex items-center justify-center text-xs font-semibold">
                                                        {initials(client.business_name || client.name)}
                                                    </div>
                                                )}
                                                <span className="font-medium">
                                                    {client.business_name || client.name}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {client.industry ? (
                                                client.industry
                                            ) : (
                                                <Badge variant="outline">—</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>{client.sedes_count ?? 0}</TableCell>
                                        <TableCell>
                                            {(client.tickets_count ?? 0) > 0 ? (
                                                <Badge variant="destructive">
                                                    {client.tickets_count}
                                                </Badge>
                                            ) : (
                                                <span className="text-muted-foreground">0</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={client.is_active ? "secondary" : "outline"}
                                                className={
                                                    client.is_active
                                                        ? "text-emerald-700 dark:text-emerald-400"
                                                        : ""
                                                }
                                            >
                                                {client.is_active ? "Activo" : "Inactivo"}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon">
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/clients/${client.id}`}>
                                                            <Eye className="h-4 w-4 mr-2" />
                                                            Ver
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link href={`/clients/${client.id}/edit`}>
                                                            <Pencil className="h-4 w-4 mr-2" />
                                                            Editar
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        className="text-destructive"
                                                        onClick={() => confirmDelete(client)}
                                                    >
                                                        <Trash2 className="h-4 w-4 mr-2" />
                                                        Eliminar
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </Card>

                    {clients.last_page > 1 && (
                        <div className="flex items-center justify-between mt-4 text-sm">
                            <span className="text-muted-foreground">
                                Página {clients.current_page} de {clients.last_page}
                            </span>
                            <div className="flex gap-2">
                                {clients.prev_page_url && (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={clients.prev_page_url}>Anterior</Link>
                                    </Button>
                                )}
                                {clients.next_page_url && (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={clients.next_page_url}>Siguiente</Link>
                                    </Button>
                                )}
                            </div>
                        </div>
                    )}
                </>
            )}
        </AuthenticatedLayout>
    );
}
