import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import CatalogPage from "@/Inertia/components/CatalogPage";
import CatalogDialog from "@/Inertia/components/CatalogDialog";
import useCatalog from "@/Inertia/hooks/useCatalog";

export default function Severities() {
    const { incidentSeverities } = usePage().props;

    const catalog = useCatalog("/api/incident-severities", () => router.reload({ only: ["incidentSeverities"] }));

    const columns = [
        { key: "level", label: "Nivel", width: "w-[80px]" },
        { key: "name", label: "Nombre" },
        { key: "code", label: "Código" },
        {
            key: "is_active",
            label: "Estado",
            width: "w-[160px]",
            activeLabel: "Activa",
            inactiveLabel: "Inactiva",
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
            placeholder: "Ej. Crítica, Alta, Media, Baja",
        },
        {
            key: "code",
            label: "Código",
            type: "text",
            required: true,
            placeholder: "Ej. CRIT, HIGH, MED, LOW",
        },
        {
            key: "level",
            label: "Nivel",
            type: "number",
            min: 1,
            max: 10,
            defaultValue: 1,
            placeholder: "1–10",
        },
    ];

    return (
        <>
            <CatalogPage
                title="Severidades"
                description="Nivel de impacto de la incidencia."
                columns={columns}
                data={incidentSeverities ?? []}
                onAdd={catalog.openCreate}
                onEdit={catalog.openEdit}
                onDelete={catalog.handleDelete}
                onToggle={catalog.handleToggle}
                loading={catalog.loading}
                addLabel="Nueva severidad"
                emptyMessage="No hay severidades registradas."
            />

            <CatalogDialog
                key={catalog.editTarget?.id ?? "create"}
                open={catalog.dialogOpen}
                onClose={catalog.closeDialog}
                title={catalog.editTarget ? "Editar severidad" : "Nueva severidad"}
                fields={fields}
                initialValues={catalog.editTarget ?? { level: 1 }}
                onSubmit={catalog.handleSubmit}
                loading={catalog.loading}
                errors={catalog.dialogErrors}
                submitLabel={catalog.editTarget ? "Actualizar" : "Crear"}
            />
        </>
    );
}

Severities.layout = (page) => (
    <AuthenticatedLayout title="Severidades">{page}</AuthenticatedLayout>
);
