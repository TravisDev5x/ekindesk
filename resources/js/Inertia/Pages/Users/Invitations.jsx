import { useCallback, useEffect, useMemo, useState } from "react";
import { Head, Link } from "@inertiajs/react";
import axios from "@/lib/axios";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from "@/components/ui/dialog";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Label } from "@/components/ui/label";
import { notify } from "@/lib/notify";
import { invitationStatusClass, statValue } from "@/lib/badgeStyles";
import { cn } from "@/lib/utils";
import { UserPlus, Loader2, RefreshCw, XCircle, ArrowLeft } from "lucide-react";

const STATUS_LABELS = {
    pending: "Pendiente",
    accepted: "Aceptada",
    expired: "Expirada",
};

function formatDate(iso) {
    if (!iso) return "—";
    try {
        return new Date(iso).toLocaleString("es-MX", { dateStyle: "short", timeStyle: "short" });
    } catch {
        return iso;
    }
}

export default function Invitations() {
    const [items, setItems] = useState([]);
    const [loading, setLoading] = useState(true);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [roles, setRoles] = useState([]);
    const [clients, setClients] = useState([]);
    const [form, setForm] = useState({ email: "", role_id: "", client_id: "" });

    const fetchInvitations = useCallback(async () => {
        setLoading(true);
        try {
            const { data } = await axios.get("/api/invitations", { params: { per_page: 100 } });
            setItems(data.data ?? []);
        } catch {
            notify.error("No se pudieron cargar las invitaciones");
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchInvitations();
    }, [fetchInvitations]);

    useEffect(() => {
        if (!dialogOpen) return;
        (async () => {
            try {
                const [rolesRes, clientsRes] = await Promise.all([
                    axios.get("/api/roles"),
                    axios.get("/api/clients").catch(() => ({ data: [] })),
                ]);
                setRoles(Array.isArray(rolesRes.data) ? rolesRes.data : rolesRes.data?.data ?? []);
                const rawClients = clientsRes.data;
                setClients(Array.isArray(rawClients) ? rawClients : rawClients?.data ?? []);
            } catch {
                notify.error("No se pudieron cargar catálogos");
            }
        })();
    }, [dialogOpen]);

    const stats = useMemo(() => {
        const counts = { pending: 0, accepted: 0, expired: 0 };
        items.forEach((row) => {
            const s = row.status || "expired";
            if (counts[s] !== undefined) counts[s]++;
        });
        return counts;
    }, [items]);

    const handleCreate = async (e) => {
        e.preventDefault();
        if (!form.email.trim()) {
            notify.error("Indica el correo del invitado");
            return;
        }
        setSubmitting(true);
        try {
            await axios.post("/api/invitations", {
                email: form.email.trim(),
                ...(form.role_id ? { role_id: Number(form.role_id) } : {}),
                client_id: form.client_id && form.client_id !== "none" ? Number(form.client_id) : null,
            });
            notify.success("Invitación enviada");
            setDialogOpen(false);
            setForm({ email: "", role_id: "", client_id: "" });
            fetchInvitations();
        } catch (err) {
            const msg = err?.response?.data?.message;
            const emailErr = err?.response?.data?.errors?.email?.[0];
            notify.error(emailErr || msg || "No se pudo enviar la invitación");
        } finally {
            setSubmitting(false);
        }
    };

    const handleCancel = async (id) => {
        try {
            await axios.delete(`/api/invitations/${id}`);
            notify.success("Invitación cancelada");
            fetchInvitations();
        } catch {
            notify.error("No se pudo cancelar");
        }
    };

    const handleResend = async (id) => {
        try {
            await axios.post(`/api/invitations/${id}/resend`);
            notify.success("Invitación reenviada");
            fetchInvitations();
        } catch {
            notify.error("No se pudo reenviar");
        }
    };

    return (
        <AuthenticatedLayout title="Invitaciones">
            <Head title="Invitaciones" />

            <div className="space-y-4 pb-12">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-sm text-muted-foreground mb-1">
                            <Link href="/users" className="hover:text-foreground inline-flex items-center gap-1">
                                <ArrowLeft className="h-3.5 w-3.5" /> Usuarios
                            </Link>
                        </div>
                        <p className="text-sm text-muted-foreground mt-0.5">
                            Envía enlaces de registro por correo en lugar de crear contraseñas manualmente.
                        </p>
                    </div>
                    <Button size="sm" onClick={() => setDialogOpen(true)}>
                        <UserPlus className="h-4 w-4 mr-2" />
                        Nueva invitación
                    </Button>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-xs text-muted-foreground uppercase tracking-wide">Pendientes</p>
                            <p className={statValue.default}>{stats.pending}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-xs text-muted-foreground uppercase tracking-wide">Aceptadas</p>
                            <p className={statValue.success}>{stats.accepted}</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-xs text-muted-foreground uppercase tracking-wide">Expiradas</p>
                            <p className={statValue.muted}>{stats.expired}</p>
                        </CardContent>
                    </Card>
                </div>

                <Card className="overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b bg-muted/40 text-left text-xs uppercase tracking-wide text-muted-foreground">
                                    <th className="p-3 font-medium">Email</th>
                                    <th className="p-3 font-medium">Rol</th>
                                    <th className="p-3 font-medium">Cliente</th>
                                    <th className="p-3 font-medium">Estado</th>
                                    <th className="p-3 font-medium">Expira</th>
                                    <th className="p-3 font-medium">Enviada por</th>
                                    <th className="p-3 font-medium text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {loading ? (
                                    <tr>
                                        <td colSpan={7} className="p-8 text-center text-muted-foreground">
                                            <Loader2 className="h-5 w-5 animate-spin inline mr-2" />
                                            Cargando…
                                        </td>
                                    </tr>
                                ) : items.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="p-8 text-center text-muted-foreground">
                                            No hay invitaciones.
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((row) => (
                                        <tr key={row.id} className="border-b hover:bg-muted/20">
                                            <td className="p-3 font-mono text-xs">{row.email}</td>
                                            <td className="p-3">{row.role_name || "—"}</td>
                                            <td className="p-3">{row.client_name || "Staff interno"}</td>
                                            <td className="p-3">
                                                <Badge
                                                    variant="outline"
                                                    className={cn(
                                                        "text-xs",
                                                        invitationStatusClass(row.status)
                                                    )}
                                                >
                                                    {STATUS_LABELS[row.status] || row.status}
                                                </Badge>
                                            </td>
                                            <td className="p-3 text-xs text-muted-foreground">
                                                {row.status === "accepted"
                                                    ? formatDate(row.accepted_at)
                                                    : formatDate(row.expires_at)}
                                            </td>
                                            <td className="p-3 text-xs">{row.invited_by_name || "—"}</td>
                                            <td className="p-3 text-right space-x-1">
                                                {row.status === "pending" && (
                                                    <>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="h-8 text-destructive"
                                                            onClick={() => handleCancel(row.id)}
                                                        >
                                                            <XCircle className="h-3.5 w-3.5 mr-1" />
                                                            Cancelar
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            className="h-8"
                                                            onClick={() => handleResend(row.id)}
                                                        >
                                                            <RefreshCw className="h-3.5 w-3.5 mr-1" />
                                                            Reenviar
                                                        </Button>
                                                    </>
                                                )}
                                                {row.status === "expired" && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="h-8"
                                                        onClick={() => handleResend(row.id)}
                                                    >
                                                        <RefreshCw className="h-3.5 w-3.5 mr-1" />
                                                        Reenviar
                                                    </Button>
                                                )}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                </Card>

                <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Nueva invitación</DialogTitle>
                            <DialogDescription>
                                Se enviará un correo con enlace válido 48 h. El invitado activará su cuenta y un
                                administrador le asignará el rol según su puesto.
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleCreate} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="inv-email">Correo</Label>
                                <Input
                                    id="inv-email"
                                    type="email"
                                    required
                                    value={form.email}
                                    onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
                                    placeholder="usuario@empresa.com"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Rol sugerido (opcional)</Label>
                                <Select
                                    value={form.role_id || "none"}
                                    onValueChange={(v) =>
                                        setForm((f) => ({ ...f, role_id: v === "none" ? "" : v }))
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="El admin asignará después" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Sin rol — asignar después</SelectItem>
                                        {roles.map((r) => (
                                            <SelectItem key={r.id} value={String(r.id)}>
                                                {r.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label>Cliente (opcional)</Label>
                                <Select
                                    value={form.client_id || "none"}
                                    onValueChange={(v) => setForm((f) => ({ ...f, client_id: v }))}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Staff interno" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">Staff interno (sin cliente)</SelectItem>
                                        {clients.map((c) => (
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="ghost" onClick={() => setDialogOpen(false)}>
                                    Cancelar
                                </Button>
                                <Button type="submit" disabled={submitting}>
                                    {submitting && <Loader2 className="h-4 w-4 mr-2 animate-spin" />}
                                    Enviar invitación
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AuthenticatedLayout>
    );
}
