import { lazy, Suspense } from "react";
import { BrowserRouter, Routes, Route, Navigate, Outlet, useLocation } from "react-router-dom";
import { Toaster } from "sileo";
import { AuthProvider, useAuth } from "@/context/AuthContext";
import { SidebarPositionProvider } from "@/context/SidebarPositionContext";
import { ThemeProvider } from "@/components/theme-provider";
import { I18nProvider } from "@/i18n/I18nProvider";

// Layout (no lazy: se necesita de inmediato para la shell)
import AppLayout from "@/layouts/AppLayout";

// Vistas privadas (lazy)
const Dashboard = lazy(() => import("@/Pages/Dashboard"));
const Users = lazy(() => import("@/Pages/Users"));
const Invitations = lazy(() => import("@/Pages/Users/Invitations"));
const Roles = lazy(() => import("@/Pages/Roles"));
const Campaigns = lazy(() => import("@/Pages/Campaigns"));
const Areas = lazy(() => import("@/Pages/Areas"));
const Positions = lazy(() => import("@/Pages/Positions"));
const Sedes = lazy(() => import("@/Pages/Sedes"));
const Clientes = lazy(() => import("@/Pages/Clientes"));
const Ubicaciones = lazy(() => import("@/Pages/Ubicaciones"));
const Prioridades = lazy(() => import("@/Pages/Prioridades"));
const ImpactLevels = lazy(() => import("@/Pages/ImpactLevels"));
const UrgencyLevels = lazy(() => import("@/Pages/UrgencyLevels"));
const PriorityMatrixSettings = lazy(() => import("@/Pages/Settings/PriorityMatrix/Index"));
const TicketEstados = lazy(() => import("@/Pages/TicketEstados"));
const TicketTipos = lazy(() => import("@/Pages/TicketTipos"));
const TicketMacros = lazy(() => import("@/Pages/TicketMacros"));
const TicketDetalle = lazy(() => import("@/Pages/TicketDetalle"));
const TicketCreate = lazy(() => import("@/Pages/TicketCreate"));
const Tickets = lazy(() => import("@/Pages/Tickets"));
// Resolbeb (ticketera en módulo propio)
const ResolbebDashboard = lazy(() => import("@/Pages/Resolbeb/Dashboard"));
const ResolbebIndex = lazy(() => import("@/Pages/Resolbeb/Index"));
const ResolbebCreate = lazy(() => import("@/Pages/Resolbeb/Create"));
const ResolbebDetalle = lazy(() => import("@/Pages/Resolbeb/Detalle"));
const ResolbebEstados = lazy(() => import("@/Pages/Resolbeb/Estados/Index"));
const ResolbebTipos = lazy(() => import("@/Pages/Resolbeb/Tipos/Index"));
const Calendario = lazy(() => import("@/Pages/Calendario"));
const Incidents = lazy(() => import("@/Pages/Incidents"));
const IncidentDetalle = lazy(() => import("@/Pages/IncidentDetalle"));
const IncidentTipos = lazy(() => import("@/Pages/IncidentTipos"));
const IncidentSeveridades = lazy(() => import("@/Pages/IncidentSeveridades"));
const IncidentEstados = lazy(() => import("@/Pages/IncidentEstados"));
const Settings = lazy(() => import("@/Pages/Settings"));
const Sessions = lazy(() => import("@/Pages/Sessions"));
const Permissions = lazy(() => import("@/Pages/Permissions"));
const AuditCommandCenter = lazy(() => import("@/Pages/AuditCommandCenter"));
const Profile = lazy(() => import("@/Pages/Profile"));
// Público / auth (lazy)
const Login = lazy(() => import("@/Pages/Login"));
const ForgotPassword = lazy(() => import("@/Pages/ForgotPassword"));
const ResetPassword = lazy(() => import("@/Pages/ResetPassword"));
const ForceChangePassword = lazy(() => import("@/Pages/ForceChangePassword"));
const Manual = lazy(() => import("@/Pages/Manual"));
const VerifyEmail = lazy(() => import("@/Pages/VerifyEmail"));

const Fallback = () => (
    <div className="p-6 text-sm text-muted-foreground">Cargando…</div>
);

const ProtectedRoute = () => {
    const { user, loading } = useAuth();
    const location = useLocation();

    if (loading) return <Fallback />;

    if (!user) return <Navigate to="/login" />;

    if (user.force_password_change && location.pathname !== "/force-change-password") {
        return <Navigate to="/force-change-password" />;
    }

    if (
        user.onboarding_redirect &&
        !location.pathname.startsWith("/onboarding")
    ) {
        window.location.href = user.onboarding_redirect;
        return <Fallback />;
    }

    return <Outlet />;
};

const GuestRoute = () => {
    const { user, loading } = useAuth();

    if (loading) return null;

    if (user?.onboarding_redirect) {
        window.location.href = user.onboarding_redirect;
        return <Fallback />;
    }

    return user ? <Navigate to="/" /> : <Outlet />;
};

