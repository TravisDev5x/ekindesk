import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import CatalogPage from "@/Inertia/components/CatalogPage";
import CatalogDialog from "@/Inertia/components/CatalogDialog";
import useCatalog from "@/Inertia/hooks/useCatalog";
import { Badge } from "@/components/ui/badge";

export default function TicketMacros() {
    const { ticketMacros } = usePage().props;

    const catalog = useCatalog(
        "/api/ticket-macros",
        () => router.reload({ only: ["ticketMacros"] }),
        { lazyLoadOnEdit: true }
    );

    const columns = [
        { key: "name", label: "Nombre" },
        {
            key: "category",
            label: "Categoría",
            render: (row) =>
                row.category ? (
                    <Badge variant="secondary">{row.category}</Badge>
                ) : (
                    "—"
                ),
        },
        {
            key: "content",
            label: "Contenido",
            render: () => (
                <span className="text-sm text-muted-foreground italic">
                    Ver al editar
                </span>
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
            label: "Nombre",
            type: "text",
            required: true,
            placeholder: "Ej. Saludo inicial",
        },
        {
            key: "category",
            label: "Categoría",
            type: "text",
            placeholder: "Ej. Saludo, Cierre…",
        },
        {
            key: "content",
            label: "Contenido",
            type: "textarea",
            required: true,
            placeholder: "Texto de la macro…",
        },
        {
            key: "is_active",
            label: "Macro activa",
            type: "switch",
            defaultValue: true,
        },
    ];

    return (
        <>
            <CatalogPage
                title="Macros de ticket"
                description="Respuestas rápidas reutilizables en tickets"
                columns={columns}
                data={ticketMacros ?? []}
                onAdd={catalog.openCreate}
                onEdit={catalog.openEdit}
                onDelete={catalog.handleDelete}
                onToggle={catalog.handleToggle}
                loading={catalog.loading}
                addLabel="Nueva macro"
                emptyMessage="No hay macros registradas"
            />

            <CatalogDialog
                key={catalog.editTarget?.id ?? "create"}
                open={catalog.dialogOpen}
                onClose={catalog.closeDialog}
                title={catalog.editTarget ? "Editar macro" : "Nueva macro"}
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

TicketMacros.layout = (page) => <AuthenticatedLayout title="Macros de ticket">{page}</AuthenticatedLayout>;
