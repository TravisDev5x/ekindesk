import { useMemo } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { cn } from "@/lib/utils";
import { User, Server, Calendar, Globe, FileEdit } from "lucide-react";

/**
 * Mapeo de llaves crudas del modelo a nombres amigables para la UI.
 */
const FIELD_LABELS = {
    ticket_state_id: "Estado",
    priority_id: "Prioridad",
    area_origin_id: "Área origen",
    area_current_id: "Área actual",
    assigned_user_id: "Responsable asignado",
    subject: "Asunto",
    description: "Descripción",
    due_at: "Fecha límite",
    resolved_at: "Fecha resolución",
    impact_level_id: "Impacto",
    urgency_level_id: "Urgencia",
};

/**
 * Traduce old_values y new_values a frases legibles.
 * @param {Record<string, unknown>|null} oldValues
 * @param {Record<string, unknown>|null} newValues
 * @returns {string[]} Lista de frases descriptivas
 */
export function translateChanges(oldValues, newValues) {
    const lines = [];
    const old = oldValues && typeof oldValues === "object" ? oldValues : {};
    const newV = newValues && typeof newValues === "object" ? newValues : {};
    const keys = new Set([...Object.keys(old), ...Object.keys(newV)]);

    keys.forEach((key) => {
        const label = FIELD_LABELS[key] || key;
        const oldVal = old[key];
        const newVal = newV[key];
        if (oldVal === undefined && newVal !== undefined) {
            lines.push(`${label}: se estableció en "${formatValue(newVal)}"`);
        } else if (oldVal !== undefined && newVal === undefined) {
            lines.push(`${label}: se eliminó (antes "${formatValue(oldVal)}")`);
        } else if (oldVal !== undefined && newVal !== undefined && String(oldVal) !== String(newVal)) {
            lines.push(`${label} cambió de "${formatValue(oldVal)}" a "${formatValue(newVal)}"`);
        }
    });

    return lines;
}

function formatValue(v) {
    if (v === null || v === undefined) return "—";
    if (typeof v === "string" && /^\d{4}-\d{2}-\d{2}/.test(v)) {
        try {
            return new Date(v).toLocaleString();
        } catch (_) {
            return String(v);
        }
    }
    return String(v);
}

const ACTION_LABELS = {
    created: "Creado",
    updated: "Actualizado",
    deleted: "Eliminado",
    restored: "Restaurado",
};

export default function AuditTimeline({ logs }) {
    const sortedLogs = useMemo(() => {
        const list = Array.isArray(logs) ? [...logs] : [];
        return list.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
    }, [logs]);

    if (!sortedLogs.length) {
        return (
            <Card className="flex-1">
                <CardContent className="py-10 text-center text-muted-foreground text-sm">
                    No hay registros de auditoría para este ticket.
                </CardContent>
            </Card>
        );
    }

    return (
        <Card className="flex-1 overflow-hidden flex flex-col">
            <CardHeader className="pb-2">
                <CardTitle className="text-base flex items-center gap-2">
                    <FileEdit className="h-4 w-4" />
                    Línea de tiempo de auditoría
                </CardTitle>
            </CardHeader>
            <CardContent className="flex-1 overflow-y-auto">
                <div className="relative space-y-0">
                    {/* Línea vertical */}
                    <div className="absolute left-[11px] top-2 bottom-2 w-0.5 bg-border rounded-full" />
                    {sortedLogs.map((log, index) => {
                        const actorName = log.user?.name ?? "Sistema";
                        const actionLabel = ACTION_LABELS[log.action] ?? log.action;
                        const changeLines = translateChanges(log.old_values || null, log.new_values || null);
                        return (
                            <div
                                key={log.id}
                                className={cn(
                                    "relative flex gap-4 pb-6",
                                    index === sortedLogs.length - 1 && "pb-0"
                                )}
                            >
                                <div className="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-primary bg-background">
                                    <div className="h-2 w-2 rounded-full bg-primary" />
                                </div>
                                <div className="min-w-0 flex-1 rounded-lg border border-border/60 bg-muted/20 p-3 text-sm">
                                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground mb-2">
                                        <span className="flex items-center gap-1">
                                            <Calendar className="h-3 w-3" />
                                            {new Date(log.created_at).toLocaleString()}
                                        </span>
                                        <span className="flex items-center gap-1">
                                            {log.user_id ? <User className="h-3 w-3" /> : <Server className="h-3 w-3" />}
                                            {actorName}
                                        </span>
                                        {log.ip_address && (
                                            <span className="flex items-center gap-1">
                                                <Globe className="h-3 w-3" />
                                                {log.ip_address}
                                            </span>
                                        )}
                                    </div>
                                    <p className="font-semibold text-foreground mb-1">{actionLabel}</p>
                                    {changeLines.length > 0 && (
                                        <ul className="list-disc list-inside space-y-0.5 text-muted-foreground">
                                            {changeLines.map((line, i) => (
                                                <li key={i}>{line}</li>
                                            ))}
                                        </ul>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </CardContent>
        </Card>
    );
}
