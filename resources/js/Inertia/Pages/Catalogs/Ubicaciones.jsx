import { useMemo } from "react";
import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import CatalogPage from "@/Inertia/components/CatalogPage";
import CatalogDialog from "@/Inertia/components/CatalogDialog";
import useCatalog from "@/Inertia/hooks/useCatalog";
import { Badge } from "@/components/ui/badge";

function prepareUbicacionPayload(formData) {
    return {
        sede_id: Number(formData.sede_id),
        name: String(formData.name ?? "").trim(),
        code: formData.code?.trim() || null,
        is_active: Boolean(formData.is_active),
    };
}

function ubicacionToForm(row) {
    if (!row) {
        return { is_active: true };
    }
    return {
        name: row.name ?? "",
        code: row.code ?? "",
        sede_id: row.sede_id ? String(row.sede_id) : "",
        is_active: Boolean(row.is_active),
    };
}

export default function Ubicaciones() {
    const { ubicaciones, sedes } = usePage().props;

    const catalog = useCatalog("/api/ubicaciones", () =>
        router.reload({ only: ["ubicaciones"] })
    );

    const sedeOptions = useMemo(
        () => (sedes ?? []).map((s) => ({ value: String(s.id), label: s.name })),
        [sedes]
    );

    const columns = [
        { key: "name", label: "Nombre" },
        {
            key: "sede",
            label: "Sede",
            render: (row) => row.sede?.name ?? "—",
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
            label: "Nombre",
            type: "text",
            required: true,
            placeholder: "Ej. Piso 3 — NOC",
        },
        {
            key: "code",
            label: "Código",
            type: "text",
            placeholder: "Opcional",
        },
        {
            key: "sede_id",
            label: "Sede",
            type: "select",
            required: true,
            options: sedeOptions,
        },
        {
            key: "is_active",
            label: "Ubicación activa",
            type: "switch",
            defaultValue: true,
        },
    ];

    const onSubmit = (formData) => catalog.handleSubmit(prepareUbicacionPayload(formData));

    return (
        <>
            <CatalogPage
                title="Ubicaciones"
                description="Ubicaciones dentro de cada sede"
                columns={columns}
                data={ubicaciones ?? []}
                onAdd={catalog.openCreate}
                onEdit={catalog.openEdit}
                onDelete={catalog.handleDelete}
                onToggle={catalog.handleToggle}
                loading={catalog.loading}
                addLabel="Nueva ubicación"
                emptyMessage="No hay ubicaciones registradas"
            />

            <CatalogDialog
                key={catalog.editTarget?.id ?? "create"}
                open={catalog.dialogOpen}
                onClose={catalog.closeDialog}
                title={catalog.editTarget ? "Editar ubicación" : "Nueva ubicación"}
                fields={fields}
                initialValues={ubicacionToForm(catalog.editTarget)}
                onSubmit={onSubmit}
                loading={catalog.loading}
                errors={catalog.dialogErrors}
                submitLabel={catalog.editTarget ? "Actualizar" : "Crear"}
            />
        </>
    );
}

Ubicaciones.layout = (page) => <AuthenticatedLayout title="Ubicaciones">{page}</AuthenticatedLayout>;
