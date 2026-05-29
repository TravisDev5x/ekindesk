import { useMemo } from "react";
import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import CatalogPage from "@/Inertia/components/CatalogPage";
import CatalogDialog from "@/Inertia/components/CatalogDialog";
import useCatalog from "@/Inertia/hooks/useCatalog";
import { Badge } from "@/components/ui/badge";

export default function TicketTypes() {
    const { ticketTypes, areas } = usePage().props;

    const catalog = useCatalog("/api/ticket-types", () =>
        router.reload({ only: ["ticketTypes"] })
    );

    const areaOptions = useMemo(
        () => (areas ?? []).map((a) => ({ value: a.id, label: a.name })),
        [areas]
    );

    const columns = [
        { key: "name", label: "Nombre" },
        { key: "code", label: "Código" },
        {
            key: "areas",
            label: "Áreas",
            render: (row) =>
                row.areas?.length ? (
                    <div className="flex flex-wrap gap-1">
                        {row.areas.map((a) => (
                            <Badge key={a.id} variant="secondary" className="text-xs">
                                {a.name}
                            </Badge>
                        ))}
                    </div>
                ) : (
                    "—"
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
            placeholder: "Ej. Falla de red",
        },
        {
            key: "code",
            label: "Código",
            type: "text",
            required: true,
            placeholder: "Ej. falla_red",
        },
        {
            key: "area_ids",
            label: "Áreas asignadas",
            type: "multiselect",
            required: true,
            options: areaOptions,
        },
        {
            key: "is_active",
            label: "Tipo activo",
            type: "switch",
            defaultValue: true,
        },
    ];

    const editInitial = catalog.editTarget
        ? {
              ...catalog.editTarget,
              area_ids: (catalog.editTarget.areas ?? []).map((a) => a.id),
          }
        : { area_ids: [], is_active: true };

    return (
        <>
            <CatalogPage
                title="Tipos de ticket"
                description="Clasificación por falla/solicitud y áreas responsables"
                columns={columns}
                data={ticketTypes ?? []}
                onAdd={catalog.openCreate}
                onEdit={catalog.openEdit}
                onDelete={catalog.handleDelete}
                onToggle={catalog.handleToggle}
                loading={catalog.loading}
                addLabel="Nuevo tipo"
                emptyMessage="No hay tipos registrados"
            />

            <CatalogDialog
                key={catalog.editTarget?.id ?? "create"}
                open={catalog.dialogOpen}
                onClose={catalog.closeDialog}
                title={catalog.editTarget ? "Editar tipo" : "Nuevo tipo"}
                fields={fields}
                initialValues={editInitial}
                onSubmit={catalog.handleSubmit}
                loading={catalog.loading}
                errors={catalog.dialogErrors}
                submitLabel={catalog.editTarget ? "Actualizar" : "Crear"}
            />
        </>
    );
}

TicketTypes.layout = (page) => <AuthenticatedLayout title="Tipos de ticket">{page}</AuthenticatedLayout>;
