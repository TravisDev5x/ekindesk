import { lazy, Suspense, useCallback } from "react";
import { BrowserRouter, Routes, Route, Navigate, Outlet, useLocation } from "react-router-dom";
import { Toaster } from "sileo";
import axios from "@/lib/axios";
import { AuthProvider, useAuth } from "@/context/AuthContext";
import { SidebarPositionProvider } from "@/context/SidebarPositionContext";
import { ThemeProvider } from "@/components/theme-provider";
import { I18nProvider } from "@/i18n/I18nProvider";

// Layout (no lazy: se necesita de inmediato para la shell)
import AppLayout from "@/layouts/AppLayout";

// Vistas privadas (lazy) — solo rutas SPA (sin Inertia::render en web.php)
const Dashboard = lazy(() => import("@/Pages/Dashboard"));
const TicketDetalle = lazy(() => import("@/Pages/TicketDetalle"));
const TicketCreate = lazy(() => import("@/Pages/TicketCreate"));
const Tickets = lazy(() => import("@/Pages/Tickets"));
const ResolbebDashboard = lazy(() => import("@/Pages/Resolbeb/Dashboard"));
const ResolbebIndex = lazy(() => import("@/Pages/Resolbeb/Index"));
const ResolbebCreate = lazy(() => import("@/Pages/Resolbeb/Create"));
const ResolbebDetalle = lazy(() => import("@/Pages/Resolbeb/Detalle"));
const Incidents = lazy(() => import("@/Pages/Incidents"));
const IncidentDetalle = lazy(() => import("@/Pages/IncidentDetalle"));
const IncidentTipos = lazy(() => import("@/Pages/IncidentTipos"));
const IncidentSeveridades = lazy(() => import("@/Pages/IncidentSeveridades"));
const IncidentEstados = lazy(() => import("@/Pages/IncidentEstados"));
// Público (lazy) — auth guest la sirve Inertia vía web.php
const Manual = lazy(() => import("@/Pages/Manual"));

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

function NotFound() {
    return (
        <div className="space-y-2 p-10">
            <h1 className="text-2xl font-semibold">404</h1>
            <p className="text-muted-foreground">Ruta no encontrada.</p>
        </div>
    );
}

function SpaThemeShell({ children }) {
    const { user, updateUserTheme } = useAuth();

    const handleThemeChange = useCallback(
        (theme) => {
            if (user?.id) {
                axios
                    .put("/api/profile/theme", { theme })
                    .then(() => updateUserTheme(theme))
                    .catch(() => {});
            }
        },
        [user?.id, updateUserTheme]
    );

    return (
        <ThemeProvider
            defaultTheme="system"
            storageKey="ekindesk_theme"
            onThemeChange={handleThemeChange}
        >
            {children}
        </ThemeProvider>
    );
}

export default function Main() {
    return (
        <AuthProvider>
            <SpaThemeShell>
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

                            {/* ZONA PRIVADA */}
                            <Route element={<ProtectedRoute />}>
                                {/* Wallboard RESOLBEB: sin sidebar/navbar, para segundo monitor */}
                                <Route path="/tickets/wallboard" element={<ResolbebDashboard isStandalone />} />
                                <Route element={<AppLayout />}>
                                    <Route path="/" element={<Dashboard />} />
                                    {/* Legacy tickets (fuera de Resolbeb) */}
                                    <Route path="/mis-tickets" element={<Tickets />} />
                                    <Route path="/tickets" element={<Tickets />} />
                                    <Route path="/tickets/new" element={<TicketCreate />} />
                                    <Route path="/tickets/:id" element={<TicketDetalle />} />
                                    <Route path="/ticket-states" element={<Navigate to="/resolbeb/estados" replace />} />
                                    <Route path="/ticket-types" element={<Navigate to="/resolbeb/tipos" replace />} />
                                    {/* Resolbeb: ticketera en módulo (rutas principales — solo SPA) */}
                                    <Route path="/resolbeb" element={<ResolbebDashboard />} />
                                    <Route path="/resolbeb/mis-tickets" element={<ResolbebIndex />} />
                                    <Route path="/resolbeb/tickets" element={<ResolbebIndex />} />
                                    <Route path="/resolbeb/tickets/new" element={<ResolbebCreate />} />
                                    <Route path="/resolbeb/tickets/:id" element={<ResolbebDetalle />} />
                                    <Route path="/incidents" element={<Incidents />} />
                                    <Route path="/incidents/:id" element={<IncidentDetalle />} />
                                    <Route path="/incident-types" element={<IncidentTipos />} />
                                    <Route path="/incident-severities" element={<IncidentSeveridades />} />
                                    <Route path="/incident-statuses" element={<IncidentEstados />} />
                                    <Route path="*" element={<NotFound />} />
                                </Route>
                            </Route>

                        </Routes>
                    </Suspense>
                </BrowserRouter>
                    </I18nProvider>
                </SidebarPositionProvider>
            </SpaThemeShell>
        </AuthProvider>
    );
}
