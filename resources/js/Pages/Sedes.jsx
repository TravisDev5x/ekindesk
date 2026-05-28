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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { clearCatalogCache } from "@/lib/catalogCache";
import { getApiErrorMessage } from "@/lib/apiErrors";
import { TablePagination } from "@/components/ui/table-pagination";
import { Pencil, Trash2, Plus } from "lucide-react";

const PER_PAGE_OPTIONS = ["10", "15", "25", "50", "100"];
const NO_CLIENT = "none";

const emptyForm = {
    name: "",
    code: "",
    type: "physical",
    client_id: NO_CLIENT,
    address: "",
    city: "",
    contact_name: "",
    contact_phone: "",
    contact_email: "",
    is_active: true,
};

function payloadFromSede(sede, overrides = {}) {
    return {
        name: sede.name,
        code: sede.code || null,
        type: sede.type,
        client_id: sede.client_id ?? null,
        address: sede.address?.trim() || null,
        city: sede.city?.trim() || null,
        contact_name: sede.contact_name?.trim() || null,
        contact_phone: sede.contact_phone?.trim() || null,
        contact_email: sede.contact_email?.trim() || null,
        is_active: sede.is_active,
        ...overrides,
    };
}

function payloadFromForm(form) {
    return {
        name: form.name.trim(),
        code: form.code.trim() || null,
        type: form.type,
        client_id: form.client_id === NO_CLIENT ? null : Number(form.client_id),
        address: form.address.trim() || null,
        city: form.city.trim() || null,
        contact_name: form.contact_name.trim() || null,
        contact_phone: form.contact_phone.trim() || null,
        contact_email: form.contact_email.trim() || null,
        is_active: form.is_active,
    };
}

function sedeToForm(sede) {
    return {
        name: sede.name || "",
        code: sede.code || "",
        type: sede.type || "physical",
        client_id: sede.client_id ? String(sede.client_id) : NO_CLIENT,
        address: sede.address || "",
        city: sede.city || "",
        contact_name: sede.contact_name || "",
        contact_phone: sede.contact_phone || "",
        contact_email: sede.contact_email || "",
        is_active: Boolean(sede.is_active),
    };
}

function truncate(text, max = 48) {
    if (!text) return "";
    return text.length > max ? `${text.slice(0, max)}…` : text;
}

