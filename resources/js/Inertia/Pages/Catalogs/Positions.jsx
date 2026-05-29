import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import CatalogPage from "@/Inertia/components/CatalogPage";
import CatalogDialog from "@/Inertia/components/CatalogDialog";
import useCatalog from "@/Inertia/hooks/useCatalog";
import { Badge } from "@/components/ui/badge";

export default function Positions() {
    const { positions } = usePage().props;

    const catalog = useCatalog("/api/positions", () =>
        router.reload({ only: ["positions"] })
    );

    const columns = [
        { key: "name", label: "Nombre" },
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
            placeholder: "Ej. Analista de soporte",
            help: "Mínimo 3 caracteres.",
        },
        {
            key: "is_active",
            label: "Puesto activo",
            type: "switch",
            defaultValue: true,
        },
    ];

    return (
        <>
            <CatalogPage
                title="Puestos"
                description="Catálogo maestro de puestos"
                columns={columns}
                data={positions ?? []}
                onAdd={catalog.openCreate}
                onEdit={catalog.openEdit}
                onDelete={catalog.handleDelete}
                onToggle={catalog.handleToggle}
                loading={catalog.loading}
                addLabel="Nuevo puesto"
                emptyMessage="No hay puestos registrados"
            />

            <CatalogDialog
                key={catalog.editTarget?.id ?? "create"}
                open={catalog.dialogOpen}
                onClose={catalog.closeDialog}
                title={catalog.editTarget ? "Editar puesto" : "Nuevo puesto"}
                fields={fields}
                initialValues={catalog.editTarget ?? { is_active: true }}
                onSubmit={catalog.handleSubmit}
                loading={catalog.loading}
                errors={catalog.dialogErrors}
                submitLabel={catalog.editTarget ? "Actualizar" : "Crear"}
            />
        </>
    );
}

Positions.layout = (page) => <AuthenticatedLayout title="Puestos">{page}</AuthenticatedLayout>;
