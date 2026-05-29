import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import CatalogPage from "@/Inertia/components/CatalogPage";
import CatalogDialog from "@/Inertia/components/CatalogDialog";
import useCatalog from "@/Inertia/hooks/useCatalog";
import { Badge } from "@/components/ui/badge";

export default function Campaigns() {
    const { campaigns } = usePage().props;

    const catalog = useCatalog("/api/campaigns", () =>
        router.reload({ only: ["campaigns"] })
    );

    const columns = [
        { key: "name", label: "Nombre" },
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
            placeholder: "Ej. Campaña Norte",
            help: "Mínimo 3 caracteres.",
        },
        {
            key: "is_active",
            label: "Campaña activa",
            type: "switch",
            defaultValue: true,
        },
    ];

    return (
        <>
            <CatalogPage
                title="Campañas"
                description="Catálogo maestro de campañas"
                columns={columns}
                data={campaigns ?? []}
                onAdd={catalog.openCreate}
                onEdit={catalog.openEdit}
                onDelete={catalog.handleDelete}
                onToggle={catalog.handleToggle}
                loading={catalog.loading}
                addLabel="Nueva campaña"
                emptyMessage="No hay campañas registradas"
            />

            <CatalogDialog
                key={catalog.editTarget?.id ?? "create"}
                open={catalog.dialogOpen}
                onClose={catalog.closeDialog}
                title={catalog.editTarget ? "Editar campaña" : "Nueva campaña"}
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

Campaigns.layout = (page) => <AuthenticatedLayout title="Campañas">{page}</AuthenticatedLayout>;
