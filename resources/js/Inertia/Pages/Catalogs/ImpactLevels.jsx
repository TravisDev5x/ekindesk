import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import CatalogPage from "@/Inertia/components/CatalogPage";
import CatalogDialog from "@/Inertia/components/CatalogDialog";
import useCatalog from "@/Inertia/hooks/useCatalog";
import { Badge } from "@/components/ui/badge";

export default function ImpactLevels() {
    const { impactLevels } = usePage().props;

    const catalog = useCatalog("/api/impact-levels", () =>
        router.reload({ only: ["impactLevels"] })
    );

    const columns = [
        { key: "name", label: "Nombre" },
        {
            key: "weight",
            label: "Peso",
            width: "w-[100px]",
            render: (row) => <Badge variant="outline">{row.weight}</Badge>,
        },
        {
            key: "is_active",
            label: "Estado",
            width: "w-[160px]",
            activeLabel: "Activo",
            inactiveLabel: "Inactivo",
        },
    ];

    const fields = [
        {
            key: "name",
            label: "Nombre",
            type: "text",
            required: true,
            placeholder: "Ej. Alto",
        },
        {
            key: "weight",
            label: "Peso",
            type: "number",
            required: true,
            min: 1,
            max: 10,
            defaultValue: 1,
            help: "Peso para cálculo de prioridad (1–10)",
        },
        {
            key: "is_active",
            label: "Nivel activo",
            type: "switch",
            defaultValue: true,
        },
    ];

    return (
        <>
            <CatalogPage
                title="Niveles de impacto"
                description="Catálogo para la matriz Impacto × Urgencia"
                columns={columns}
                data={impactLevels ?? []}
                onAdd={catalog.openCreate}
                onEdit={catalog.openEdit}
                onDelete={catalog.handleDelete}
                onToggle={catalog.handleToggle}
                loading={catalog.loading}
                addLabel="Nuevo nivel"
                emptyMessage="No hay niveles de impacto registrados"
            />

            <CatalogDialog
                key={catalog.editTarget?.id ?? "create"}
                open={catalog.dialogOpen}
                onClose={catalog.closeDialog}
                title={catalog.editTarget ? "Editar nivel de impacto" : "Nuevo nivel de impacto"}
                fields={fields}
                initialValues={catalog.editTarget ?? { weight: 1, is_active: true }}
                onSubmit={catalog.handleSubmit}
                loading={catalog.loading}
                errors={catalog.dialogErrors}
                submitLabel={catalog.editTarget ? "Actualizar" : "Crear"}
            />
        </>
    );
}

ImpactLevels.layout = (page) => <AuthenticatedLayout title="Niveles de impacto">{page}</AuthenticatedLayout>;
