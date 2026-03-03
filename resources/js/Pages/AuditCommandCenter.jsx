import { useState } from "react";
import axios from "@/lib/axios";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import { notify } from "@/lib/notify";
import { getApiErrorMessage } from "@/lib/apiErrors";
import AuditTimeline from "@/Pages/AuditTimeline";
import { Search, Download, Loader2, ShieldCheck, Ticket } from "lucide-react";

export default function AuditCommandCenter() {
    const [dateFrom, setDateFrom] = useState("");
    const [dateTo, setDateTo] = useState("");
    const [ticketIds, setTicketIds] = useState("");
    const [auditLogs, setAuditLogs] = useState([]);
    const [selectedTicketId, setSelectedTicketId] = useState(null);
    const [isLoading, setIsLoading] = useState(false);
    const [isExporting, setIsExporting] = useState(false);

    const fetchLogs = async () => {
        setIsLoading(true);
        setSelectedTicketId(null);
        try {
            const params = new URLSearchParams();
            if (dateFrom) params.set("from", dateFrom);
            if (dateTo) params.set("to", dateTo);
            if (ticketIds.trim()) params.set("ticket_ids", ticketIds.trim());
            const { data } = await axios.get("/api/tickets/audit-logs?" + params.toString());
            setAuditLogs(Array.isArray(data.data) ? data.data : []);
        } catch (err) {
            notify.error(getApiErrorMessage(err, "No se pudieron cargar los logs de auditoría"));
            setAuditLogs([]);
        } finally {
            setIsLoading(false);
        }
    };

    const handleExport = async () => {
        const params = new URLSearchParams();
        if (dateFrom) params.set("start_date", dateFrom);
        if (dateTo) params.set("end_date", dateTo);
        if (ticketIds.trim()) params.set("ticket_ids", ticketIds.trim());
        setIsExporting(true);
        try {
            const response = await axios.get("/api/tickets/audit-export?" + params.toString(), {
                responseType: "blob",
            });
            const disposition = response.headers["content-disposition"];
            const match = disposition && disposition.match(/filename="?([^";]+)"?/);
            const filename = match ? match[1] : `auditoria_tickets_${new Date().toISOString().slice(0, 10)}.xlsx`;
            const blob = new Blob([response.data], {
                type: response.headers["content-type"] || "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.setAttribute("download", filename);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
            notify.success("Reporte descargado correctamente");
        } catch {
            // Con responseType: 'blob', errores 4xx/5xx llegan como blob; toast genérico evita parsear JSON
            notify.error("No se pudo descargar el reporte Excel");
        } finally {
            setIsExporting(false);
        }
    };

    const ticketsWithActivity = [...new Set((auditLogs || []).map((l) => l.ticket_id))].sort((a, b) => a - b);
    const logsForSelected = (auditLogs || []).filter((l) => l.ticket_id === selectedTicketId);

    return (
        <div className="space-y-4 p-4 md:p-6 max-w-[1600px] mx-auto">
            <header>
                <h1 className="text-2xl font-semibold flex items-center gap-2">
                    <ShieldCheck className="h-6 w-6" />
                    Centro de Mando de Auditoría
                </h1>
                <p className="text-muted-foreground text-sm mt-1">
                    Filtra por rango de fechas y/o IDs de tickets. Selecciona un ticket para ver la línea de tiempo.
                </p>
            </header>

            <Card>
                <CardHeader className="pb-4">
                    <CardTitle className="text-lg">Filtros y acciones</CardTitle>
                    <CardDescription>Indica fechas y/o IDs de tickets (separados por comas) y pulsa Buscar.</CardDescription>
                </CardHeader>
                <CardContent className="flex flex-wrap gap-4 items-end">
                    <div className="space-y-2">
                        <Label className="text-xs">Fecha de inicio</Label>
                        <Input
                            type="date"
                            value={dateFrom}
                            onChange={(e) => setDateFrom(e.target.value)}
                            className="w-[180px]"
                        />
                    </div>
                    <div className="space-y-2">
                        <Label className="text-xs">Fecha de fin</Label>
                        <Input
                            type="date"
                            value={dateTo}
                            onChange={(e) => setDateTo(e.target.value)}
                            className="w-[180px]"
                        />
                    </div>
                    <div className="space-y-2 flex-1 min-w-[200px]">
                        <Label className="text-xs">IDs de tickets (separados por comas)</Label>
                        <Input
                            placeholder="Ej: 1, 5, 12"
                            value={ticketIds}
                            onChange={(e) => setTicketIds(e.target.value)}
                        />
                    </div>
                    <Button onClick={fetchLogs} disabled={isLoading}>
                        {isLoading ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <Search className="h-4 w-4 mr-2" />}
                        Buscar / Filtrar
                    </Button>
                    <Button variant="outline" onClick={handleExport} disabled={isExporting}>
                        {isExporting ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <Download className="h-4 w-4 mr-2" />}
                        Descargar Reporte Excel
                    </Button>
                </CardContent>
            </Card>

            <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 min-h-[400px]">
                <Card className="lg:col-span-1 flex flex-col">
                    <CardHeader className="pb-2">
                        <CardTitle className="text-base">Tickets con actividad</CardTitle>
                        <CardDescription>Haz clic en un ticket para ver su línea de tiempo.</CardDescription>
                    </CardHeader>
                    <CardContent className="flex-1 overflow-y-auto">
                        {isLoading ? (
                            <div className="space-y-2">
                                {[1, 2, 3, 4, 5].map((i) => (
                                    <Skeleton key={i} className="h-10 w-full" />
                                ))}
                            </div>
                        ) : ticketsWithActivity.length === 0 ? (
                            <p className="text-sm text-muted-foreground py-4 text-center">
                                No hay resultados. Ajusta los filtros y pulsa Buscar.
                            </p>
                        ) : (
                            <ul className="space-y-1">
                                {ticketsWithActivity.map((tid) => {
                                    const count = auditLogs.filter((l) => l.ticket_id === tid).length;
                                    return (
                                        <li key={tid}>
                                            <button
                                                type="button"
                                                onClick={() => setSelectedTicketId(tid)}
                                                className={`
                                                    w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors
                                                    flex items-center justify-between gap-2
                                                    ${selectedTicketId === tid
                                                        ? "bg-primary text-primary-foreground"
                                                        : "hover:bg-muted"
                                                    }
                                                `}
                                            >
                                                <span className="flex items-center gap-2">
                                                    <Ticket className="h-4 w-4 shrink-0" />
                                                    #{String(tid).padStart(5, "0")}
                                                </span>
                                                <span className="text-xs opacity-80">{count} evento{count !== 1 ? "s" : ""}</span>
                                            </button>
                                        </li>
                                    );
                                })}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <div className="lg:col-span-2 flex flex-col min-h-[300px]">
                    {selectedTicketId ? (
                        <AuditTimeline logs={logsForSelected} />
                    ) : (
                        <Card className="flex-1 flex items-center justify-center">
                            <CardContent className="py-12 text-center text-muted-foreground text-sm">
                                Selecciona un ticket de la lista para ver la línea de tiempo de auditoría.
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </div>
    );
}
