import { useEffect, useMemo, useState } from "react";
import { useParams, Link } from "react-router-dom";
import axios from "@/lib/axios";
import { useAuth } from "@/context/AuthContext";
import { loadCatalogs, clearCatalogCache } from "@/lib/catalogCache";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";
import { cn } from "@/lib/utils";
import { notify } from "@/lib/notify";
import { Skeleton } from "@/components/ui/skeleton";
import { Loader2, MessageSquare, Paperclip, ArrowLeft, ChevronDown, ChevronUp, UserCheck, AlertTriangle, XCircle, BellRing } from "lucide-react";

const RESOLVE_BASE = "/resolbeb";

/** Badge semántico por nombre de estado (solo lectura). */
function getStateBadgeClass(stateName) {
    if (!stateName) return "bg-muted text-muted-foreground";
    const n = String(stateName).toLowerCase();
    if (n.includes("resuelto") || n.includes("cerrado") || n.includes("cerrada")) return "bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border-emerald-500/30";
    if (n.includes("proceso") || n.includes("asignado") || n.includes("abierto")) return "bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-500/30";
    if (n.includes("cancelado")) return "bg-muted text-muted-foreground";
    return "bg-muted text-muted-foreground";
}

/** Badge semántico por prioridad (solo lectura). */
function getPriorityBadgeClass(priorityName) {
    if (!priorityName) return "bg-muted text-muted-foreground";
    const n = String(priorityName).toLowerCase();
    if (n.includes("crític") || n.includes("alta")) return "bg-red-500/15 text-red-700 dark:text-red-400 border-red-500/30";
    if (n.includes("media")) return "bg-amber-500/15 text-amber-700 dark:text-amber-400 border-amber-500/30";
    return "bg-muted text-muted-foreground";
}

/** Pasos de la timeline: Creado -> Asignado -> En Proceso -> Resuelto. */
const TIMELINE_STEPS = [
    { key: "creado", label: "Creado" },
    { key: "asignado", label: "Asignado" },
    { key: "en_proceso", label: "En proceso" },
    { key: "resuelto", label: "Resuelto" },
];

function normalizeStateKey(name) {
    if (!name) return "";
    return String(name).toLowerCase().replace(/\s+/g, "_").replace(/ó/g, "o");
}

