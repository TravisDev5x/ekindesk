import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import CatalogPage from "@/Inertia/components/CatalogPage";
import CatalogDialog from "@/Inertia/components/CatalogDialog";
import useCatalog from "@/Inertia/hooks/useCatalog";

export default function Types() {
    const { incidentTypes } = usePage().props;

    const catalog = useCatalog("/api/incident-types", () => router.reload({ only: ["incidentTypes"] }));

    const columns = [
        { key: "id", label: "ID", width: "w-[80px]" },
        { key: "name", label: "Nombre" },
        { key: "code", label: "Código" },
        {
            key: "is_active",
            label: "Estado",
            width: "w-[160px]",
            activeLabel: "Activo",
            inactiveLabel: "Inactivo",
        },
        {
            key: "created_at",
            label: "Creado",
            width: "w-[180px]",
            render: (row) =>
                row.created_at
                    ? new Date(row.created_at).toLocaleDateString("es-ES")
                    : "—",
        },
    ];

    const fields = [
        {
            key: "name",
            label: "Nombre",
            type: "text",
            required: true,
            placeholder: "Ej. Hardware, Software, Red",
        },
        {
            key: "code",
            label: "Código",
            type: "text",
            required: true,
            placeholder: "Ej. HW, SW, NET",
        },
    ];

    return (
        <>
            <CatalogPage
                title="Tipos de incidencia"
                description="Clasificación por tipo de evento."
                columns={columns}
                data={incidentTypes ?? []}
                onAdd={catalog.openCreate}
                onEdit={catalog.openEdit}
                onDelete={catalog.handleDelete}
                onToggle={catalog.handleToggle}
                loading={catalog.loading}
                addLabel="Nuevo tipo"
                emptyMessage="No hay tipos de incidencia registrados."
            />

            <CatalogDialog
                key={catalog.editTarget?.id ?? "create"}
                open={catalog.dialogOpen}
                onClose={catalog.closeDialog}
                title={catalog.editTarget ? "Editar tipo de incidencia" : "Nuevo tipo de incidencia"}
                fields={fields}
                initialValues={catalog.editTarget ?? {}}
                onSubmit={catalog.handleSubmit}
                loading={catalog.loading}
                errors={catalog.dialogErrors}
                submitLabel={catalog.editTarget ? "Actualizar" : "Crear"}
            />
        </>
    );
}

Types.layout = (page) => (
    <AuthenticatedLayout title="Tipos de incidencia">{page}</AuthenticatedLayout>
);
