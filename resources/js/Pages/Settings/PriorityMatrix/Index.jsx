import { useEffect, useState } from "react";
import axios from "@/lib/axios";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { notify } from "@/lib/notify";
import { clearCatalogCache } from "@/lib/catalogCache";
import { getApiErrorMessage } from "@/lib/apiErrors";
import { Save, Loader2, Grid3X3 } from "lucide-react";

function cellKey(impactId, urgencyId) {
    return `${impactId}_${urgencyId}`;
}

export default function PriorityMatrixIndex() {
    const [impactLevels, setImpactLevels] = useState([]);
    const [urgencyLevels, setUrgencyLevels] = useState([]);
    const [priorities, setPriorities] = useState([]);
    const [matrixState, setMatrixState] = useState({});
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    const load = async () => {
        setLoading(true);
        try {
            const { data } = await axios.get("/api/priority-matrix");
            setImpactLevels(data.impact_levels || []);
            setUrgencyLevels(data.urgency_levels || []);
            setPriorities(data.priorities || []);
            const map = {};
            (data.matrix || []).forEach((row) => {
                map[cellKey(row.impact_level_id, row.urgency_level_id)] = row.priority_id;
            });
            setMatrixState(map);
        } catch (err) {
            notify.error(getApiErrorMessage(err, "No se pudo cargar la matriz"));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => { load(); }, []);

    const getCellPriority = (impactId, urgencyId) => {
        const key = cellKey(impactId, urgencyId);
        return matrixState[key] ?? (priorities[0]?.id ?? null);
    };

    const setCellPriority = (impactId, urgencyId, priorityId) => {
        setMatrixState((prev) => ({
            ...prev,
            [cellKey(impactId, urgencyId)]: Number(priorityId),
        }));
    };

    const save = async () => {
        const matrix = [];
        impactLevels.forEach((imp) => {
            urgencyLevels.forEach((urg) => {
                matrix.push({
                    impact_level_id: imp.id,
                    urgency_level_id: urg.id,
                    priority_id: getCellPriority(imp.id, urg.id),
                });
            });
        });
        setSaving(true);
        try {
            await axios.post("/api/priority-matrix/bulk", { matrix });
            clearCatalogCache();
            notify.success("Configuración de la matriz de prioridades guardada correctamente.");
            load();
        } catch (err) {
            notify.error(getApiErrorMessage(err, "No se pudo guardar la configuración"));
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <div className="flex flex-col items-center justify-center py-16 gap-3">
                <Loader2 className="h-8 w-8 animate-spin text-primary" />
                <p className="text-sm text-muted-foreground">Cargando matriz de prioridades…</p>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-semibold flex items-center gap-2">
                        <Grid3X3 className="h-6 w-6" />
                        Matriz de prioridades
                    </h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Configura la prioridad resultante para cada combinación Impacto × Urgencia.
                    </p>
                </div>
                <Button onClick={save} disabled={saving}>
                    {saving ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : <Save className="h-4 w-4 mr-2" />}
                    Guardar configuración
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Impacto × Urgencia → Prioridad</CardTitle>
                    <CardDescription>
                        Filas: nivel de impacto. Columnas: nivel de urgencia. En cada celda elige la prioridad del ticket.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="overflow-x-auto rounded-md border border-border/60">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/50">
                                    <TableHead className="w-[140px] font-semibold">Impacto \ Urgencia</TableHead>
                                    {urgencyLevels.map((u) => (
                                        <TableHead key={u.id} className="text-center min-w-[140px] font-semibold">
                                            {u.name}
                                        </TableHead>
                                    ))}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {impactLevels.map((imp) => (
                                    <TableRow key={imp.id}>
                                        <TableCell className="font-medium bg-muted/30">{imp.name}</TableCell>
                                        {urgencyLevels.map((urg) => (
                                            <TableCell key={urg.id} className="p-2">
                                                <select
                                                    className="w-full h-9 rounded-md border border-input bg-background px-3 py-1 text-sm ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                                    value={getCellPriority(imp.id, urg.id)}
                                                    onChange={(e) => setCellPriority(imp.id, urg.id, e.target.value)}
                                                >
                                                    {priorities.map((p) => (
                                                        <option key={p.id} value={p.id}>
                                                            {p.name}
                                                        </option>
                                                    ))}
                                                </select>
                                            </TableCell>
                                        ))}
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                    {(impactLevels.length === 0 || urgencyLevels.length === 0) && (
                        <p className="text-sm text-muted-foreground mt-4">
                            No hay niveles de impacto o urgencia. Ejecuta el seeder PriorityMatrixSeeder o crea los catálogos primero.
                        </p>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
