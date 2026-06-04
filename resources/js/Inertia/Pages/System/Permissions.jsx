import { useCallback, useEffect, useMemo, useState } from "react";
import { router, usePage } from "@inertiajs/react";
import axios from "@/lib/axios";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import InertiaPageShell from "@/Inertia/components/InertiaPageShell";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
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
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { notify } from "@/lib/notify";
import { cn } from "@/lib/utils";
import { statusDotWarning } from "@/lib/badgeStyles";
import { getApiErrorMessage } from "@/lib/apiErrors";
import { Loader2, Plus, Save, ShieldCheck, Trash2 } from "lucide-react";

const LABELS = {
    "users.manage": "Gestionar usuarios",
    "roles.manage": "Gestionar roles",
    "permissions.manage": "Gestionar permisos",
    "catalogs.manage": "Gestionar catálogos",
    "notifications.manage": "Gestionar notificaciones",
};

const formatLabel = (name) =>
    LABELS[name] || name.split(".").pop().replace(/\b\w/g, (c) => c.toUpperCase());

function buildMatrix(roles) {
    const map = {};
    (roles ?? []).forEach((role) => {
        map[role.id] = new Set((role.permissions ?? []).map((p) => p.id));
    });
    return map;
}

function setsEqual(a, b) {
    if (a.size !== b.size) return false;
    for (const v of a) if (!b.has(v)) return false;
    return true;
}

