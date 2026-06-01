import { useEffect, useMemo, useState } from "react";
import { Pencil, Plus, Search, Trash2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Switch } from "@/components/ui/switch";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { TableWrapper } from "@/components/ui/table-wrapper";
import { TablePagination } from "@/components/ui/table-pagination";
import InertiaPageShell from "@/Inertia/components/InertiaPageShell";
import { clientActiveBadge, clientInactiveBadge } from "@/lib/badgeStyles";
import { cn } from "@/lib/utils";

const PER_PAGE_OPTIONS = ["10", "15", "25", "50", "100"];

function rowMatchesSearch(row, query) {
    const q = query.trim().toLowerCase();
    if (!q) return true;
    return Object.values(row).some((val) => {
        if (val === null || val === undefined) return false;
        if (typeof val === "object") return false;
        return String(val).toLowerCase().includes(q);
    });
}

function renderCell(col, row, onToggle) {
    if (col.key === "is_active" && onToggle) {
        const active = Boolean(row.is_active);
        const activeLabel = col.activeLabel ?? "Activo";
        const inactiveLabel = col.inactiveLabel ?? "Inactivo";
        return (
            <div className="flex items-center gap-2">
                <Switch checked={active} onCheckedChange={() => onToggle(row)} />
                <Badge
                    variant="outline"
                    className={cn(
                        "text-xs",
                        active ? clientActiveBadge : clientInactiveBadge
                    )}
                >
                    {active ? activeLabel : inactiveLabel}
                </Badge>
            </div>
        );
    }
    if (col.render) return col.render(row);
    return row[col.key] ?? "—";
}

export default function CatalogPage({
    title,
    description,
    columns = [],
    data = [],
    onAdd,
    onEdit,
    onDelete = null,
    onToggle = null,
    loading = false,
    searchable = true,
    addLabel = "Nuevo",
    emptyMessage = "No hay registros",
    canCreate = true,
    canEdit = true,
    canDelete = true,
    deleteNameKey = "name",
    customActions = null,
    rowKey = null,
}) {
    const storageKey = `catalog_perpage_${String(title).toLowerCase().replace(/\s+/g, "_")}`;
    const [search, setSearch] = useState("");
    const [debouncedSearch, setDebouncedSearch] = useState("");
    const [page, setPage] = useState(1);
    const [perPage, setPerPage] = useState(() => localStorage.getItem(storageKey) || "10");

    useEffect(() => {
        const id = setTimeout(() => setDebouncedSearch(search), 300);
        return () => clearTimeout(id);
    }, [search]);

    useEffect(() => {
        setPage(1);
    }, [debouncedSearch, perPage]);

    useEffect(() => {
        localStorage.setItem(storageKey, perPage);
    }, [perPage, storageKey]);

    const filtered = useMemo(
        () => (searchable ? data.filter((row) => rowMatchesSearch(row, debouncedSearch)) : data),
        [data, debouncedSearch, searchable]
    );

    const total = filtered.length;
    const lastPage = Math.max(1, Math.ceil(total / Number(perPage)));
    const currentPage = Math.min(page, lastPage);
    const paginated = useMemo(
        () => filtered.slice((currentPage - 1) * Number(perPage), currentPage * Number(perPage)),
        [filtered, currentPage, perPage]
    );

    useEffect(() => {
        if (currentPage !== page) setPage(currentPage);
    }, [currentPage, page]);

    const from = total === 0 ? 0 : (currentPage - 1) * Number(perPage) + 1;
    const to = Math.min(currentPage * Number(perPage), total);
    const showActions = Boolean(customActions || (canEdit && onEdit) || (canDelete && onDelete));
    const colSpan = columns.length + (showActions ? 1 : 0);

    const handleDelete = (row) => {
        if (!onDelete) return;
        const name =
            row[deleteNameKey] ?? row.name ?? row.id ?? "este registro";
        if (
            confirm(
                `¿Eliminar "${name}"? Esta acción no se puede deshacer.`
            )
        ) {
            onDelete(row);
        }
    };

    return (
        <InertiaPageShell className="space-y-6">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                {description ? (
                    <p className="text-muted-foreground text-sm max-w-2xl">{description}</p>
                ) : (
                    <span />
                )}
                {canCreate && onAdd && (
                    <Button type="button" variant="default" onClick={onAdd} className="gap-2 shrink-0">
                        <Plus className="h-4 w-4" />
                        {addLabel}
                    </Button>
                )}
            </div>

            {searchable && (
                <div className="relative max-w-md">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Buscar…"
                        className="pl-9"
                        aria-label="Buscar en el catálogo"
                    />
                </div>
            )}

            <div className="rounded-xl border overflow-hidden">
                <TableWrapper>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                {columns.map((col) => (
                                    <TableHead
                                        key={col.key}
                                        className={col.className ?? col.width ?? undefined}
                                    >
                                        {col.label}
                                    </TableHead>
                                ))}
                                {showActions && (
                                    <TableHead className="text-right w-[180px]">Acciones</TableHead>
                                )}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {loading ? (
                                [...Array(5)].map((_, i) => (
                                    <TableRow key={i}>
                                        {columns.map((col) => (
                                            <TableCell
                                                key={col.key}
                                                className={col.className ?? col.width ?? undefined}
                                            >
                                                <Skeleton className="h-5 w-full max-w-[120px]" />
                                            </TableCell>
                                        ))}
                                        {showActions && (
                                            <TableCell className="text-right">
                                                <Skeleton className="h-8 w-24 ml-auto" />
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))
                            ) : paginated.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={colSpan}
                                        className="text-center text-muted-foreground py-10"
                                    >
                                        {emptyMessage}
                                    </TableCell>
                                </TableRow>
                            ) : (
                                paginated.map((row) => (
                                    <TableRow
                                        key={
                                            rowKey
                                                ? rowKey(row)
                                                : row.id ?? row.user_id ?? JSON.stringify(row)
                                        }
                                    >
                                        {columns.map((col) => (
                                            <TableCell
                                                key={col.key}
                                                className={
                                                    col.key === "id"
                                                        ? "font-medium"
                                                        : col.key === "created_at"
                                                          ? "text-muted-foreground"
                                                          : undefined
                                                }
                                            >
                                                {renderCell(col, row, onToggle)}
                                            </TableCell>
                                        ))}
                                        {showActions && (
                                            <TableCell className="text-right">
                                                {customActions ? (
                                                    customActions(row)
                                                ) : (
                                                    <div className="flex items-center justify-end gap-1">
                                                        {canEdit && onEdit && (
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                className="h-8 gap-1"
                                                                onClick={() => onEdit(row)}
                                                            >
                                                                <Pencil className="h-3.5 w-3.5" />
                                                                Editar
                                                            </Button>
                                                        )}
                                                        {canDelete && onDelete && (
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                className="h-8 gap-1 text-destructive hover:text-destructive hover:bg-destructive/10"
                                                                onClick={() => handleDelete(row)}
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                                Eliminar
                                                            </Button>
                                                        )}
                                                    </div>
                                                )}
                                            </TableCell>
                                        )}
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </TableWrapper>

                {!loading && total > Number(perPage) && (
                    <TablePagination
                        total={total}
                        from={from}
                        to={to}
                        currentPage={currentPage}
                        lastPage={lastPage}
                        perPage={perPage}
                        perPageOptions={PER_PAGE_OPTIONS}
                        onPerPageChange={setPerPage}
                        onPageChange={setPage}
                        loading={loading}
                    />
                )}
            </div>
        </InertiaPageShell>
    );
}
