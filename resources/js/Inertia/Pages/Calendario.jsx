import { useCallback, useEffect, useMemo, useState } from "react";
import { usePage } from "@inertiajs/react";
import { de, enUS, es as dateFnsEs, fr, ja, zhCN } from "date-fns/locale";
import axios from "@/lib/axios";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import InertiaPageShell from "@/Inertia/components/InertiaPageShell";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import { Skeleton } from "@/components/ui/skeleton";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { cn } from "@/lib/utils";
import { AlertCircle, CalendarDays, ShieldOff, Ticket } from "lucide-react";

const CALENDAR_PER_PAGE = 500;

function toDateKey(d) {
    if (!d) return "";
    const date = d instanceof Date ? d : new Date(d);
    if (isNaN(date.getTime())) return "";
    return (
        date.getFullYear() +
        "-" +
        String(date.getMonth() + 1).padStart(2, "0") +
        "-" +
        String(date.getDate()).padStart(2, "0")
    );
}

function getCalendarDate(ticket) {
    return ticket?.created_at ?? null;
}

function userCan(user, permission) {
    return (user?.permissions ?? []).includes(permission);
}

const LOCALE_MAP = {
    es: dateFnsEs,
    en: enUS,
    ja,
    de,
    zh: zhCN,
    fr,
};

