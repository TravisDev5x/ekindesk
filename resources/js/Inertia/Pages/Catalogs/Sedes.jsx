import { useMemo } from "react";
import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import CatalogPage from "@/Inertia/components/CatalogPage";
import CatalogDialog from "@/Inertia/components/CatalogDialog";
import useCatalog from "@/Inertia/hooks/useCatalog";
import { Badge } from "@/components/ui/badge";

const NO_CLIENT = "none";

const TYPE_LABELS = {
    physical: "Física",
    virtual: "Virtual",
};

function prepareSedePayload(formData) {
    return {
        name: String(formData.name ?? "").trim(),
        code: formData.code?.trim() || null,
        type: formData.type || "physical",
        client_id:
            !formData.client_id || formData.client_id === NO_CLIENT
                ? null
                : Number(formData.client_id),
        address: formData.address?.trim() || null,
        city: formData.city?.trim() || null,
        contact_name: formData.contact_name?.trim() || null,
        contact_phone: formData.contact_phone?.trim() || null,
        contact_email: formData.contact_email?.trim() || null,
        is_active: Boolean(formData.is_active),
    };
}

function sedeToForm(sede) {
    if (!sede) {
        return {
            type: "physical",
            client_id: NO_CLIENT,
            is_active: true,
        };
    }
    return {
        name: sede.name ?? "",
        code: sede.code ?? "",
        type: sede.type ?? "physical",
        client_id: sede.client_id ? String(sede.client_id) : NO_CLIENT,
        address: sede.address ?? "",
        city: sede.city ?? "",
        contact_name: sede.contact_name ?? "",
        contact_phone: sede.contact_phone ?? "",
        contact_email: sede.contact_email ?? "",
        is_active: Boolean(sede.is_active),
    };
}

export default function Sedes() {
    const { sedes, clientes } = usePage().props;

    const catalog = useCatalog("/api/sedes", () => router.reload({ only: ["sedes"] }));

    const clientOptions = useMemo(
        () => [
            { value: NO_CLIENT, label: "Sin cliente" },
            ...(clientes ?? []).map((c) => ({ value: String(c.id), label: c.name })),
        ],
        [clientes]
    );

    const columns = [
        { key: "name", label: "Nombre" },
        {
            key: "cliente",
            label: "Cliente",
            render: (row) => row.cliente?.name ?? "—",
        },
        { key: "city", label: "Ciudad" },
        {
            key: "type",
            label: "Tipo",
            render: (row) => (
                <Badge variant="outline">{TYPE_LABELS[row.type] ?? row.type ?? "—"}</Badge>
            ),
        },
        {
            key: "is_active",
            label: "Estado",
            width: "w-[160px]",
            activeLabel: "Activa",
            inactiveLabel: "Inactiva",
        },
    ];

    const fields = [
        {
            key: "name",
            label: "Nombre de la sede",
            type: "text",
            required: true,
            placeholder: "Ej. Central Polanco",
        },
        {
            key: "code",
            label: "Código",
            type: "text",
            placeholder: "Opcional",
        },
        {
            key: "type",
            label: "Tipo",
            type: "select",
            required: true,
            defaultValue: "physical",
            options: [
                { value: "physical", label: "Física" },
                { value: "virtual", label: "Virtual" },
            ],
        },
        {
            key: "client_id",
            label: "Cliente",
            type: "select",
            options: clientOptions,
        },
        {
            key: "address",
            label: "Dirección",
            type: "textarea",
            placeholder: "Calle, número, colonia…",
        },
        {
            key: "city",
            label: "Ciudad",
            type: "text",
            placeholder: "Ej. Ciudad de México",
        },
        {
            key: "contact_name",
            label: "Contacto",
            type: "text",
        },
        {
            key: "contact_phone",
            label: "Teléfono contacto",
            type: "text",
        },
        {
            key: "contact_email",
            label: "Email contacto",
            type: "text",
        },
        {
            key: "is_active",
            label: "Sede activa",
            type: "switch",
            defaultValue: true,
        },
    ];

    const onSubmit = (formData) => catalog.handleSubmit(prepareSedePayload(formData));

    return (
        <>
            <CatalogPage
                title="Sedes"
                description="Sitios físicos o virtuales asociados a clientes"
                columns={columns}
                data={sedes ?? []}
                onAdd={catalog.openCreate}
                onEdit={catalog.openEdit}
                onDelete={catalog.handleDelete}
                onToggle={catalog.handleToggle}
                loading={catalog.loading}
                addLabel="Nueva sede"
                emptyMessage="No hay sedes registradas"
            />

            <CatalogDialog
                key={catalog.editTarget?.id ?? "create"}
                open={catalog.dialogOpen}
                onClose={catalog.closeDialog}
                title={catalog.editTarget ? "Editar sede" : "Nueva sede"}
                fields={fields}
                initialValues={sedeToForm(catalog.editTarget)}
                onSubmit={onSubmit}
                loading={catalog.loading}
                errors={catalog.dialogErrors}
                submitLabel={catalog.editTarget ? "Actualizar" : "Crear"}
            />
        </>
    );
}

Sedes.layout = (page) => <AuthenticatedLayout title="Sedes">{page}</AuthenticatedLayout>;
