import { useEffect, useState, useMemo } from "react";
import axios from "@/lib/axios";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { TableWrapper } from "@/components/ui/table-wrapper";
import { Switch } from "@/components/ui/switch";
import { Label } from "@/components/ui/label";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { notify } from "@/lib/notify";
import { clearCatalogCache } from "@/lib/catalogCache";
import { getApiErrorMessage } from "@/lib/apiErrors";
import { TablePagination } from "@/components/ui/table-pagination";
import { Pencil, Trash2 } from "lucide-react";

const PER_PAGE_OPTIONS = ["10", "15", "25", "50", "100"];

const emptyEditForm = {
    name: "",
    code: "",
    legal_name: "",
    tax_id: "",
    contact_name: "",
    contact_email: "",
    contact_phone: "",
    notes: "",
    is_active: true,
};

export default function Clientes() {
    const [list, setList] = useState([]);
    const [name, setName] = useState("");
    const [code, setCode] = useState("");
    const [taxId, setTaxId] = useState("");
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [editingCliente, setEditingCliente] = useState(null);
    const [editForm, setEditForm] = useState(emptyEditForm);
    const [savingEdit, setSavingEdit] = useState(false);
    const [perPage, setPerPage] = useState(() => localStorage.getItem("clientes.perPage") || "10");
    const [page, setPage] = useState(1);

    const load = async () => {
        setLoading(true);
        try {
            const { data } = await axios.get("/api/clientes");
            setList(data);
        } catch (err) {
            notify.error(getApiErrorMessage(err, "No se pudieron cargar los clientes"));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    const create = async (e) => {
        e.preventDefault();
        if (name.trim().length < 2) return;
        setSaving(true);
        try {
            const { data } = await axios.post("/api/clientes", {
                name: name.trim(),
                code: code.trim() || null,
                tax_id: taxId.trim() || null,
            });
            setList((prev) => [data, ...prev]);
            clearCatalogCache();
            setName("");
            setCode("");
            setTaxId("");
            notify.success("Cliente creado");
        } catch (err) {
            notify.error(getApiErrorMessage(err, "No se pudo crear"));
        } finally {
            setSaving(false);
        }
    };

    const toggle = async (cliente) => {
        try {
            const { data } = await axios.put(`/api/clientes/${cliente.id}`, {
                ...cliente,
                is_active: !cliente.is_active,
            });
            setList((prev) => prev.map((c) => (c.id === data.id ? data : c)));
            clearCatalogCache();
        } catch (err) {
            notify.error(getApiErrorMessage(err, "No se pudo actualizar"));
        }
    };

    const openEdit = (cliente) => {
        setEditingCliente(cliente);
        setEditForm({
            name: cliente.name || "",
            code: cliente.code || "",
            legal_name: cliente.legal_name || "",
            tax_id: cliente.tax_id || "",
            contact_name: cliente.contact_name || "",
            contact_email: cliente.contact_email || "",
            contact_phone: cliente.contact_phone || "",
            notes: cliente.notes || "",
            is_active: Boolean(cliente.is_active),
        });
    };

    const saveEdit = async () => {
        if (!editingCliente || editForm.name.trim().length < 2) return;
        setSavingEdit(true);
        try {
            const { data } = await axios.put(`/api/clientes/${editingCliente.id}`, {
                name: editForm.name.trim(),
                code: editForm.code.trim() || null,
                legal_name: editForm.legal_name.trim() || null,
                tax_id: editForm.tax_id.trim() || null,
                contact_name: editForm.contact_name.trim() || null,
                contact_email: editForm.contact_email.trim() || null,
                contact_phone: editForm.contact_phone.trim() || null,
                notes: editForm.notes.trim() || null,
                is_active: editForm.is_active,
            });
            setList((prev) => prev.map((c) => (c.id === data.id ? data : c)));
            clearCatalogCache();
            setEditingCliente(null);
            notify.success("Cliente actualizado");
        } catch (err) {
            notify.error(getApiErrorMessage(err, "No se pudo actualizar el cliente"));
        } finally {
            setSavingEdit(false);
        }
    };

    const remove = async (cliente) => {
        const sedesCount = cliente.sedes_count ?? cliente.sedes?.length ?? 0;
        if (sedesCount > 0) {
            notify.error("No se puede eliminar: hay sedes asignadas a este cliente");
            return;
        }
        if (!window.confirm(`¿Eliminar el cliente "${cliente.name}"? Esta acción no se puede deshacer.`)) return;
        try {
            await axios.delete(`/api/clientes/${cliente.id}`);
            setList((prev) => prev.filter((c) => c.id !== cliente.id));
            clearCatalogCache();
            notify.success("Cliente eliminado");
        } catch (err) {
            notify.error(getApiErrorMessage(err, "No se pudo eliminar el cliente"));
        }
    };

    const total = list.length;
    const lastPage = Math.max(1, Math.ceil(total / Number(perPage)));
    const currentPage = Math.min(page, lastPage);
    const paginatedList = useMemo(
        () => list.slice((currentPage - 1) * Number(perPage), currentPage * Number(perPage)),
        [list, currentPage, perPage]
    );

    useEffect(() => {
        if (currentPage !== page) setPage(currentPage);
    }, [currentPage, page]);

    useEffect(() => {
        localStorage.setItem("clientes.perPage", perPage);
    }, [perPage]);

    const from = total === 0 ? 0 : (currentPage - 1) * Number(perPage) + 1;
    const to = Math.min(currentPage * Number(perPage), total);

    return (
        <div className="space-y-6">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold">Clientes</h1>
                    <p className="text-muted-foreground">Catálogo de clientes y datos de contacto.</p>
                </div>
                <form onSubmit={create} className="flex flex-wrap gap-2">
                    <Input
                        placeholder="Nombre"
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        className="w-48"
                    />
                    <Input
                        placeholder="Código"
                        value={code}
                        onChange={(e) => setCode(e.target.value)}
                        className="w-28"
                    />
                    <Input
                        placeholder="RFC / ID fiscal"
                        value={taxId}
                        onChange={(e) => setTaxId(e.target.value)}
                        className="w-36"
                    />
                    <Button type="submit" disabled={saving || name.trim().length < 2}>
                        {saving ? "Guardando" : "Agregar"}
                    </Button>
                </form>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Listado</CardTitle>
                </CardHeader>
                <CardContent>
                    <TableWrapper>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nombre</TableHead>
                                    <TableHead>Código</TableHead>
                                    <TableHead>RFC</TableHead>
                                    <TableHead>Sedes</TableHead>
                                    <TableHead>Contacto</TableHead>
                                    <TableHead className="text-right">Activo</TableHead>
                                    <TableHead className="w-[80px] text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {loading ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center">
                                            Cargando...
                                        </TableCell>
                                    </TableRow>
                                ) : list.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center text-muted-foreground">
                                            Sin registros
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    paginatedList.map((cliente) => (
                                        <TableRow key={cliente.id}>
                                            <TableCell>
                                                <div className="font-medium">{cliente.name}</div>
                                                {cliente.legal_name ? (
                                                    <div className="text-xs text-muted-foreground">{cliente.legal_name}</div>
                                                ) : null}
                                            </TableCell>
                                            <TableCell>{cliente.code || "-"}</TableCell>
                                            <TableCell>{cliente.tax_id || "-"}</TableCell>
                                            <TableCell>{cliente.sedes_count ?? cliente.sedes?.length ?? 0}</TableCell>
                                            <TableCell>
                                                {cliente.contact_name ? (
                                                    <div>{cliente.contact_name}</div>
                                                ) : null}
                                                {cliente.contact_email ? (
                                                    <div className="text-xs text-muted-foreground">{cliente.contact_email}</div>
                                                ) : null}
                                                {!cliente.contact_name && !cliente.contact_email ? "-" : null}
                                            </TableCell>
                                            <TableCell className="text-right align-middle">
                                                <span className="inline-flex items-center justify-center min-h-[44px] min-w-[44px] md:min-h-0 md:min-w-0">
                                                    <Switch
                                                        checked={cliente.is_active}
                                                        onCheckedChange={() => toggle(cliente)}
                                                    />
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="h-11 w-11 p-0 md:h-8 md:w-auto md:gap-1 md:px-2"
                                                        onClick={() => openEdit(cliente)}
                                                        title="Editar"
                                                    >
                                                        <Pencil className="h-4 w-4 md:h-3.5 md:w-3.5" />
                                                        <span className="hidden md:inline">Editar</span>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="h-11 w-11 p-0 text-destructive hover:text-destructive hover:bg-destructive/10 md:h-8 md:w-auto md:gap-1 md:px-2"
                                                        onClick={() => remove(cliente)}
                                                        title="Eliminar cliente"
                                                    >
                                                        <Trash2 className="h-4 w-4 md:h-3.5 md:w-3.5" />
                                                        <span className="hidden md:inline">Eliminar</span>
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </TableWrapper>

                    {!loading && list.length > 0 && (
                        <TablePagination
                            total={total}
                            from={from}
                            to={to}
                            currentPage={currentPage}
                            lastPage={lastPage}
                            perPage={perPage}
                            perPageOptions={PER_PAGE_OPTIONS}
                            onPerPageChange={setPerPage}
                            onPageChange={(p) => setPage(p)}
                        />
                    )}
                </CardContent>
            </Card>

            <Dialog
                open={!!editingCliente}
                onOpenChange={(open) => {
                    if (!savingEdit) setEditingCliente(open ? editingCliente : null);
                }}
            >
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Editar cliente</DialogTitle>
                    </DialogHeader>
                    <div className="grid gap-4 py-4 max-h-[70vh] overflow-y-auto pr-1">
                        <div className="grid gap-2">
                            <Label htmlFor="edit-name">Nombre</Label>
                            <Input
                                id="edit-name"
                                value={editForm.name}
                                onChange={(e) => setEditForm((prev) => ({ ...prev, name: e.target.value }))}
                                placeholder="Nombre comercial"
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-code">Código</Label>
                                <Input
                                    id="edit-code"
                                    value={editForm.code}
                                    onChange={(e) => setEditForm((prev) => ({ ...prev, code: e.target.value }))}
                                    placeholder="Opcional"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-tax">RFC / ID fiscal</Label>
                                <Input
                                    id="edit-tax"
                                    value={editForm.tax_id}
                                    onChange={(e) => setEditForm((prev) => ({ ...prev, tax_id: e.target.value }))}
                                    placeholder="Opcional"
                                />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit-legal">Razón social</Label>
                            <Input
                                id="edit-legal"
                                value={editForm.legal_name}
                                onChange={(e) => setEditForm((prev) => ({ ...prev, legal_name: e.target.value }))}
                                placeholder="Opcional"
                            />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit-contact">Contacto</Label>
                            <Input
                                id="edit-contact"
                                value={editForm.contact_name}
                                onChange={(e) => setEditForm((prev) => ({ ...prev, contact_name: e.target.value }))}
                                placeholder="Nombre del contacto"
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="edit-email">Correo</Label>
                                <Input
                                    id="edit-email"
                                    type="email"
                                    value={editForm.contact_email}
                                    onChange={(e) => setEditForm((prev) => ({ ...prev, contact_email: e.target.value }))}
                                    placeholder="correo@ejemplo.com"
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="edit-phone">Teléfono</Label>
                                <Input
                                    id="edit-phone"
                                    value={editForm.contact_phone}
                                    onChange={(e) => setEditForm((prev) => ({ ...prev, contact_phone: e.target.value }))}
                                    placeholder="Opcional"
                                />
                            </div>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="edit-notes">Notas</Label>
                            <Textarea
                                id="edit-notes"
                                value={editForm.notes}
                                onChange={(e) => setEditForm((prev) => ({ ...prev, notes: e.target.value }))}
                                placeholder="Información adicional"
                                rows={3}
                            />
                        </div>
                        <div className="flex items-center gap-2">
                            <Switch
                                id="edit-active"
                                checked={editForm.is_active}
                                onCheckedChange={(v) => setEditForm((prev) => ({ ...prev, is_active: !!v }))}
                            />
                            <Label htmlFor="edit-active" className="cursor-pointer">
                                Activo
                            </Label>
                        </div>
                        {editingCliente?.sedes?.length > 0 ? (
                            <div className="grid gap-2">
                                <Label>Sedes asignadas</Label>
                                <ul className="rounded-md border border-border/60 divide-y divide-border/60 text-sm max-h-40 overflow-y-auto">
                                    {editingCliente.sedes.map((sede) => (
                                        <li key={sede.id} className="px-3 py-2 space-y-0.5">
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="font-medium">{sede.name}</span>
                                                <span className="text-xs text-muted-foreground shrink-0">
                                                    {sede.type === "physical" ? "Física" : "Virtual"}
                                                    {!sede.is_active ? " · Inactiva" : ""}
                                                </span>
                                            </div>
                                            {sede.city || sede.address ? (
                                                <p className="text-xs text-muted-foreground">
                                                    {[sede.address, sede.city].filter(Boolean).join(" · ")}
                                                </p>
                                            ) : null}
                                            {sede.contact_phone ? (
                                                <p className="text-xs text-muted-foreground">{sede.contact_phone}</p>
                                            ) : null}
                                        </li>
                                    ))}
                                </ul>
                                <p className="text-xs text-muted-foreground">
                                    Asigna o cambia sedes desde el catálogo de Sedes.
                                </p>
                            </div>
                        ) : null}
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setEditingCliente(null)} disabled={savingEdit}>
                            Cancelar
                        </Button>
                        <Button onClick={saveEdit} disabled={savingEdit || editForm.name.trim().length < 2}>
                            {savingEdit ? "Guardando…" : "Guardar"}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
