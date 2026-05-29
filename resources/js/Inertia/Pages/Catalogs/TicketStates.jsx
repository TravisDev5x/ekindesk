import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import CatalogPage from "@/Inertia/components/CatalogPage";
import CatalogDialog from "@/Inertia/components/CatalogDialog";
import useCatalog from "@/Inertia/hooks/useCatalog";
import { Badge } from "@/components/ui/badge";

export default function TicketStates() {
    const { ticketStates } = usePage().props;

    const catalog = useCatalog("/api/ticket-states", () =>
        router.reload({ only: ["ticketStates"] })
    );

    const columns = [
        { key: "name", label: "Nombre" },
        { key: "code", label: "Código" },
        {
            key: "is_final",
            label: "Final",
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
    ];

    const fields = [
        {
            key: "name",
            label: "Nombre",
            type: "text",
            required: true,
            placeholder: "Ej. En progreso",
        },
        {
            key: "code",
            label: "Código",
            type: "text",
            required: true,
            placeholder: "Ej. en_progreso",
            help: "Identificador único en minúsculas.",
        },
        {
            key: "is_final",
            label: "Estado final",
            type: "switch",
            defaultValue: false,
        },
        {
            key: "is_active",
            label: "Estado activo",
            type: "switch",
            defaultValue: true,
        },
    ];

    return (
        <>
            <CatalogPage
                title="Estados de ticket"
                description="Ciclo de vida configurable para tickets (Resolbeb)"
                columns={columns}
                data={ticketStates ?? []}
                onAdd={catalog.openCreate}
                onEdit={catalog.openEdit}
                onDelete={catalog.handleDelete}
                onToggle={catalog.handleToggle}
                loading={catalog.loading}
                addLabel="Nuevo estado"
                emptyMessage="No hay estados registrados"
            />

            <CatalogDialog
                key={catalog.editTarget?.id ?? "create"}
                open={catalog.dialogOpen}
                onClose={catalog.closeDialog}
                title={catalog.editTarget ? "Editar estado" : "Nuevo estado"}
                fields={fields}
                initialValues={
                    catalog.editTarget ?? { is_final: false, is_active: true }
                }
                onSubmit={catalog.handleSubmit}
                loading={catalog.loading}
                errors={catalog.dialogErrors}
                submitLabel={catalog.editTarget ? "Actualizar" : "Crear"}
            />
        </>
    );
}

TicketStates.layout = (page) => <AuthenticatedLayout title="Estados de ticket">{page}</AuthenticatedLayout>;