function SedeFormFields({ form, setForm, clients, isEdit }) {
    return (
        <div className="grid gap-4 py-4 max-h-[70vh] overflow-y-auto pr-1">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="sede-name">Nombre de la sede</Label>
                    <Input
                        id="sede-name"
                        value={form.name}
                        onChange={(e) => setForm((prev) => ({ ...prev, name: e.target.value }))}
                        placeholder="Ej. CentralW Polanco"
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="sede-code">Código</Label>
                    <Input
                        id="sede-code"
                        value={form.code}
                        onChange={(e) => setForm((prev) => ({ ...prev, code: e.target.value }))}
                        placeholder="Opcional"
                    />
                </div>
                <div className="grid gap-2">
                    <Label>Tipo</Label>
                    <Select value={form.type} onValueChange={(v) => setForm((prev) => ({ ...prev, type: v }))}>
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="physical">Física</SelectItem>
                            <SelectItem value="virtual">Virtual</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div className="grid gap-2 sm:col-span-2">
                    <Label>Cliente</Label>
                    <Select
                        value={form.client_id}
                        onValueChange={(v) => setForm((prev) => ({ ...prev, client_id: v }))}
                    >
                        <SelectTrigger><SelectValue placeholder="Cliente" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value={NO_CLIENT}>Sin cliente</SelectItem>
                            {clients.map((c) => (
                                <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div className="rounded-lg border border-border/60 bg-muted/20 p-4 space-y-4">
                <p className="text-sm font-medium">Ubicación y contacto</p>
                <div className="grid gap-2">
                    <Label htmlFor="sede-address">Dirección</Label>
                    <Textarea
                        id="sede-address"
                        value={form.address}
                        onChange={(e) => setForm((prev) => ({ ...prev, address: e.target.value }))}
                        placeholder="Calle, número, colonia, referencias"
                        rows={2}
                    />
                </div>
                <div className="grid gap-2">
                    <Label htmlFor="sede-city">Ciudad</Label>
                    <Input
                        id="sede-city"
                        value={form.city}
                        onChange={(e) => setForm((prev) => ({ ...prev, city: e.target.value }))}
                        placeholder="Ej. Ciudad de México"
                    />
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="sede-contact-name">Contacto en sitio</Label>
                        <Input
                            id="sede-contact-name"
                            value={form.contact_name}
                            onChange={(e) => setForm((prev) => ({ ...prev, contact_name: e.target.value }))}
                            placeholder="Nombre del responsable"
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="sede-contact-phone">Teléfono</Label>
                        <Input
                            id="sede-contact-phone"
                            value={form.contact_phone}
                            onChange={(e) => setForm((prev) => ({ ...prev, contact_phone: e.target.value }))}
                            placeholder="Ej. 55 1234 5678"
                        />
                    </div>
                    <div className="grid gap-2 sm:col-span-2">
                        <Label htmlFor="sede-contact-email">Correo de contacto</Label>
                        <Input
                            id="sede-contact-email"
                            type="email"
                            value={form.contact_email}
                            onChange={(e) => setForm((prev) => ({ ...prev, contact_email: e.target.value }))}
                            placeholder="opcional@ejemplo.com"
                        />
                    </div>
                </div>
            </div>

            {isEdit ? (
                <div className="flex items-center gap-2">
                    <Switch
                        id="sede-active"
                        checked={form.is_active}
                        onCheckedChange={(v) => setForm((prev) => ({ ...prev, is_active: !!v }))}
                    />
                    <Label htmlFor="sede-active" className="cursor-pointer">Activa</Label>
                </div>
            ) : null}
        </div>
    );
}

export default function Sedes() {
    const [list, setList] = useState([]);
    const [clients, setClients] = useState([]);
    const [loading, setLoading] = useState(true);
    const [formOpen, setFormOpen] = useState(false);
    const [form, setForm] = useState(emptyForm);
    const [editingSede, setEditingSede] = useState(null);
    const [saving, setSaving] = useState(false);
    const [perPage, setPerPage] = useState(() => localStorage.getItem("sedes.perPage") || "10");
    const [page, setPage] = useState(1);

    const load = async () => {
        setLoading(true);
        try {
            const [{ data: sedesData }, { data: clientsData }] = await Promise.all([
                axios.get("/api/sedes"),
                axios.get("/api/clientes"),
            ]);
            setList(sedesData);
            setClients(clientsData.filter((c) => c.is_active));
        } catch (err) {
            notify.error(getApiErrorMessage(err, "No se pudieron cargar las sedes"));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, []);

    const openCreate = () => {
        setEditingSede(null);
        setForm(emptyForm);
        setFormOpen(true);
    };

    const openEdit = (sede) => {
        setEditingSede(sede);
        setForm(sedeToForm(sede));
        setFormOpen(true);
    };

    const closeForm = () => {
        if (!saving) {
            setFormOpen(false);
            setEditingSede(null);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (form.name.trim().length < 2) return;
        setSaving(true);
        try {
            const body = payloadFromForm(form);
            if (editingSede) {
                const { data } = await axios.put(`/api/sedes/${editingSede.id}`, body);
                setList((prev) => prev.map((s) => (s.id === data.id ? data : s)));
                notify.success("Sede actualizada");
            } else {
                const { data } = await axios.post("/api/sedes", body);
                setList((prev) => [data, ...prev]);
                notify.success("Sede creada");
            }
            clearCatalogCache();
            setFormOpen(false);
            setEditingSede(null);
            setForm(emptyForm);
        } catch (err) {
            notify.error(getApiErrorMessage(err, editingSede ? "No se pudo actualizar la sede" : "No se pudo crear"));
        } finally {
            setSaving(false);
        }
    };

    const toggle = async (sede) => {
        try {
            const { data } = await axios.put(`/api/sedes/${sede.id}`, payloadFromSede(sede, { is_active: !sede.is_active }));
            setList((prev) => prev.map((s) => (s.id === data.id ? data : s)));
            clearCatalogCache();
        } catch (err) {
            notify.error(getApiErrorMessage(err, "No se pudo actualizar"));
        }
    };

    const remove = async (sede) => {
        if (sede.code === "REMOTO") {
            notify.error("La sede Remoto no puede eliminarse");
            return;
        }
        if (!window.confirm(`¿Eliminar la sede "${sede.name}"? Esta acción no se puede deshacer.`)) return;
        try {
            await axios.delete(`/api/sedes/${sede.id}`);
            setList((prev) => prev.filter((s) => s.id !== sede.id));
            clearCatalogCache();
            notify.success("Sede eliminada");
        } catch (err) {
            notify.error(getApiErrorMessage(err, "No se pudo eliminar la sede"));
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
        localStorage.setItem("sedes.perPage", perPage);
    }, [perPage]);

    const from = total === 0 ? 0 : (currentPage - 1) * Number(perPage) + 1;
    const to = Math.min(currentPage * Number(perPage), total);

    return (
        <div className="space-y-6">
            <div className="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-semibold">Sedes</h1>
                    <p className="text-muted-foreground">
                        Sedes por cliente con dirección y datos de contacto en sitio.
                    </p>
                </div>
                <Button type="button" onClick={openCreate} className="shrink-0">
                    <Plus className="h-4 w-4 mr-2" />
                    Nueva sede
                </Button>
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
                                    <TableHead>Sede</TableHead>
                                    <TableHead>Cliente</TableHead>
                                    <TableHead>Dirección</TableHead>
                                    <TableHead>Contacto</TableHead>
                                    <TableHead>Tipo</TableHead>
                                    <TableHead className="text-right">Activa</TableHead>
                                    <TableHead className="w-[80px] text-right">Acciones</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {loading ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center">Cargando...</TableCell>
                                    </TableRow>
                                ) : list.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={7} className="text-center text-muted-foreground">
                                            Sin registros
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    paginatedList.map((sede) => (
                                        <TableRow key={sede.id}>
                                            <TableCell>
                                                <div className="font-medium">{sede.name}</div>
                                                {sede.code ? (
                                                    <div className="text-xs text-muted-foreground">{sede.code}</div>
                                                ) : null}
                                            </TableCell>
                                            <TableCell>{sede.cliente?.name || "-"}</TableCell>
                                            <TableCell className="max-w-[200px]">
                                                {sede.address || sede.city ? (
                                                    <>
                                                        <div className="text-sm">{truncate(sede.address) || "-"}</div>
                                                        {sede.city ? (
                                                            <div className="text-xs text-muted-foreground">{sede.city}</div>
                                                        ) : null}
                                                    </>
                                                ) : (
                                                    <span className="text-muted-foreground">-</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {sede.contact_name || sede.contact_phone ? (
                                                    <>
                                                        {sede.contact_name ? <div>{sede.contact_name}</div> : null}
                                                        {sede.contact_phone ? (
                                                            <div className="text-xs text-muted-foreground">{sede.contact_phone}</div>
                                                        ) : null}
                                                    </>
                                                ) : (
                                                    "-"
                                                )}
                                            </TableCell>
                                            <TableCell>{sede.type === "physical" ? "Física" : "Virtual"}</TableCell>
                                            <TableCell className="text-right align-middle">
                                                <span className="inline-flex items-center justify-center min-h-[44px] min-w-[44px] md:min-h-0 md:min-w-0">
                                                    <Switch checked={sede.is_active} onCheckedChange={() => toggle(sede)} />
                                                </span>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex items-center justify-end gap-1">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="h-11 w-11 p-0 md:h-8 md:w-auto md:gap-1 md:px-2"
                                                        onClick={() => openEdit(sede)}
                                                        title="Editar"
                                                    >
                                                        <Pencil className="h-4 w-4 md:h-3.5 md:w-3.5" />
                                                        <span className="hidden md:inline">Editar</span>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="h-11 w-11 p-0 text-destructive hover:text-destructive hover:bg-destructive/10 md:h-8 md:w-auto md:gap-1 md:px-2"
                                                        onClick={() => remove(sede)}
                                                        disabled={sede.code === "REMOTO"}
                                                        title={sede.code === "REMOTO" ? "La sede Remoto no puede eliminarse" : "Eliminar sede"}
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

            <Dialog open={formOpen} onOpenChange={(open) => { if (!open) closeForm(); else setFormOpen(true); }}>
                <DialogContent className="sm:max-w-lg">
                    <form onSubmit={handleSubmit}>
                        <DialogHeader>
                            <DialogTitle>{editingSede ? "Editar sede" : "Nueva sede"}</DialogTitle>
                        </DialogHeader>
                        <SedeFormFields
                            form={form}
                            setForm={setForm}
                            clients={clients}
                            isEdit={!!editingSede}
                        />
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={closeForm} disabled={saving}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={saving || form.name.trim().length < 2}>
                                {saving ? "Guardando…" : editingSede ? "Guardar" : "Agregar sede"}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    );
}
