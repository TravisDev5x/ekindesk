import { useEffect, useState } from "react";
import { Link, usePage } from "@inertiajs/react";
import PortalLayout from "@/Inertia/Layouts/PortalLayout";
import { useAuth } from "@/context/AuthContext";
import { getTenantBrandName } from "@/lib/tenantBranding";
import axios from "@/lib/axios";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Loader2, PlusCircle, TicketIcon } from "lucide-react";

export default function PortalDashboard() {
    const { tenant = {} } = usePage().props;
    const { user } = useAuth();
    const brandName = getTenantBrandName(tenant, "Portal");

    const [stats, setStats] = useState(null);
    const [recentTickets, setRecentTickets] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        axios
            .get("/api/my-tickets", { params: { per_page: 5, page: 1 } })
            .then((res) => {
                const data = res.data;
                const tickets = data.data ?? data.tickets ?? [];
                setRecentTickets(tickets.slice(0, 5));
                setStats({
                    total: data.meta?.total ?? data.total ?? tickets.length,
                });
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    }, []);

    return (
        <PortalLayout title="Inicio">
            {/* Welcome */}
            <div className="mb-8">
                <h1 className="text-2xl font-bold">
                    {tenant.portal_welcome_message ||
                        `Bienvenido al portal de soporte${user?.first_name ? `, ${user.first_name}` : ""}.`}
                </h1>
                <p className="mt-1 text-muted-foreground">
                    Aquí puedes abrir solicitudes y dar seguimiento a tus tickets.
                </p>
            </div>

            {/* Stats row */}
            <div className="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card>
                    <CardHeader className="pb-2">
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Total de tickets
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-3xl font-bold">
                            {loading ? "—" : (stats?.total ?? 0)}
                        </p>
                    </CardContent>
                </Card>
            </div>

            {/* Recent tickets */}
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-semibold">Tickets recientes</h2>
                    <Button asChild size="sm">
                        <Link href="/tickets/new">
                            <PlusCircle className="mr-1.5 h-4 w-4" />
                            Nuevo ticket
                        </Link>
                    </Button>
                </div>

                {loading ? (
                    <div className="flex justify-center py-12">
                        <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                    </div>
                ) : recentTickets.length === 0 ? (
                    <EmptyState />
                ) : (
                    <div className="divide-y rounded-lg border">
                        {recentTickets.map((ticket) => (
                            <TicketRow key={ticket.id} ticket={ticket} />
                        ))}
                    </div>
                )}

                {!loading && recentTickets.length > 0 && (
                    <div className="text-right">
                        <Link
                            href="/tickets"
                            className="text-sm text-primary hover:underline"
                        >
                            Ver todos los tickets →
                        </Link>
                    </div>
                )}
            </div>
        </PortalLayout>
    );
}

function TicketRow({ ticket }) {
    return (
        <Link
            href={`/tickets/${ticket.id}`}
            className="flex items-center gap-3 px-4 py-3 hover:bg-muted/50 transition-colors"
        >
            <TicketIcon className="h-4 w-4 shrink-0 text-muted-foreground" />
            <div className="min-w-0 flex-1">
                <p className="truncate text-sm font-medium">{ticket.subject}</p>
                <p className="text-xs text-muted-foreground">
                    {ticket.folio && <span className="mr-2 font-mono">#{ticket.folio}</span>}
                    {ticket.created_at
                        ? new Date(ticket.created_at).toLocaleDateString("es-MX")
                        : ""}
                </p>
            </div>
            {ticket.state?.name && (
                <Badge variant="secondary" className="shrink-0 text-xs">
                    {ticket.state.name}
                </Badge>
            )}
        </Link>
    );
}

function EmptyState() {
    return (
        <div className="flex flex-col items-center gap-3 rounded-lg border border-dashed py-12 text-center">
            <TicketIcon className="h-10 w-10 text-muted-foreground/40" />
            <div>
                <p className="font-medium">Sin tickets por el momento</p>
                <p className="text-sm text-muted-foreground">
                    Crea un ticket cuando necesites ayuda del equipo de soporte.
                </p>
            </div>
            <Button asChild size="sm" className="mt-2">
                <Link href="/tickets/new">Abrir primer ticket</Link>
            </Button>
        </div>
    );
}