export default function Permissions() {
    const { roles: initialRoles, permissions: initialPermissions } = usePage().props;
    const { auth } = usePage().props;
    const perms = auth?.user?.permissions ?? [];
    const canMutate =
        perms.includes("permissions.manage") || perms.includes("roles.manage");

    const webPermissions = useMemo(
        () => (initialPermissions ?? []).filter((p) => p.guard_name === "web"),
        [initialPermissions]
    );

    const [matrix, setMatrix] = useState(() => buildMatrix(initialRoles));
    const [newPermission, setNewPermission] = useState("");
    const [creating, setCreating] = useState(false);
    const [savingRole, setSavingRole] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);

    useEffect(() => {
        setMatrix(buildMatrix(initialRoles));
    }, [initialRoles]);

    const groupedPermissions = useMemo(() => {
        const groups = {};
        webPermissions.forEach((perm) => {
            const [category] = perm.name.split(".");
            if (!groups[category]) groups[category] = [];
            groups[category].push(perm);
        });
        return groups;
    }, [webPermissions]);

    const togglePermission = (roleId, permissionId, checked) => {
        if (!canMutate) return;
        setMatrix((prev) => {
            const next = { ...prev };
            const set = new Set(prev[roleId] ?? []);
            if (checked) set.add(permissionId);
            else set.delete(permissionId);
            next[roleId] = set;
            return next;
        });
    };

    const isRoleDirty = useCallback(
        (role) => {
            const original = new Set((role.permissions ?? []).map((p) => p.id));
            const current = matrix[role.id] ?? new Set();
            return !setsEqual(original, current);
        },
        [matrix]
    );

    const saveRolePermissions = async (role) => {
        setSavingRole(role.id);
        try {
            const ids = [...(matrix[role.id] ?? [])];
            await axios.post(`/api/roles/${role.id}/permissions`, { permissions: ids });
            notify.success(`Permisos de "${role.name}" actualizados`);
            router.reload({ only: ["roles", "permissions"] });
        } catch (err) {
            notify.error(getApiErrorMessage(err, "Error al guardar permisos"));
        } finally {
            setSavingRole(null);
        }
    };

    const createPermission = async (e) => {
        e.preventDefault();
        const name = newPermission.trim();
        if (name.length < 3 || !canMutate) return;
        setCreating(true);
        try {
            await axios.post("/api/permissions", { name });
            setNewPermission("");
            notify.success("Permiso creado");
            router.reload({ only: ["roles", "permissions"] });
        } catch (err) {
            notify.error(getApiErrorMessage(err, "Error al crear permiso"));
        } finally {
            setCreating(false);
        }
    };

    const confirmDeletePermission = async () => {
        if (!deleteTarget || !canMutate) return;
        try {
            await axios.delete(`/api/permissions/${deleteTarget.id}`);
            notify.success("Permiso eliminado");
            setDeleteTarget(null);
            router.reload({ only: ["roles", "permissions"] });
        } catch (err) {
            notify.error(getApiErrorMessage(err, "Error al eliminar"));
        }
    };

    const roles = initialRoles ?? [];

    return (
        <>
            <InertiaPageShell className="space-y-6">
                <header className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <p className="text-sm text-muted-foreground max-w-2xl">
                        Matriz de control de acceso por rol
                    </p>
                    {!canMutate && (
                        <Badge variant="secondary" className="uppercase text-[10px]">
                            Solo lectura
                        </Badge>
                    )}
                </header>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Nuevo permiso</CardTitle>
                        <CardDescription>
                            Crea un permiso con formato módulo.acción (ej. tickets.view)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={createPermission} className="flex flex-wrap gap-2">
                            <Input
                                placeholder="ej: tickets.export"
                                value={newPermission}
                                onChange={(e) => setNewPermission(e.target.value)}
                                className="max-w-xs"
                                disabled={!canMutate}
                            />
                            <Button
                                type="submit"
                                disabled={!canMutate || creating || newPermission.trim().length < 3}
                            >
                                {creating ? (
                                    <Loader2 className="h-4 w-4 animate-spin mr-2" />
                                ) : (
                                    <Plus className="h-4 w-4 mr-2" />
                                )}
                                Crear
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Tabs defaultValue={roles[0] ? String(roles[0].id) : undefined}>
                    <TabsList className="flex h-auto flex-wrap justify-start gap-1 bg-muted/50 p-1">
                        {roles.map((role) => (
                            <TabsTrigger key={role.id} value={String(role.id)} className="text-sm">
                                {role.name}
                                {isRoleDirty(role) && (
                                    <span className={cn("ml-1.5 h-1.5 w-1.5", statusDotWarning)} />
                                )}
                            </TabsTrigger>
                        ))}
                    </TabsList>

                    {roles.map((role) => (
                        <TabsContent key={role.id} value={String(role.id)} className="mt-4">
                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 border-b bg-muted/20">
                                    <div>
                                        <CardTitle className="text-lg">{role.name}</CardTitle>
                                        <CardDescription>
                                            Marca los permisos asignados a este rol
                                        </CardDescription>
                                    </div>
                                    <Button
                                        size="sm"
                                        disabled={
                                            !canMutate ||
                                            !isRoleDirty(role) ||
                                            savingRole === role.id
                                        }
                                        onClick={() => saveRolePermissions(role)}
                                    >
                                        {savingRole === role.id ? (
                                            <Loader2 className="h-4 w-4 animate-spin mr-2" />
                                        ) : (
                                            <Save className="h-4 w-4 mr-2" />
                                        )}
                                        Guardar permisos de {role.name}
                                    </Button>
                                </CardHeader>
                                <CardContent className="pt-6 space-y-8">
                                    {Object.entries(groupedPermissions).map(([category, perms]) => (
                                        <div key={category} className="space-y-3">
                                            <div className="flex items-center gap-2">
                                                <h4 className="text-xs font-bold uppercase tracking-widest text-primary/70">
                                                    {category}
                                                </h4>
                                                <Separator className="flex-1" />
                                            </div>
                                            <div className="grid grid-cols-1 gap-2 md:grid-cols-2">
                                                {perms.map((perm) => (
                                                    <div
                                                        key={perm.id}
                                                        className="group flex items-center justify-between rounded-lg border border-muted p-3 hover:border-primary/20 hover:bg-primary/[0.02]"
                                                    >
                                                        <div className="flex items-center gap-3">
                                                            <Checkbox
                                                                id={`r${role.id}-p${perm.id}`}
                                                                checked={(matrix[role.id] ?? new Set()).has(
                                                                    perm.id
                                                                )}
                                                                onCheckedChange={(val) =>
                                                                    togglePermission(
                                                                        role.id,
                                                                        perm.id,
                                                                        Boolean(val)
                                                                    )
                                                                }
                                                                disabled={!canMutate}
                                                            />
                                                            <label
                                                                htmlFor={`r${role.id}-p${perm.id}`}
                                                                className="cursor-pointer"
                                                            >
                                                                <div className="text-sm font-medium">
                                                                    {formatLabel(perm.name)}
                                                                </div>
                                                                <div className="font-mono text-[10px] text-muted-foreground">
                                                                    {perm.name}
                                                                </div>
                                                            </label>
                                                        </div>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            className="h-7 w-7 text-destructive opacity-0 transition-opacity group-hover:opacity-100"
                                                            disabled={!canMutate}
                                                            onClick={() => setDeleteTarget(perm)}
                                                        >
                                                            <Trash2 className="h-3.5 w-3.5" />
                                                        </Button>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        </TabsContent>
                    ))}
                </Tabs>
            </InertiaPageShell>

            <AlertDialog open={!!deleteTarget} onOpenChange={(open) => !open && setDeleteTarget(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>¿Eliminar permiso?</AlertDialogTitle>
                        <AlertDialogDescription>
                            ¿Eliminar &quot;{deleteTarget?.name}&quot;? Se quitará de todos los roles.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancelar</AlertDialogCancel>
                        <AlertDialogAction
                            className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                            onClick={confirmDeletePermission}
                        >
                            Eliminar
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}

Permissions.layout = (page) => <AuthenticatedLayout title="Permisos">{page}</AuthenticatedLayout>;
