import { useMemo, useState } from "react";
import axios from "@/lib/axios";
import { usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { notify } from "@/lib/notify";
import { clearCatalogCache } from "@/lib/catalogCache";
import { getApiErrorMessage } from "@/lib/apiErrors";
import { Grid3X3, Loader2, Save } from "lucide-react";

function cellKey(impactId, urgencyId) {
    return `${impactId}_${urgencyId}`;
}

function buildMatrixState(matrixRows, priorities) {
    const map = {};
    (matrixRows ?? []).forEach((row) => {
        map[cellKey(row.impact_level_id, row.urgency_level_id)] = row.priority_id;
    });
    return map;
}

export default function PriorityMatrix() {
    const { matrix, impactLevels, urgencyLevels, priorities } = usePage().props;

    const initialState = useMemo(
        () => buildMatrixState(matrix, priorities),
        [matrix, priorities]
    );

    const [matrixState, setMatrixState] = useState(initialState);
    const [saving, setSaving] = useState(false);

    const getCellPriority = (impactId, urgencyId) => {
        const key = cellKey(impactId, urgencyId);
        return matrixState[key] ?? priorities?.[0]?.id ?? null;
    };

    const setCellPriority = (impactId, urgencyId, priorityId) => {
        setMatrixState((prev) => ({
            ...prev,
            [cellKey(impactId, urgencyId)]: Number(priorityId),
        }));
    };

    const save = async () => {
        const payload = [];
        (impactLevels ?? []).forEach((imp) => {
            (urgencyLevels ?? []).forEach((urg) => {
                payload.push({
                    impact_level_id: imp.id,
                    urgency_level_id: urg.id,
                    priority_id: getCellPriority(imp.id, urg.id),
                });
            });
        });

        setSaving(true);
        try {
            await axios.post("/api/priority-matrix/bulk", { matrix: payload });
            clearCatalogCache();
            notify.success("Matriz de prioridades guardada correctamente");
        } catch (err) {
            notify.error(getApiErrorMessage(err, "No se pudo guardar la matriz"));
        } finally {
            setSaving(false);
        }
    };

    const emptyCatalogs =
        !(impactLevels?.length) || !(urgencyLevels?.length) || !(priorities?.length);

    return (
        <div className="space-y-6">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <p className="text-muted-foreground text-sm max-w-2xl">
                    Define la prioridad según impacto × urgencia
                </p>
                <Button onClick={save} disabled={saving || emptyCatalogs}>
                    {saving ? (
                        <Loader2 className="h-4 w-4 animate-spin mr-2" />
                    ) : (
                        <Save className="h-4 w-4 mr-2" />
                    )}
                    Guardar cambios
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Impacto × Urgencia → Prioridad</CardTitle>
                    <CardDescription>
                        Filas: nivel de impacto. Columnas: nivel de urgencia.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    {emptyCatalogs ? (
                        <p className="text-sm text-muted-foreground py-6 text-center">
                            Configura primero niveles de impacto, urgencia y prioridades activas.
                        </p>
                    ) : (
                        <div className="overflow-x-auto rounded-md border border-border/60">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/50">
                                        <TableHead className="w-[140px] font-semibold">
                                            Impacto \ Urgencia
                                        </TableHead>
                                        {(urgencyLevels ?? []).map((u) => (
                                            <TableHead
                                                key={u.id}
                                                className="text-center min-w-[140px] font-semibold"
                                            >
                                                {u.name}
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {(impactLevels ?? []).map((imp) => (
                                        <TableRow key={imp.id}>
                                            <TableCell className="font-medium bg-muted/30">
                                                {imp.name}
                                            </TableCell>
                                            {(urgencyLevels ?? []).map((urg) => {
                                                const value = getCellPriority(imp.id, urg.id);
                                                return (
                                                    <TableCell key={urg.id} className="p-2">
                                                        <Select
                                                            value={value ? String(value) : undefined}
                                                            onValueChange={(v) =>
                                                                setCellPriority(imp.id, urg.id, v)
                                                            }
                                                        >
                                                            <SelectTrigger className="h-9">
                                                                <SelectValue placeholder="Prioridad" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {(priorities ?? []).map((p) => (
                                                                    <SelectItem
                                                                        key={p.id}
                                                                        value={String(p.id)}
                                                                    >
                                                                        {p.name}
                                                                    </SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                    </TableCell>
                                                );
                                            })}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}

PriorityMatrix.layout = (page) => (
    <AuthenticatedLayout title="Matriz de prioridades">{page}</AuthenticatedLayout>
);
