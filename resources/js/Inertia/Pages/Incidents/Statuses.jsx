import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import CatalogPage from "@/Inertia/components/CatalogPage";
import CatalogDialog from "@/Inertia/components/CatalogDialog";
import useCatalog from "@/Inertia/hooks/useCatalog";
import { Badge } from "@/components/ui/badge";

export default function Statuses() {
    const { incidentStatuses } = usePage().props;

    const catalog = useCatalog("/api/incident-statuses", () => router.reload({ only: ["incidentStatuses"] }));

    const columns = [
        { key: "name", label: "Nombre" },
        { key: "code", label: "Código" },
        {
            key: "is_final",
            label: "Final",
            width: "w-[100px]",
            render: (row) => (
                <Badge variant={row.is_final ? "default" : "secondary"}>
                    {row.is_final ? "Sí" : "No"}
                </Badge>
            ),
        },
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
            placeholder: "Ej. Abierto, En progreso, Cerrado",
        },
        {
            key: "code",
            label: "Código",
            type: "text",
            required: true,
            placeholder: "Ej. OPEN, IN_PROGRESS, CLOSED",
        },
        {
            key: "is_final",
            label: "Estado final",
            type: "switch",
            defaultValue: false,
            switchDescription: "Marca si este estado cierra el ciclo de vida.",
        },
    ];

    return (
        <>
            <CatalogPage
                title="Estados de incidencia"
                description="Ciclo de vida configurable."
                columns={columns}
                data={incidentStatuses ?? []}
                onAdd={catalog.openCreate}
                onEdit={catalog.openEdit}
                onDelete={catalog.handleDelete}
                onToggle={catalog.handleToggle}
                loading={catalog.loading}
                addLabel="Nuevo estado"
                emptyMessage="No hay estados registrados."
            />

            <CatalogDialog
                key={catalog.editTarget?.id ?? "create"}
                open={catalog.dialogOpen}
                onClose={catalog.closeDialog}
                title={catalog.editTarget ? "Editar estado de incidencia" : "Nuevo estado de incidencia"}
                fields={fields}
                initialValues={catalog.editTarget ?? { is_final: false }}
                onSubmit={catalog.handleSubmit}
                loading={catalog.loading}
                errors={catalog.dialogErrors}
                submitLabel={catalog.editTarget ? "Actualizar" : "Crear"}
            />
        </>
    );
}

Statuses.layout = (page) => (
    <AuthenticatedLayout title="Estados de incidencia">{page}</AuthenticatedLayout>
);