export default function Calendario() {
    const { auth } = usePage().props;
    const user = auth?.user;
    const calendarLocale = LOCALE_MAP[auth?.user?.locale ?? "es"] ?? dateFnsEs;

    const [tickets, setTickets] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [selectedDate, setSelectedDate] = useState(undefined);
    const [stateFilter, setStateFilter] = useState("all");
    const [scope, setScope] = useState("assigned");

    const isOperativo = useMemo(
        () => userCan(user, "tickets.view_area") || userCan(user, "tickets.manage_all"),
        [user]
    );
    const isSolicitante = useMemo(
        () => !isOperativo && userCan(user, "tickets.view_own"),
        [isOperativo, user]
    );

    const buildParams = useCallback(() => {
        const params = { per_page: CALENDAR_PER_PAGE };
        if (isSolicitante) return params;
        if (isOperativo) {
            if (scope === "assigned") params.assigned_to = "me";
            else if (scope === "created") params.created_by = "me";
        }
        return params;
    }, [isSolicitante, isOperativo, scope]);

    const loadTickets = useCallback(() => {
        if (
            !userCan(user, "tickets.view_own") &&
            !userCan(user, "tickets.view_area") &&
            !userCan(user, "tickets.manage_all")
        ) {
            setTickets([]);
            setLoading(false);
            setError("no_permission");
            return;
        }
        setLoading(true);
        setError(null);
        axios
            .get("/api/tickets", { params: buildParams() })
            .then((res) => setTickets(res.data?.data ?? []))
            .catch((err) => {
                if (err?.response?.status === 403) {
                    setError("no_permission");
                    setTickets([]);
                } else {
                    setError("load_failed");
                    setTickets([]);
                }
            })
            .finally(() => setLoading(false));
    }, [user, buildParams]);

    useEffect(() => {
        loadTickets();
    }, [loadTickets]);

    const ticketDatesSet = useMemo(() => {
        const set = new Set();
        (tickets || []).forEach((t) => {
            const at = getCalendarDate(t);
            if (at) set.add(toDateKey(at));
        });
        return set;
    }, [tickets]);

    const modifiers = useMemo(
        () => ({
            hasTicket: (date) => ticketDatesSet.has(toDateKey(date)),
        }),
        [ticketDatesSet]
    );

    const modifiersClassNames = useMemo(
        () => ({
            hasTicket: "bg-primary/20 text-primary font-semibold ring-1 ring-primary/40",
        }),
        []
    );

    const ticketsToShow = useMemo(() => {
        if (!selectedDate) return tickets;
        const key = toDateKey(selectedDate);
        return tickets.filter((t) => {
            const at = getCalendarDate(t);
            return at && toDateKey(at) === key;
        });
    }, [tickets, selectedDate]);

    const ticketsFilteredByState = useMemo(() => {
        if (stateFilter === "all") return ticketsToShow;
        return ticketsToShow.filter((t) => {
            const code = (t.state?.code ?? "").toLowerCase();
            if (stateFilter === "open") return code === "abierto";
            if (stateFilter === "progress")
                return ["en_progreso", "en progreso", "en_espera"].includes(code);
            if (stateFilter === "resolved") return code === "cerrado" || code === "resuelto";
            return true;
        });
    }, [ticketsToShow, stateFilter]);

    const scopeLabel = useMemo(() => {
        if (isSolicitante) return "Mis tickets (creados por mí)";
        if (scope === "assigned") return "Asignados a mí";
        if (scope === "created") return "Creados por mí";
        return "Todos los que puedo ver";
    }, [isSolicitante, scope]);

    if (!user) return null;

    if (error === "no_permission") {
        return (
            <div className="mx-auto max-w-2xl p-6">
                <Card className="border-amber-200 dark:border-amber-900/50 bg-amber-50/30 dark:bg-amber-900/10">
                    <CardContent className="flex flex-col items-center gap-4 py-8 text-center">
                        <ShieldOff className="h-10 w-10 text-amber-600" />
                        <p className="text-sm text-muted-foreground">
                            No tienes permiso para ver el calendario de tickets.
                        </p>
                    </CardContent>
                </Card>
            </div>
        );
    }

    return (
        <InertiaPageShell className="space-y-6">
            <p className="text-sm text-muted-foreground">
                Tickets por fecha de creación — {scopeLabel}
            </p>

            {isOperativo && (
                <div className="flex flex-wrap gap-2">
                    {[
                        { value: "assigned", label: "Asignados a mí" },
                        { value: "created", label: "Creados por mí" },
                        { value: "all", label: "Todos los que puedo ver" },
                    ].map((opt) => (
                        <Button
                            key={opt.value}
                            type="button"
                            variant={scope === opt.value ? "secondary" : "ghost"}
                            size="sm"
                            onClick={() => setScope(opt.value)}
                        >
                            {opt.label}
                        </Button>
                    ))}
                </div>
            )}

            <Card>
                <CardHeader>
                    <CardTitle className="text-base">Calendario de tickets</CardTitle>
                    <CardDescription>
                        Los días resaltados tienen tickets. Haz clic para filtrar la lista.
                    </CardDescription>
                </CardHeader>
                <CardContent className="flex justify-center">
                    {loading ? (
                        <Skeleton className="h-[280px] w-[320px] rounded-lg" />
                    ) : (
                        <Calendar
                            mode="single"
                            selected={selectedDate}
                            onSelect={setSelectedDate}
                            modifiers={modifiers}
                            modifiersClassNames={modifiersClassNames}
                            locale={calendarLocale}
                            className="rounded-lg border border-border/50 bg-muted/20 p-3"
                        />
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader className="flex flex-row flex-wrap items-center justify-between gap-3">
                    <div>
                        <CardTitle className="text-base flex items-center gap-2">
                            <Ticket className="h-4 w-4" />
                            {scopeLabel}
                        </CardTitle>
                        {selectedDate && (
                            <CardDescription>
                                {selectedDate.toLocaleDateString("es-ES", {
                                    weekday: "long",
                                    day: "numeric",
                                    month: "long",
                                })}
                            </CardDescription>
                        )}
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Select value={stateFilter} onValueChange={setStateFilter}>
                            <SelectTrigger className="w-[160px] h-8 text-xs">
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="open">Abiertos</SelectItem>
                                <SelectItem value="progress">En progreso</SelectItem>
                                <SelectItem value="resolved">Resueltos</SelectItem>
                            </SelectContent>
                        </Select>
                        {selectedDate && (
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setSelectedDate(undefined)}
                            >
                                Ver todos
                            </Button>
                        )}
                    </div>
                </CardHeader>
                <CardContent>
                    {loading ? (
                        <div className="space-y-2">
                            {[1, 2, 3].map((i) => (
                                <Skeleton key={i} className="h-12 w-full" />
                            ))}
                        </div>
                    ) : error === "load_failed" ? (
                        <p className="py-6 text-center text-sm text-muted-foreground">
                            No se pudieron cargar los tickets.
                        </p>
                    ) : ticketsFilteredByState.length === 0 ? (
                        <p className="py-6 text-center text-sm text-muted-foreground">
                            No hay tickets para mostrar.
                        </p>
                    ) : (
                        <ul className="divide-y divide-border">
                            {ticketsFilteredByState.map((t) => {
                                const assigned = t.assigned_user || t.assignedUser;
                                const unassigned = !assigned;
                                return (
                                    <li key={t.id} className="py-3 first:pt-0">
                                        <a
                                            href={`/resolbeb/tickets/${t.id}`}
                                            className={cn(
                                                "flex flex-wrap items-center justify-between gap-2 rounded px-2 py-1.5 -mx-2 hover:bg-muted/30 transition-colors",
                                                unassigned && "border-l-2 border-l-amber-500/50 pl-3"
                                            )}
                                        >
                                            <span className="font-medium">
                                                #{String(t.id).padStart(5, "0")} — {t.subject}
                                            </span>
                                            <div className="flex flex-wrap items-center gap-2">
                                                {unassigned && (
                                                    <span className="inline-flex items-center gap-1 text-[11px] text-amber-600">
                                                        <AlertCircle className="h-3.5 w-3.5" />
                                                        Sin asignar
                                                    </span>
                                                )}
                                                {assigned && (
                                                    <span className="text-xs text-muted-foreground">
                                                        {assigned.name}
                                                    </span>
                                                )}
                                                {t.state?.name && (
                                                    <Badge variant="outline" className="text-[10px]">
                                                        {t.state.name}
                                                    </Badge>
                                                )}
                                            </div>
                                        </a>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </CardContent>
            </Card>
        </InertiaPageShell>
    );
}

Calendario.layout = (page) => <AuthenticatedLayout title="Calendario">{page}</AuthenticatedLayout>;
