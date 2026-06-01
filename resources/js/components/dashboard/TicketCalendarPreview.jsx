import { useCallback, useEffect, useMemo, useState } from "react";
import NavLink from "@/components/NavLink";
import { es as dateFnsEs, enUS as dateFnsEn } from "date-fns/locale";
import axios from "@/lib/axios";
import { useTheme } from "@/hooks/useTheme";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Calendar } from "@/components/ui/calendar";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import { cn } from "@/lib/utils";
import { CalendarDays } from "lucide-react";

const PER_PAGE = 200;

function toDateKey(d) {
    if (!d) return "";
    const date = d instanceof Date ? d : new Date(d);
    if (Number.isNaN(date.getTime())) return "";
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
}

/** Vista compacta del calendario de tickets del solicitante (incrustada en Inicio). */
export function TicketCalendarPreview({ ticketLinkBase = "/resolbeb/tickets" }) {
    const { locale: appLocale } = useTheme();
    const calendarLocale = appLocale === "en" ? dateFnsEn : dateFnsEs;
    const [tickets, setTickets] = useState([]);
    const [loading, setLoading] = useState(true);
    const [selectedDate, setSelectedDate] = useState(undefined);

    const loadTickets = useCallback(() => {
        setLoading(true);
        axios
            .get("/api/tickets", { params: { per_page: PER_PAGE } })
            .then((res) => setTickets(res.data?.data ?? []))
            .catch(() => setTickets([]))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        loadTickets();
    }, [loadTickets]);

    const ticketDatesSet = useMemo(() => {
        const set = new Set();
        tickets.forEach((t) => {
            const at = t?.created_at;
            if (at) set.add(toDateKey(at));
        });
        return set;
    }, [tickets]);

    const dayTickets = useMemo(() => {
        if (!selectedDate) return [];
        const key = toDateKey(selectedDate);
        return tickets.filter((t) => t.created_at && toDateKey(t.created_at) === key);
    }, [tickets, selectedDate]);

    return (
        <Card className="border-border/60">
            <CardHeader className="pb-2">
                <div className="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <CardTitle className="text-sm font-semibold flex items-center gap-2">
                            <CalendarDays className="h-4 w-4 text-primary" />
                            Calendario de mis tickets
                        </CardTitle>
                        <CardDescription className="text-xs">
                            Días en los que abriste solicitudes. Selecciona un día para ver el detalle.
                        </CardDescription>
                    </div>
                    <Button asChild variant="outline" size="sm" className="h-8 text-xs shrink-0">
                        <NavLink href="/calendario">Ver calendario completo</NavLink>
                    </Button>
                </div>
            </CardHeader>
            <CardContent className="space-y-4">
                {loading ? (
                    <Skeleton className="mx-auto h-[260px] w-full max-w-[320px] rounded-lg" />
                ) : (
                    <div className="flex justify-center">
                        <Calendar
                            mode="single"
                            selected={selectedDate}
                            onSelect={setSelectedDate}
                            locale={calendarLocale}
                            modifiers={{
                                hasTicket: (date) => ticketDatesSet.has(toDateKey(date)),
                            }}
                            modifiersClassNames={{
                                hasTicket: "bg-primary/20 font-semibold text-primary ring-1 ring-primary/30",
                            }}
                            className="rounded-md border border-border/60 p-2"
                        />
                    </div>
                )}
                {selectedDate && (
                    <div className="rounded-lg border border-border/50 bg-muted/20 p-3 space-y-2">
                        <p className="text-xs font-medium text-muted-foreground">
                            {selectedDate.toLocaleDateString("es-MX", {
                                weekday: "long",
                                day: "numeric",
                                month: "long",
                            })}
                        </p>
                        {dayTickets.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Sin tickets ese día.</p>
                        ) : (
                            <ul className="space-y-1.5">
                                {dayTickets.map((t) => (
                                    <li key={t.id}>
                                        <NavLink
                                            href={`${ticketLinkBase}/${t.id}`}
                                            className={cn(
                                                "flex items-center justify-between gap-2 rounded-md px-2 py-1.5 text-sm",
                                                "hover:bg-background transition-colors"
                                            )}
                                        >
                                            <span className="font-medium truncate">
                                                #{String(t.id).padStart(5, "0")} — {t.subject}
                                            </span>
                                            <Badge variant="secondary" className="text-[10px] shrink-0">
                                                {t.state?.name ?? "—"}
                                            </Badge>
                                        </NavLink>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
