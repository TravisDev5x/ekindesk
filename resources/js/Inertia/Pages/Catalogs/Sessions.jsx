import { useCallback, useEffect, useMemo, useState } from "react";
import { formatDistanceToNow } from "date-fns";
import { es } from "date-fns/locale";
import axios from "@/lib/axios";
import { notify } from "@/lib/notify";
import { getApiErrorMessage } from "@/lib/apiErrors";
import { useAuth } from "@/context/AuthContext";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import CatalogPage from "@/Inertia/components/CatalogPage";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from "@/components/ui/tooltip";
import { LogOut, Monitor, RefreshCw, Smartphone } from "lucide-react";
import { cn } from "@/lib/utils";

const REFRESH_MS = 60_000;

function formatLastActivity(timestamp) {
    if (!timestamp) return "—";
    try {
        return formatDistanceToNow(new Date(timestamp * 1000), {
            addSuffix: true,
            locale: es,
        });
    } catch {
        return "—";
    }
}

function deviceIcon(browser) {
    const mobile = /iphone|android|mobile|ipad/i.test(String(browser ?? ""));
    return mobile ? Smartphone : Monitor;
}

export default function Sessions() {
    const { user } = useAuth();
    const [sessions, setSessions] = useState([]);
    const [loading, setLoading] = useState(true);
    const [loggingOutUserId, setLoggingOutUserId] = useState(null);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const { data } = await axios.get("/api/sessions");
            const list = (data.sessions ?? []).map((s) => ({
                ...s,
                is_current: user?.id != null && s.user_id === user.id,
            }));
            setSessions(list);
        } catch (err) {
            notify.error(getApiErrorMessage(err, "No se pudieron cargar las sesiones"));
            setSessions([]);
        } finally {
            setLoading(false);
        }
    }, [user?.id]);

    useEffect(() => {
        load();
    }, [load]);

    useEffect(() => {
        const id = setInterval(load, REFRESH_MS);
        return () => clearInterval(id);
    }, [load]);

    const handleLogout = async (row) => {
        if (row.is_current) return;
        if (!window.confirm(`¿Cerrar sesión remota de ${row.name}?`)) return;
        setLoggingOutUserId(row.user_id);
        try {
            await axios.post("/api/sessions/logout-user", { user_id: row.user_id });
            notify.success("Sesiones del usuario cerradas");
            await load();
        } catch (err) {
            notify.error(getApiErrorMessage(err, "No se pudo cerrar la sesión"));
        } finally {
            setLoggingOutUserId(null);
        }
    };

    const columns = useMemo(
        () => [
            {
                key: "name",
                label: "Usuario",
                render: (row) => (
                    <div className="min-w-0">
                        <p className="font-medium truncate">{row.name}</p>
                        <p className="text-xs text-muted-foreground truncate">
                            {row.email || row.employee_number || "—"}
                        </p>
                    </div>
                ),
            },
            {
                key: "ip_address",
                label: "IP",
                render: (row) => (
                    <span className="font-mono text-xs">{row.ip_address || "—"}</span>
                ),
            },
            {
                key: "browser",
                label: "Dispositivo",
                render: (row) => {
                    const Icon = deviceIcon(row.browser);
                    return (
                        <span className="inline-flex items-center gap-1.5 text-sm">
                            <Icon className="h-4 w-4 text-muted-foreground shrink-0" />
                            {row.browser || "—"}
                        </span>
                    );
                },
            },
            {
                key: "last_activity",
                label: "Última actividad",
                render: (row) => formatLastActivity(row.last_activity),
            },
            {
                key: "is_current",
                label: "Actual",
                render: (row) =>
                    row.is_current ? (
                        <Badge variant="default">Tú</Badge>
                    ) : (
                        <span className="text-muted-foreground text-sm">—</span>
                    ),
            },
        ],
        []
    );

    const customActions = (row) => (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="h-8 w-8 text-destructive hover:text-destructive hover:bg-destructive/10"
                    disabled={row.is_current || loggingOutUserId === row.user_id}
                    onClick={() => handleLogout(row)}
                >
                    <LogOut className="h-4 w-4" />
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                {row.is_current ? "No puedes cerrar tu sesión actual" : "Cerrar sesión remota"}
            </TooltipContent>
        </Tooltip>
    );

    return (
        <div className="space-y-4">
            <div className="flex justify-end">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={load}
                    disabled={loading}
                    className="gap-2"
                >
                    <RefreshCw className={cn("h-4 w-4", loading && "animate-spin")} />
                    Actualizar
                </Button>
            </div>

            <CatalogPage
                title="Sesiones activas"
                description="Monitor de sesiones con actividad reciente (requiere users.manage)"
                columns={columns}
                data={sessions}
                loading={loading}
                searchable
                canCreate={false}
                canEdit={false}
                canDelete={false}
                customActions={customActions}
                rowKey={(row) => `${row.user_id}-${row.last_activity}-${row.ip_address}`}
                emptyMessage="No hay sesiones activas en este momento"
            />
        </div>
    );
}

Sessions.layout = (page) => <AuthenticatedLayout title="Sesiones activas">{page}</AuthenticatedLayout>;
