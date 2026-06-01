import { memo } from "react";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";
import {
    incidentSeverityClassByLevel,
    incidentStatusClassByStatus,
    planTypeClass,
    priorityClassByLevel,
    priorityClassByName,
    ticketStateClassByCode,
    ticketStateClassByName,
} from "@/lib/badgeStyles";
import { AlertCircle, AlertTriangle, CheckCircle2, Clock, ShieldAlert, Ticket } from "lucide-react";

const priorityBadgeClass =
    "uppercase text-[10px] font-bold tracking-tight px-2 py-0.5";

const stateBadgeClass = "font-medium py-0.5 pl-2 pr-2.5";

export const TicketPriorityBadge = memo(function TicketPriorityBadge({ priority, className }) {
    return (
        <Badge
            variant="outline"
            className={cn(priorityBadgeClass, priorityClassByLevel(priority?.level), className)}
        >
            {priority?.name}
        </Badge>
    );
});

export const TicketStateBadge = memo(function TicketStateBadge({ state, className }) {
    const code = (state?.code || "").toLowerCase();
    let icon = <Clock className="w-3 h-3 mr-1.5" />;
    let styles = ticketStateClassByCode(code);

    if (["abierto", "en_progreso", "asignado"].includes(code)) {
        icon = <Ticket className="w-3 h-3 mr-1.5" />;
    } else if (["resuelto", "cerrado"].includes(code)) {
        icon = <CheckCircle2 className="w-3 h-3 mr-1.5" />;
    } else if (code.includes("cancel") || code.includes("rechaz")) {
        icon = <AlertCircle className="w-3 h-3 mr-1.5" />;
    }

    return (
        <Badge variant="outline" className={cn(stateBadgeClass, styles, className)}>
            {icon}
            {state?.name}
        </Badge>
    );
});

export const TicketPriorityBadgeByName = memo(function TicketPriorityBadgeByName({
    name,
    className,
}) {
    return (
        <Badge variant="outline" className={cn(priorityBadgeClass, priorityClassByName(name), className)}>
            {name ?? "—"}
        </Badge>
    );
});

export const TicketStateBadgeByName = memo(function TicketStateBadgeByName({ name, className }) {
    return (
        <Badge variant="outline" className={cn(stateBadgeClass, ticketStateClassByName(name), className)}>
            {name ?? "—"}
        </Badge>
    );
});

export const IncidentSeverityBadge = memo(function IncidentSeverityBadge({ severity, className }) {
    return (
        <Badge
            variant="outline"
            className={cn(
                priorityBadgeClass,
                incidentSeverityClassByLevel(severity?.level),
                className
            )}
        >
            {severity?.name}
        </Badge>
    );
});

export const IncidentStatusBadge = memo(function IncidentStatusBadge({ status, className }) {
    const code = (status?.code || "").toLowerCase();
    let icon = <Clock className="w-3 h-3 mr-1.5" />;
    const styles = incidentStatusClassByStatus(status);

    if (status?.is_final) {
        icon = <CheckCircle2 className="w-3 h-3 mr-1.5" />;
    } else if (code.includes("cancel") || code.includes("rechaz")) {
        icon = <AlertTriangle className="w-3 h-3 mr-1.5" />;
    } else {
        icon = <ShieldAlert className="w-3 h-3 mr-1.5" />;
    }

    return (
        <Badge variant="outline" className={cn(stateBadgeClass, styles, className)}>
            {icon}
            {status?.name}
        </Badge>
    );
});

export function PlanTypeBadge({ type, className }) {
    if (!type) return null;
    return (
        <Badge variant="outline" className={cn("text-xs", planTypeClass(type), className)}>
            {type === "msp" && "MSP"}
            {type === "inhouse" && "In-House"}
            {type === "both" && "Flexible"}
        </Badge>
    );
}
