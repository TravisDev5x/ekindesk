import { Head } from "@inertiajs/react";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";
import HomeDashboard from "@/components/dashboard/HomeDashboard";

export default function HomeDashboardPage() {
    return (
        <>
            <Head title="Inicio" />
            <HomeDashboard />
        </>
    );
}

HomeDashboardPage.layout = (page) => (
    <AuthenticatedLayout title="Inicio">{page}</AuthenticatedLayout>
);
