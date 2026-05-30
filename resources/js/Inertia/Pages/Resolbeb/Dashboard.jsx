import DashboardContent from "@/Inertia/components/Resolbeb/DashboardContent";
import AuthenticatedLayout from "@/Inertia/Layouts/AuthenticatedLayout";

export default function Dashboard() {
  return <DashboardContent isStandalone={false} />;
}

Dashboard.layout = (page) => (
  <AuthenticatedLayout title="Dashboard operativo">
    {page}
  </AuthenticatedLayout>
);
