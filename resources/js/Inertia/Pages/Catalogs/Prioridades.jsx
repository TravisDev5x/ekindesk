import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import CatalogPage from "@/Inertia/components/CatalogPage";
import CatalogDialog from "@/Inertia/components/CatalogDialog";
import useCatalog from "@/Inertia/hooks/useCatalog";
import { Badge } from "@/components/ui/badge";

export default function Prioridades() {
    const { priorities } = usePage().props;

    const catalog = useCatalog("/api/priorities", () =>
        router.reload({ only: ["priorities"] })
    );

    const columns = [
        { key: "name", label: "Nombre" },
        {
            key: "level",
            label: "Nivel",
            width: "w-[100px]",
            render: (row) => <Badge variant="outline">{row.level}</Badge>,
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
            placeholder: "Ej. Crítica",
        },
        {
            key: "level",
            label: "Nivel",
            type: "number",
            required: true,
            min: 1,
            max: 10,
            defaultValue: 1,
            help: "Número del 1 al 10 (1=menor, 10=mayor)",
        },
        {
            key: "is_active",
            label: "Prioridad activa",
            type: "switch",
            defaultValue: true,
        },
    ];

    return (
        <>
            <CatalogPage
                title="Prioridades"
                description="Base para SLAs y matriz de prioridades"
                columns={columns}
                data={priorities ?? []}
                onAdd={catalog.openCreate}
                onEdit={catalog.openEdit}
                onDelete={catalog.handleDelete}
                onToggle={catalog.handleToggle}
                loading={catalog.loading}
                addLabel="Nueva prioridad"
                emptyMessage="No hay prioridades registradas"
            />

            <CatalogDialog
                key={catalog.editTarget?.id ?? "create"}
                open={catalog.dialogOpen}
                onClose={catalog.closeDialog}
                title={catalog.editTarget ? "Editar prioridad" : "Nueva prioridad"}
                fields={fields}
                initialValues={catalog.editTarget ?? { level: 1, is_active: true }}
                onSubmit={catalog.handleSubmit}
                loading={catalog.loading}
                errors={catalog.dialogErrors}
                submitLabel={catalog.editTarget ? "Actualizar" : "Crear"}
            />
        </>
    );
}

Prioridades.layout = (page) => <AuthenticatedLayout title="Prioridades">{page}</AuthenticatedLayout>;