function NotFound() {
    return (
        <div className="space-y-2 p-10">
            <h1 className="text-2xl font-semibold">404</h1>
            <p className="text-muted-foreground">Ruta no encontrada.</p>
        </div>
    );
}

export default function Main() {
    return (
        <AuthProvider>
            <ThemeProvider defaultTheme="system" storageKey="theme">
                <SidebarPositionProvider>
                    <I18nProvider>
                    <Toaster
                        position="top-center"
                        options={{
                            fill: "hsl(var(--card))",
                            roundness: 12,
                            styles: {
                                title: "!text-foreground !font-semibold",
                                description: "!text-foreground/90",
                                badge: "!bg-primary/15 !text-primary !border !border-primary/30",
                                button: "!bg-muted hover:!bg-accent !text-foreground",
                            },
                        }}
                    />
                    <BrowserRouter>
                    <Suspense fallback={<Fallback />}>
                        <Routes>

                            {/* ZONA PÚBLICA (Login) */}
                            <Route path="/manual" element={<Manual />} />
                            <Route path="/verify-email" element={<VerifyEmail />} />
                            <Route element={<GuestRoute />}>
                                {/* /register lo sirve Laravel → Inertia (web.php), no React Router */}
                                <Route path="/login" element={<Login />} />
                                <Route path="/forgot-password" element={<ForgotPassword />} />
                                <Route path="/reset-password" element={<ResetPassword />} />
                            </Route>

                            {/* ZONA PRIVADA */}
                            <Route element={<ProtectedRoute />}>
                                {/* Wallboard RESOLBEB: sin sidebar/navbar, para segundo monitor */}
                                <Route path="/tickets/wallboard" element={<ResolbebDashboard isStandalone />} />
                                <Route element={<AppLayout />}>
                                    <Route path="/force-change-password" element={<ForceChangePassword />} />
                                    <Route path="/" element={<Dashboard />} />
                                    <Route path="/users" element={<Users />} />
                                    <Route path="/users/invitations" element={<Invitations />} />
                                    <Route path="/campaigns" element={<Campaigns />} />
                                    <Route path="/areas" element={<Areas />} />
                                    <Route path="/positions" element={<Positions />} />
                                    <Route path="/sedes" element={<Sedes />} />
                                    <Route path="/clientes" element={<Clientes />} />
                                    <Route path="/ubicaciones" element={<Ubicaciones />} />
                                    <Route path="/priorities" element={<Prioridades />} />
                                    <Route path="/impact-levels" element={<ImpactLevels />} />
                                    <Route path="/urgency-levels" element={<UrgencyLevels />} />
                                    <Route path="/priority-matrix" element={<PriorityMatrixSettings />} />
                                    <Route path="/ticket-states" element={<TicketEstados />} />
                                    <Route path="/ticket-types" element={<TicketTipos />} />
                                    <Route path="/ticket-macros" element={<TicketMacros />} />
                                    <Route path="/mis-tickets" element={<Tickets />} />
                                    <Route path="/tickets" element={<Tickets />} />
                                    <Route path="/tickets/new" element={<TicketCreate />} />
                                    <Route path="/tickets/:id" element={<TicketDetalle />} />
                                    {/* Resolbeb: ticketera en módulo (rutas principales) */}
                                    <Route path="/resolbeb" element={<ResolbebDashboard />} />
                                    <Route path="/resolbeb/mis-tickets" element={<ResolbebIndex />} />
                                    <Route path="/resolbeb/tickets" element={<ResolbebIndex />} />
                                    <Route path="/resolbeb/tickets/new" element={<ResolbebCreate />} />
                                    <Route path="/resolbeb/tickets/:id" element={<ResolbebDetalle />} />
                                    <Route path="/resolbeb/estados" element={<ResolbebEstados />} />
                                    <Route path="/resolbeb/tipos" element={<ResolbebTipos />} />
                                    <Route path="/calendario" element={<Calendario />} />
                                    <Route path="/incidents" element={<Incidents />} />
                                    <Route path="/incidents/:id" element={<IncidentDetalle />} />
                                    <Route path="/incident-types" element={<IncidentTipos />} />
                                    <Route path="/incident-severities" element={<IncidentSeveridades />} />
                                    <Route path="/incident-statuses" element={<IncidentEstados />} />
                                    <Route path="/roles" element={<Roles />} />
                                    <Route path="/settings" element={<Settings />} />
                                    <Route path="/sessions" element={<Sessions />} />
                                    <Route path="/permissions" element={<Permissions />} />
                                    <Route path="/audit-command" element={<AuditCommandCenter />} />
                                    <Route path="/profile" element={<Profile />} />
                                    <Route path="*" element={<NotFound />} />
                                </Route>
                            </Route>

                        </Routes>
                    </Suspense>
                </BrowserRouter>
                    </I18nProvider>
                </SidebarPositionProvider>
            </ThemeProvider>
        </AuthProvider>
    );
}


