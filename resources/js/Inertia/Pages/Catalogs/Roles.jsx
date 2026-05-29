import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import CatalogPage from "@/Inertia/components/CatalogPage";
import CatalogDialog from "@/Inertia/components/CatalogDialog";
import useCatalog from "@/Inertia/hooks/useCatalog";
import { useAuth } from "@/context/AuthContext";
import { Badge } from "@/components/ui/badge";

export default function Roles() {
    const { roles } = usePage().props;
    const { can } = useAuth();
    const canManage = can("roles.manage");

    const catalog = useCatalog("/api/roles", () => router.reload({ only: ["roles"] }));

    const columns = [
        { key: "name", label: "Nombre" },
        {
            key: "guard_name",
            label: "Guard",
            width: "w-[120px]",
            render: (row) => (
                <Badge variant="secondary" className="font-mono text-xs">
                    {row.guard_name ?? "web"}
                </Badge>
            ),
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
            label: "Nombre del rol",
            type: "text",
            required: true,
            placeholder: "Ej. Admin, Editor, Soporte",
            help: "Mínimo 3 caracteres. El slug y guard se generan en el servidor.",
        },
    ];

    return (
        <>
            <CatalogPage
                title="Roles"
                description="Administra los roles del sistema"
                columns={columns}
                data={roles ?? []}
                onAdd={canManage ? catalog.openCreate : undefined}
                onDelete={canManage ? catalog.handleDelete : null}
                loading={catalog.loading}
                addLabel="Nuevo rol"
                emptyMessage="No hay roles registrados"
                canCreate={canManage}
                canEdit={false}
                canDelete={canManage}
            />

            <CatalogDialog
                key="create-role"
                open={catalog.dialogOpen}
                onClose={catalog.closeDialog}
                title="Nuevo rol"
                fields={fields}
                initialValues={{}}
                onSubmit={catalog.handleSubmit}
                loading={catalog.loading}
                errors={catalog.dialogErrors}
                submitLabel="Crear"
            />
        </>
    );
}

Roles.layout = (page) => <AuthenticatedLayout title="Roles">{page}</AuthenticatedLayout>;
