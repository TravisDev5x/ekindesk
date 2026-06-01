import "../css/app.css";
import "sileo/styles.css";
import React, { Suspense } from "react";
import { createRoot } from "react-dom/client";
import { createInertiaApp } from "@inertiajs/react";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { Toaster } from "sileo";
import { AuthProvider } from "@/context/AuthContext";
import { InertiaI18nProvider } from "@/i18n/I18nProvider";
import { SidebarPositionProvider } from "@/context/SidebarPositionContext";

import { InertiaThemeProvider } from "./Inertia/components/InertiaThemeProvider";
import { ThemeAuthSync } from "./Inertia/components/ThemeAuthSync";

/** Solo entradas de página (excluye subcomponentes de Landing). */
const pages = import.meta.glob([
    "./Inertia/Pages/**/*.jsx",
    "!./Inertia/Pages/Landing/components/**",
]);

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(`./Inertia/Pages/${name}.jsx`, pages),
    setup({ el, App, props }) {
        createRoot(el).render(
            <Suspense
                fallback={
                    <div className="flex items-center justify-center min-h-screen">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary" />
                    </div>
                }
            >
                <AuthProvider>
                    <InertiaThemeProvider>
                        <ThemeAuthSync />
                        <InertiaI18nProvider>
                            <SidebarPositionProvider>
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
                                <App {...props} />
                            </SidebarPositionProvider>
                        </InertiaI18nProvider>
                    </InertiaThemeProvider>
                </AuthProvider>
            </Suspense>
        );
    },
    progress: {
        color: "hsl(var(--brand))",
        delay: 100,
        includeCSS: true,
    },
});
