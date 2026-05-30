import { useEffect, useMemo, useState } from "react";
import { Head, router } from "@inertiajs/react";
import axios from "@/lib/axios";
import { useAuth } from "@/context/AuthContext";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Separator } from "@/components/ui/separator";
import { notify } from "@/lib/notify";
import { ArrowLeft, Plus, Loader2, CheckCircle2, MapPin, User } from "lucide-react";

const RESOLVE_BASE = "/resolbeb";

function FieldError({ errors, field }) {
    const message = errors?.[field];
    if (!message) return null;
    const text = Array.isArray(message) ? message[0] : message;
    return <p className="text-sm text-destructive mt-1">{text}</p>;
}

export default function ResolbebCreate({ catalogs: catalogsProp }) {
    const { user, can } = useAuth();
    const catalogs = catalogsProp ?? {};
    const [saving, setSaving] = useState(false);
    const [errors, setErrors] = useState({});
    const [form, setForm] = useState({
        subject: "",
        description: "",
        sede_id: "",
        area_origin_id: "",
        area_current_id: "",
        ticket_type_id: "",
        impact_level_id: "",
        urgency_level_id: "",
        ticket_state_id: "",
    });

    useEffect(() => {
        if (!catalogs.ticket_states?.length) return;
        const openState =
            catalogs.ticket_states.find((s) => (s.code || "").toLowerCase() === "abierto") ||
            catalogs.ticket_states[0];
        setForm((prev) => ({
            ...prev,
            sede_id: prev.sede_id || String(user?.sede_id || user?.sede?.id || ""),
            area_origin_id: prev.area_origin_id || String(user?.area_id || ""),
            ticket_type_id: prev.ticket_type_id || String(catalogs.ticket_types?.[0]?.id || ""),
            impact_level_id: prev.impact_level_id || String(catalogs.impact_levels?.[0]?.id || ""),
            urgency_level_id: prev.urgency_level_id || String(catalogs.urgency_levels?.[0]?.id || ""),
            ticket_state_id: String(openState?.id || ""),
        }));
    }, [
        catalogs.ticket_states,
        catalogs.ticket_types,
        catalogs.impact_levels,
        catalogs.urgency_levels,
        user?.sede_id,
        user?.sede?.id,
        user?.area_id,
    ]);

    const isSolicitanteOnly = !can("tickets.manage_all") && !can("tickets.view_area");
    const backTo = isSolicitanteOnly ? RESOLVE_BASE : `${RESOLVE_BASE}/tickets`;

    const calculatedPriorityName = useMemo(() => {
        const matrix = catalogs.priority_matrix || [];
        const row = matrix.find(
            (m) =>
                Number(m.impact_level_id) === Number(form.impact_level_id) &&
                Number(m.urgency_level_id) === Number(form.urgency_level_id)
        );
        const pid = row?.priority_id;
        const p = (catalogs.priorities || []).find((x) => Number(x.id) === Number(pid));
        if (p) return p.name;
        if (form.impact_level_id && form.urgency_level_id) return "—";
        return "Selecciona Impacto y Urgencia";
    }, [catalogs.priority_matrix, catalogs.priorities, form.impact_level_id, form.urgency_level_id]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setErrors({});

        if (!form.subject?.trim()) {
            notify.error("El asunto es obligatorio");
            return;
        }
        if (!user?.sede_id && !user?.sede?.id) {
            notify.error("Tu usuario no tiene sede asignada. Contacta al administrador.");
            return;
        }
        if (
            !form.area_origin_id ||
            !form.area_current_id ||
            !form.ticket_type_id ||
            !form.impact_level_id ||
            !form.urgency_level_id ||
            !form.ticket_state_id
        ) {
            notify.error("Completa todos los campos obligatorios (incl. Impacto y Urgencia)");
            return;
        }

        setSaving(true);
        try {
            const now = new Date().toISOString();
            const payload = {
                subject: form.subject.trim(),
                description: form.description?.trim() || null,
                area_origin_id: Number(form.area_origin_id),
                area_current_id: Number(form.area_current_id),
                impact_level_id: Number(form.impact_level_id),
                urgency_level_id: Number(form.urgency_level_id),
                ticket_type_id: Number(form.ticket_type_id),
                ticket_state_id: Number(form.ticket_state_id),
                created_at: now,
            };
            const { data } = await axios.post("/api/tickets", payload);
            notify.success("Ticket creado correctamente");
            const ticketId = data?.id ?? data?.ticket?.id;
            if (ticketId) {
                router.visit(`${RESOLVE_BASE}/tickets/${ticketId}`);
            } else if (isSolicitanteOnly) {
                router.visit(RESOLVE_BASE);
            } else {
                router.visit(`${RESOLVE_BASE}/tickets`);
            }
        } catch (err) {
            if (err?.response?.status === 422) {
                setErrors(err.response.data.errors ?? {});
                notify.error(err?.response?.data?.message || "Revisa los campos del formulario");
            } else {
                notify.error(err?.response?.data?.message || "Error al crear el ticket");
            }
        } finally {
            setSaving(false);
        }
    };

    return (
        <AuthenticatedLayout title="Nuevo ticket">
            <Head title="Nuevo ticket" />

            <div className="w-full max-w-2xl mx-auto p-4 md:p-6 space-y-6">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="sm" asChild className="-ml-2">
                        <a href={backTo}>
                            <ArrowLeft className="h-4 w-4 mr-1" /> Volver
                        </a>
                    </Button>
                </div>

                <Card>
                    <CardHeader className="bg-primary/10 border-b">
                        <CardTitle className="text-xl flex items-center gap-2">
                            <Plus className="h-5 w-5" /> Nuevo ticket
                        </CardTitle>
                        <CardDescription>
                            Completa los datos para registrar tu solicitud (Resolvev1).
                        </CardDescription>
                    </CardHeader>
                    <form onSubmit={handleSubmit}>
                        <CardContent className="p-6 space-y-6">
                            <div className="space-y-2">
                                <Label>
                                    Asunto <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    required
                                    placeholder="Ej: Fallo en impresora de recepción"
                                    value={form.subject}
                                    onChange={(e) => setForm({ ...form, subject: e.target.value })}
                                />
                                <FieldError errors={errors} field="subject" />
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Tipo de ticket</Label>
                                    <Select
                                        value={form.ticket_type_id}
                                        onValueChange={(v) => setForm({ ...form, ticket_type_id: v })}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {(catalogs.ticket_types || []).map((t) => (
                                                <SelectItem key={t.id} value={String(t.id)}>
                                                    {t.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <FieldError errors={errors} field="ticket_type_id" />
                                </div>
                                <div className="space-y-2">
                                    <Label>
                                        Impacto <span className="text-destructive">*</span>
                                    </Label>
                                    <Select
                                        value={form.impact_level_id}
                                        onValueChange={(v) => setForm({ ...form, impact_level_id: v })}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {(catalogs.impact_levels || []).map((i) => (
                                                <SelectItem key={i.id} value={String(i.id)}>
                                                    {i.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <FieldError errors={errors} field="impact_level_id" />
                                </div>
                                <div className="space-y-2">
                                    <Label>
                                        Urgencia <span className="text-destructive">*</span>
                                    </Label>
                                    <Select
                                        value={form.urgency_level_id}
                                        onValueChange={(v) => setForm({ ...form, urgency_level_id: v })}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {(catalogs.urgency_levels || []).map((u) => (
                                                <SelectItem key={u.id} value={String(u.id)}>
                                                    {u.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <FieldError errors={errors} field="urgency_level_id" />
                                </div>
                                <div className="space-y-2">
                                    <Label>Prioridad (calculada)</Label>
                                    <Input readOnly className="bg-muted" value={calculatedPriorityName} />
                                </div>
                            </div>

                            <Separator />

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 bg-muted/20 p-4 rounded-lg border border-border/50">
                                <div className="space-y-2">
                                    <Label>Cliente</Label>
                                    <div className="flex h-10 items-center rounded-md border border-border/60 bg-background px-3 text-sm">
                                        {user?.client_name || "Sin cliente"}
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label className="flex items-center gap-1">
                                        <MapPin className="w-3 h-3" /> Sede
                                    </Label>
                                    <div className="flex h-10 items-center rounded-md border border-border/60 bg-background px-3 text-sm">
                                        {user?.sede?.name ||
                                            (catalogs.sedes || []).find(
                                                (s) => String(s.id) === String(user?.sede_id)
                                            )?.name ||
                                            "—"}
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        Asignadas automáticamente a tu perfil.
                                    </p>
                                </div>
                                <div className="space-y-2">
                                    <Label className="flex items-center gap-1">
                                        <User className="w-3 h-3" /> Área responsable{" "}
                                        <span className="text-destructive">*</span>
                                    </Label>
                                    <Select
                                        value={form.area_current_id}
                                        onValueChange={(v) => setForm({ ...form, area_current_id: v })}
                                    >
                                        <SelectTrigger className="bg-background">
                                            <SelectValue placeholder="Área que atenderá" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {(catalogs.areas || []).map((a) => (
                                                <SelectItem key={a.id} value={String(a.id)}>
                                                    {a.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <FieldError errors={errors} field="area_current_id" />
                                </div>
                                <div className="space-y-2 md:col-span-2">
                                    <Label>Área de origen (solicitante)</Label>
                                    <Select
                                        value={form.area_origin_id}
                                        onValueChange={(v) => setForm({ ...form, area_origin_id: v })}
                                    >
                                        <SelectTrigger className="bg-background">
                                            <SelectValue placeholder="Tu área" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {(catalogs.areas || []).map((a) => (
                                                <SelectItem key={a.id} value={String(a.id)}>
                                                    {a.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <FieldError errors={errors} field="area_origin_id" />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label>Descripción del problema</Label>
                                <Textarea
                                    placeholder="Describe qué ocurrió, cuándo y si hay mensajes de error..."
                                    className="min-h-[120px] resize-y"
                                    value={form.description}
                                    onChange={(e) => setForm({ ...form, description: e.target.value })}
                                />
                                <FieldError errors={errors} field="description" />
                            </div>
                        </CardContent>
                        <CardFooter className="border-t p-4 flex justify-end gap-2">
                            <Button type="button" variant="ghost" asChild>
                                <a href={backTo}>Cancelar</a>
                            </Button>
                            <Button type="submit" disabled={saving}>
                                {saving ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : (
                                    <CheckCircle2 className="mr-2 h-4 w-4" />
                                )}
                                Crear ticket
                            </Button>
                        </CardFooter>
                    </form>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