export default function Resolvev1Detalle() {
    const { id } = useParams();
    const { user, can } = useAuth();
    const backToListLink = (can("tickets.manage_all") || can("tickets.view_area")) ? `${RESOLVE_BASE}/tickets` : RESOLVE_BASE;
    const [ticket, setTicket] = useState(null);
    const [catalogs, setCatalogs] = useState({ areas: [], priorities: [], ticket_states: [], area_users: [], positions: [] });
    const [note, setNote] = useState("");
    const [updating, setUpdating] = useState(false);
    const [assigneeId, setAssigneeId] = useState("none");
    const [dueAtLocal, setDueAtLocal] = useState("");
    const [isInternalNote, setIsInternalNote] = useState(true);
    const [attachmentFiles, setAttachmentFiles] = useState([]);
    const [uploadingAttachments, setUploadingAttachments] = useState(false);
    const [descExpanded, setDescExpanded] = useState(false);
    const [alertMessage, setAlertMessage] = useState("");
    const [sendingAlert, setSendingAlert] = useState(false);
    const [cancelling, setCancelling] = useState(false);

    const load = async () => {
        try {
            const [catalogData, ticketRes] = await Promise.all([
                loadCatalogs(false, ["core", "tickets"]),
                axios.get(`/api/tickets/${id}`),
            ]);
            setCatalogs({
                areas: catalogData.areas || [],
                priorities: catalogData.priorities || [],
                impact_levels: catalogData.impact_levels || [],
                urgency_levels: catalogData.urgency_levels || [],
                priority_matrix: catalogData.priority_matrix || [],
                ticket_states: catalogData.ticket_states || [],
                area_users: catalogData.area_users || [],
                positions: catalogData.positions || [],
            });
            setTicket(ticketRes.data);
            const t = ticketRes.data;
            setDueAtLocal(t?.due_at ? new Date(t.due_at).toISOString().slice(0, 16) : "");
        } catch (err) {
            notify.error("No se pudo cargar el ticket");
        }
    };

    useEffect(() => { load(); }, [id]);

    const update = async (payload) => {
        setUpdating(true);
        try {
            const { data } = await axios.put(`/api/tickets/${id}`, { ...payload, note, is_internal: isInternalNote });
            setTicket(data);
            setNote("");
            setAssigneeId("none");
            setDueAtLocal(data?.due_at ? new Date(data.due_at).toISOString().slice(0, 16) : "");
            clearCatalogCache();
            notify.success("Ticket actualizado");
        } catch (err) {
            notify.error(err?.response?.data?.message || "No se pudo actualizar");
        } finally { setUpdating(false); }
    };

    const takeTicket = async () => {
        setUpdating(true);
        try {
            const { data } = await axios.post(`/api/tickets/${id}/take`);
            setTicket(data);
            notify.success("Ticket tomado");
        } catch (err) {
            notify.error(err?.response?.data?.message || "No se pudo tomar");
        } finally { setUpdating(false); }
    };

    const assignTicket = async () => {
        if (assigneeId === "none") return;
        setUpdating(true);
        try {
            const { data } = await axios.post(`/api/tickets/${id}/assign`, { assigned_user_id: Number(assigneeId) });
            setTicket(data);
            setAssigneeId("none");
            notify.success("Responsable asignado");
        } catch (err) {
            notify.error(err?.response?.data?.message || "No se pudo reasignar");
        } finally { setUpdating(false); }
    };

    const unassignTicket = async () => {
        setUpdating(true);
        try {
            const { data } = await axios.post(`/api/tickets/${id}/unassign`);
            setTicket(data);
            notify.success("Ticket liberado");
        } catch (err) {
            notify.error(err?.response?.data?.message || "No se pudo liberar");
        } finally { setUpdating(false); }
    };

    const sendAlert = async () => {
        setSendingAlert(true);
        try {
            const { data } = await axios.post(`/api/tickets/${id}/alert`, { message: alertMessage.trim() || undefined });
            if (data.ticket) setTicket(data.ticket);
            setAlertMessage("");
            notify.success("Alerta enviada. Se ha notificado al responsable y a supervisores.");
        } catch (err) {
            notify.error(err?.response?.data?.message || "No se pudo enviar la alerta");
        } finally { setSendingAlert(false); }
    };

    const cancelTicket = async () => {
        if (!window.confirm("¿Estás seguro de que deseas cancelar este ticket? Esta acción no se puede deshacer.")) return;
        setCancelling(true);
        try {
            const { data } = await axios.post(`/api/tickets/${id}/cancel`);
            setTicket(data);
            notify.success("Ticket cancelado");
        } catch (err) {
            notify.error(err?.response?.data?.message || "No se pudo cancelar el ticket");
        } finally { setCancelling(false); }
    };

    const uploadAttachments = async () => {
        if (!attachmentFiles.length) return;
        setUploadingAttachments(true);
        try {
            const payload = new FormData();
            attachmentFiles.forEach((f) => payload.append("attachments[]", f));
            const { data } = await axios.post(`/api/tickets/${id}/attachments`, payload, {
                headers: { "Content-Type": "multipart/form-data" },
            });
            setTicket((prev) => ({ ...prev, attachments: [...(prev?.attachments || []), ...(data || [])] }));
            setAttachmentFiles([]);
            notify.success("Adjuntos cargados");
        } catch (err) {
            notify.error(err?.response?.data?.message || "No se pudieron cargar los adjuntos");
        } finally {
            setUploadingAttachments(false);
        }
    };

    const removeAttachment = async (att) => {
        try {
            await axios.delete(`/api/tickets/${id}/attachments/${att.id}`);
            setTicket((prev) => ({ ...prev, attachments: (prev?.attachments || []).filter((a) => a.id !== att.id) }));
            notify.success("Adjunto eliminado");
        } catch (err) {
            notify.error("No se pudo eliminar el adjunto");
        }
    };

    const attendedBy = useMemo(() => {
        if (!ticket) return [];
        const assigned = ticket.assigned_user || ticket.assignedUser;
        const raw = ticket.histories ?? ticket.histories_list ?? [];
        const hist = Array.isArray(raw) ? raw : (Array.isArray(raw?.data) ? raw.data : (raw && typeof raw === 'object' && !Array.isArray(raw) ? Object.values(raw) : []));
        const ids = new Set();
        const names = [];
        if (assigned?.id) {
            ids.add(assigned.id);
            names.push({ id: assigned.id, name: assigned.name });
        }
        hist.forEach((h) => {
            if (h.actor_id && h.actor_id !== ticket.requester_id && !ids.has(h.actor_id)) {
                ids.add(h.actor_id);
                names.push({ id: h.actor_id, name: h.actor?.name || "—" });
            }
        });
        return names;
    }, [ticket]);

    const completedStepKeys = useMemo(() => {
        if (!ticket) return new Set(["creado"]);
        const hasAssignee = Boolean(ticket.assigned_user_id);
        const keys = new Set(["creado"]);
        if (ticket.created_at) keys.add("creado");
        if (hasAssignee) keys.add("asignado");
        const stateName = (ticket.state?.name || "").toLowerCase();
        if (stateName.includes("proceso") || stateName.includes("abierto") || stateName.includes("asignado")) keys.add("en_proceso");
        if (stateName.includes("resuelto") || stateName.includes("cerrado") || stateName.includes("cerrada")) keys.add("resuelto");
        return keys;
    }, [ticket]);

    const timelineStepsWithStatus = useMemo(() => TIMELINE_STEPS.map((step) => ({
        ...step,
        completed: completedStepKeys.has(step.key),
    })), [completedStepKeys]);

    if (!ticket) {
        return (
            <div className="p-4 md:p-6 space-y-6 max-w-4xl mx-auto">
                <div className="flex items-center gap-3">
                    <Skeleton className="h-9 w-9 rounded-md" />
                    <div className="space-y-2">
                        <Skeleton className="h-6 w-48" />
                        <Skeleton className="h-4 w-32" />
                    </div>
                </div>
                <Card>
                    <CardHeader><Skeleton className="h-5 w-3/4" /></CardHeader>
                    <CardContent className="space-y-3">
                        {[1, 2, 3, 4, 5].map((i) => <Skeleton key={i} className="h-4 w-full" />)}
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-6"><Skeleton className="h-24 w-full" /></CardContent>
                </Card>
            </div>
        );
    }

    const abilities = ticket.abilities || {};
    const canChangeArea = Boolean(abilities.change_area);
    const canChangeStatus = Boolean(abilities.change_status);
    const canComment = Boolean(abilities.comment);
    const canAssign = Boolean(abilities.assign);
    const canRelease = Boolean(abilities.release);
    const canAlert = Boolean(abilities.alert);
    const canCancel = Boolean(abilities.cancel);

    const canEdit = canChangeArea || canChangeStatus || canComment;
    const hasAssignee = Boolean(ticket.assigned_user_id);
    const assignedUser = ticket.assigned_user || ticket.assignedUser;

    const rawHist = ticket.histories ?? [];
    const histories = Array.isArray(rawHist) ? rawHist : (Array.isArray(rawHist?.data) ? rawHist.data : (rawHist && typeof rawHist === 'object' && !Array.isArray(rawHist) ? Object.values(rawHist) : []));
    const assignmentEvents = histories.filter(h => ["assigned", "reassigned", "unassigned"].includes(h.action));
    const commentEntries = histories.filter(h => h.action === "comment" && !h.is_internal);
    const internalNoteEntries = histories.filter(h => h.action === "comment" && h.is_internal);
    const stateChangeEntries = histories.filter(h => h.action && h.action !== "comment" && ["escalated", "state_change", "assigned", "reassigned", "unassigned"].includes(h.action));
    const alertEntries = histories.filter(h => h.action === "requester_alert");
    const stateChangeWithDiff = stateChangeEntries.map((h, idx) => {
        const next = stateChangeEntries[idx + 1];
        if (!next) return { ...h, diff: null };
        const current = new Date(h.created_at);
        const nextDate = new Date(next.created_at);
        const diffHours = Math.round((current - nextDate) / 3600000 * 10) / 10;
        return { ...h, diff: diffHours };
    });
    const areaUsers = catalogs.area_users || [];

    const descLong = (ticket.description || "").length > 280;
    const isRequester = user && Number(user.id) === Number(ticket.requester_id);

    return (
        <div className="space-y-6 max-w-5xl mx-auto pb-8">
            <div className="flex items-center gap-3">
                <Button variant="ghost" size="sm" asChild className="text-muted-foreground hover:text-foreground -ml-2">
                    <Link to={backToListLink}><ArrowLeft className="h-4 w-4 mr-1" /> Volver al listado</Link>
                </Button>
            </div>

            {isRequester && attendedBy.length > 0 && (
                <Card className="border-primary/20 bg-primary/5">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm flex items-center gap-2">
                            <UserCheck className="h-4 w-4" /> Quién ha atendido este ticket
                        </CardTitle>
                        <CardDescription className="text-xs">Personas que han intervenido en la atención de tu solicitud.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <ul className="flex flex-wrap gap-2">
                            {attendedBy.map((p) => (
                                <li key={p.id}>
                                    <Badge variant="secondary" className="font-normal">{p.name}</Badge>
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>
            )}

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {/* Columna principal (2/3) */}
                <div className="lg:col-span-2 space-y-6">
                    {/* Encabezado: asunto, badges, descripción */}
                    <Card className={ticket.is_overdue ? "border-l-4 border-l-destructive" : ""}>
                        <CardHeader className="pb-2">
                            <div className="flex flex-wrap items-center gap-2 mb-2">
                                <Badge className={getStateBadgeClass(ticket.state?.name)}>{ticket.state?.name ?? "—"}</Badge>
                                <Badge className={getPriorityBadgeClass(ticket.priority?.name)}>{ticket.priority?.name ?? "—"}</Badge>
                                <Badge variant="outline" className="text-xs">{ticket.area_current?.name ?? "—"}</Badge>
                                {ticket.is_overdue && <Badge variant="destructive">SLA vencido</Badge>}
                            </div>
                            <CardTitle className="text-lg md:text-xl">Ticket #{ticket.id} — {ticket.subject}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <p className="text-slate-600 dark:text-slate-300 whitespace-pre-wrap text-sm">
                                {descLong && !descExpanded ? (
                                    <>
                                        {(ticket.description || "").slice(0, 280)}…
                                        <button type="button" onClick={() => setDescExpanded(true)} className="ml-2 text-primary hover:underline inline-flex items-center gap-0.5 text-xs font-medium">Ver más <ChevronDown className="h-3 w-3" /></button>
                                    </>
                                ) : descLong && descExpanded ? (
                                    <>
                                        {ticket.description}
                                        <button type="button" onClick={() => setDescExpanded(false)} className="ml-2 text-primary hover:underline inline-flex items-center gap-0.5 text-xs font-medium">Ver menos <ChevronUp className="h-3 w-3" /></button>
                                    </>
                                ) : (
                                    ticket.description || "—"
                                )}
                            </p>
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1 text-xs text-muted-foreground border-t pt-3">
                                <div><strong className="text-foreground/80">Área origen:</strong> {ticket.area_origin?.name ?? "—"}</div>
                                <div><strong className="text-foreground/80">Tipo:</strong> {ticket.ticket_type?.name ?? "—"}</div>
                                <div><strong className="text-foreground/80">Solicitante:</strong> {ticket.requester?.name ?? "—"}</div>
                                <div><strong className="text-foreground/80">Responsable:</strong> {assignedUser ? assignedUser.name : "Sin asignar"}</div>
                                <div><strong className="text-foreground/80">Fecha límite:</strong> {ticket.due_at ? new Date(ticket.due_at).toLocaleString() : "—"}</div>
                                {ticket.sla_status_text && <div><strong className="text-foreground/80">SLA:</strong> <span className={ticket.is_overdue ? "text-destructive font-medium" : ""}>{ticket.sla_status_text}</span></div>}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Historial / Chat: burbujas y nuevo comentario */}
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base flex items-center gap-2">
                                <MessageSquare className="h-4 w-4" /> Historial y comentarios
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {commentEntries.length ? (
                                <ul className="space-y-3">
                                    {commentEntries.map((h) => {
                                        const isUser = Number(h.actor_id) === Number(ticket.requester_id);
                                        return (
                                            <li key={h.id} className={isUser ? "flex justify-end" : "flex justify-start"}>
                                                <div className={cn(
                                                    "max-w-[85%] rounded-2xl px-4 py-2.5 text-sm",
                                                    isUser
                                                        ? "bg-primary text-primary-foreground rounded-br-md"
                                                        : "bg-muted rounded-bl-md"
                                                )}>
                                                    <div className="text-xs opacity-90 mb-0.5">{h.actor?.name} · {new Date(h.created_at).toLocaleString()}</div>
                                                    <div className="whitespace-pre-wrap">{h.note || "—"}</div>
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ul>
                            ) : (
                                <p className="text-sm text-muted-foreground">Sin comentarios aún.</p>
                            )}
                            {canComment && (
                                <div className="border-t pt-4 space-y-2">
                                    <Textarea
                                        placeholder="Escribe un comentario..."
                                        value={note}
                                        onChange={(e) => setNote(e.target.value)}
                                        disabled={updating}
                                        className="min-h-[80px] resize-y"
                                    />
                                    <Button onClick={() => update({})} disabled={updating || !note.trim()}>
                                        {updating ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : null}
                                        Enviar comentario
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Adjuntos: solo lectura */}
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-base">Adjuntos</CardTitle>
                            <Badge variant="secondary" className="text-[10px]">{(ticket.attachments || []).length}</Badge>
                        </CardHeader>
                        <CardContent>
                            {(ticket.attachments || []).length === 0 ? (
                                <p className="text-sm text-muted-foreground">Sin adjuntos.</p>
                            ) : (
                                <ul className="space-y-1.5">
                                    {(ticket.attachments || []).map((a) => (
                                        <li key={a.id}>
                                            <a
                                                href={`/api/tickets/${id}/attachments/${a.id}/download`}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="text-primary hover:underline text-sm inline-flex items-center gap-1"
                                            >
                                                <Paperclip className="h-3.5 w-3.5" /> {a.original_name}
                                            </a>
                                            <span className="text-xs text-muted-foreground ml-2">{Math.round((a.size || 0) / 1024)} KB</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Columna secundaria (1/3) */}
                <div className="lg:col-span-1 space-y-6">
                    {/* Botón de alerta (nudge) */}
                    {canAlert && (
                        <Card>
                            <CardContent className="pt-6 space-y-3">
                                <Textarea
                                    placeholder="Mensaje opcional (ej: Llevo días sin respuesta...)"
                                    value={alertMessage}
                                    onChange={(e) => setAlertMessage(e.target.value)}
                                    disabled={sendingAlert}
                                    className="min-h-[72px] resize-y text-sm"
                                    maxLength={1000}
                                />
                                <Button
                                    variant="destructive"
                                    className="w-full"
                                    onClick={sendAlert}
                                    disabled={sendingAlert}
                                >
                                    {sendingAlert ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <BellRing className="h-4 w-4 mr-2" />}
                                    Enviar Alerta a Soporte
                                </Button>
                                {canCancel && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        className="w-full mt-2 text-destructive hover:text-destructive"
                                        onClick={cancelTicket}
                                        disabled={cancelling}
                                    >
                                        {cancelling ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <XCircle className="h-4 w-4 mr-2" />}
                                        Cancelar ticket
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {/* Timeline: Progreso */}
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-base">Progreso</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="relative">
                                <div className="absolute left-2 top-2 bottom-2 w-0.5 border-l-2 border-border" aria-hidden="true" />
                                <ul className="space-y-0">
                                    {timelineStepsWithStatus.map((step, idx) => (
                                        <li key={step.key} className="relative flex gap-3 pb-6 last:pb-0">
                                            <div
                                                className={cn(
                                                    "relative z-10 h-4 w-4 rounded-full border-2 flex-shrink-0 mt-0.5",
                                                    step.completed
                                                        ? "bg-primary border-primary"
                                                        : "bg-background border-muted-foreground/30"
                                                )}
                                            />
                                            <div className="flex-1 min-w-0 pt-0">
                                                <p className={cn("text-sm font-medium", step.completed ? "text-foreground" : "text-muted-foreground")}>
                                                    {step.label}
                                                </p>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    );
}
