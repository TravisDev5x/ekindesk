import { useCallback, useEffect, useState } from "react";
import { Link } from "@inertiajs/react";
import PortalLayout from "@/Inertia/Layouts/PortalLayout";
import axios from "@/lib/axios";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Loader2, PlusCircle, Search, TicketIcon } from "lucide-react";
import { cn } from "@/lib/utils";

const STATE_COLORS = {
    abierto: "bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300",
    en_progreso: "bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300",
    resuelto: "bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300",
    cerrado: "bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300",
};

export default function PortalTicketsIndex() {
    const [tickets, setTickets] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [search, setSearch] = useState("");
    const [debouncedSearch, setDebouncedSearch] = useState("");

    // Debounce search
    useEffect(() => {
        const t = setTimeout(() => setDebouncedSearch(search), 350);
        return () => clearTimeout(t);
    }, [search]);

    const fetchTickets = useCallback(() => {
        setLoading(true);
        axios
            .get("/api/my-tickets", {
                params: { page, per_page: 15, search: debouncedSearch || undefined },
            })
            .then((res) => {
                setTickets(res.data.data ?? res.data.tickets ?? []);
                setMeta(res.data.meta ?? null);
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [page, debouncedSearch]);

    useEffect(() => {
        fetchTickets();
    }, [fetchTickets]);

    // Reset page on search change
    useEffect(() => {
        setPage(1);
    }, [debouncedSearch]);

    return (
        <PortalLayout title="Mis Tickets">
            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-2xl font-bold">Mis Tickets</h1>
                <Button asChild>
                    <Link href="/tickets/new">
                        <PlusCircle className="mr-1.5 h-4 w-4" />
                        Nuevo ticket
                    </Link>
                </Button>
            </div>

            {/* Search */}
            <div className="relative mb-4">
                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    placeholder="Buscar por asunto o folio…"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="pl-9"
                />
            </div>

            {/* List */}
            {loading ? (
                <div className="flex justify-center py-16">
                    <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                </div>
            ) : tickets.length === 0 ? (
                <EmptyState hasSearch={!!debouncedSearch} />
            ) : (
                <>
                    <div className="divide-y rounded-lg border">
                        {tickets.map((ticket) => (
                            <TicketRow key={ticket.id} ticket={ticket} />
                        ))}
                    </div>

                    {/* Pagination */}
                    {meta && meta.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-between text-sm">
                            <span className="text-muted-foreground">
                                {meta.total} tickets
                            </span>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={page === 1}
                                    onClick={() => setPage((p) => p - 1)}
                                >
                                    Anterior
                                </Button>
                                <span className="flex items-center px-2 text-muted-foreground">
                                    {page} / {meta.last_page}
                                </span>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={page >= meta.last_page}
                                    onClick={() => setPage((p) => p + 1)}
                                >
                                    Siguiente
                                </Button>
                            </div>
                        </div>
                    )}
                </>
            )}
        </PortalLayout>
    );
}

function TicketRow({ ticket }) {
    const stateCode = ticket.state?.code ?? ticket.state?.name?.toLowerCase() ?? "abierto";
    const colorClass = STATE_COLORS[stateCode] ?? STATE_COLORS.abierto;

    return (
        <Link
            href={`/tickets/${ticket.id}`}
            className="flex items-start gap-3 px-4 py-4 hover:bg-muted/50 transition-colors"
        >
            <TicketIcon className="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
            <div className="min-w-0 flex-1 space-y-0.5">
                <p className="truncate font-medium leading-snug">{ticket.subject}</p>
                <p className="text-xs text-muted-foreground">
                    {ticket.folio && (
                        <span className="mr-2 font-mono text-primary/80">#{ticket.folio}</span>
                    )}
                    {ticket.ticket_type?.name && (
                        <span className="mr-2">{ticket.ticket_type.name}</span>
                    )}
                    {ticket.created_at &&
                        new Date(ticket.created_at).toLocaleDateString("es-MX", {
                            day: "2-digit",
                            month: "short",
                            year: "numeric",
                        })}
                </p>
            </div>
            {ticket.state?.name && (
                <span
                    className={cn(
                        "shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium",
                        colorClass
                    )}
                >
                    {ticket.state.name}
                </span>
            )}
        </Link>
    );
}

function EmptyState({ hasSearch }) {
    return (
        <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed py-14 text-center">
            <TicketIcon className="h-10 w-10 text-muted-foreground/40" />
            {hasSearch ? (
                <>
                    <p className="font-medium">Sin resultados</p>
                    <p className="text-sm text-muted-foreground">
                        Ningún ticket coincide con tu búsqueda.
                    </p>
                </>
            ) : (
                <>
                    <p className="font-medium">Sin tickets todavía</p>
                    <p className="text-sm text-muted-foreground">
                        Cuando abras un ticket aparecerá aquí.
                    </p>
                    <Button asChild size="sm" className="mt-1">
                        <Link href="/tickets/new">Abrir ticket</Link>
                    </Button>
                </>
            )}
        </div>
    );
}
