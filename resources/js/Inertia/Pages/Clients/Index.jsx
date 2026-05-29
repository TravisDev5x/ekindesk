import { useState, useEffect } from "react";
import { Head, Link, router } from "@inertiajs/react";
import { useFlash } from "@/hooks/useFlash";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import { Building2, Eye, Pencil, Plus, Trash2 } from "lucide-react";

function initials(name) {
    const parts = String(name || "").trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return "??";
    return (parts[0][0] + (parts[1]?.[0] || "")).toUpperCase();
}

function logoUrl(path) {
    if (!path) return null;
    return `/storage/${path.replace(/^\//, "")}`;
}

const TABLE_HEADERS = (
    <TableRow>
        <TableHead>Cliente</TableHead>
        <TableHead>Industria</TableHead>
        <TableHead>Sedes</TableHead>
        <TableHead>Tickets</TableHead>
        <TableHead>Estado</TableHead>
        <TableHead className="text-right">Acciones</TableHead>
    </TableRow>
);

function TableSkeleton() {
    return (
        <Table>
            <TableHeader>{TABLE_HEADERS}</TableHeader>
            <TableBody>
                {[...Array(5)].map((_, i) => (
                    <TableRow key={i}>
                        <TableCell>
                            <div className="flex items-center gap-3">
                                <Skeleton className="h-9 w-9 rounded-md shrink-0" />
                                <Skeleton className="h-4 w-48" />
                            </div>
                        </TableCell>
                        <TableCell>
                            <Skeleton className="h-4 w-24" />
                        </TableCell>
                        <TableCell>
                            <Skeleton className="h-4 w-12" />
                        </TableCell>
                        <TableCell>
                            <Skeleton className="h-4 w-12" />
                        </TableCell>
                        <TableCell>
                            <Skeleton className="h-6 w-16 rounded-full" />
                        </TableCell>
                        <TableCell>
                            <div className="flex justify-end gap-2">
                                <Skeleton className="h-8 w-8" />
                                <Skeleton className="h-8 w-8" />
                                <Skeleton className="h-8 w-8" />
                            </div>
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

export default function Index({ clients, total }) {
    useFlash();
    const rows = clients?.data ?? [];
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        const unsubStart = router.on("start", () => setLoading(true));
        const unsubFinish = router.on("finish", () => setLoading(false));
        return () => {
            unsubStart();
            unsubFinish();
        };
    }, []);

    const handleDelete = () => {
        if (!deleteTarget) return;
        router.delete(`/clients/${deleteTarget.id}`, {
            onFinish: () => setDeleteTarget(null),
        });
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

            {rows.length === 0 && !loading ? (
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
                        {loading ? (
                            <TableSkeleton />
                        ) : (
                            <Table>
                                <TableHeader>{TABLE_HEADERS}</TableHeader>
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
                                                            {initials(
                                                                client.business_name || client.name
                                                            )}
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
                                                    variant={
                                                        client.is_active ? "secondary" : "outline"
                                                    }
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
                                                <TooltipProvider>
                                                    <div className="flex justify-end gap-1">
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    asChild
                                                                >
                                                                    <Link
                                                                        href={`/clients/${client.id}`}
                                                                    >
                                                                        <Eye className="h-4 w-4" />
                                                                    </Link>
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Ver cliente
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    asChild
                                                                >
                                                                    <Link
                                                                        href={`/clients/${client.id}/edit`}
                                                                    >
                                                                        <Pencil className="h-4 w-4" />
                                                                    </Link>
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Editar cliente
                                                            </TooltipContent>
                                                        </Tooltip>
                                                        <Tooltip>
                                                            <TooltipTrigger asChild>
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="text-destructive hover:text-destructive"
                                                                    onClick={() =>
                                                                        setDeleteTarget(client)
                                                                    }
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </Button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>
                                                                Eliminar cliente
                                                            </TooltipContent>
                                                        </Tooltip>
                                                    </div>
                                                </TooltipProvider>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </Card>

                    {!loading && clients.last_page > 1 && (
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

            <AlertDialog
                open={deleteTarget !== null}
                onOpenChange={(open) => {
                    if (!open) setDeleteTarget(null);
                }}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Eliminar cliente?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Se eliminará a{" "}
                            {deleteTarget?.business_name || deleteTarget?.name}. Esta acción no se
                            puede deshacer.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            onClick={handleDelete}
                        >
                            Eliminar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AuthenticatedLayout>
    );
}
