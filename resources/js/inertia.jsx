import "../css/app.css";
import "sileo/styles.css";
import { createRoot } from "react-dom/client";
import { createInertiaApp } from "@inertiajs/react";
import { BrowserRouter } from "react-router-dom";
import { Toaster } from "sileo";
import { AuthProvider } from "@/context/AuthContext";
import { InertiaI18nProvider } from "@/i18n/I18nProvider";
import { SidebarPositionProvider } from "@/context/SidebarPositionContext";

import { InertiaThemeProvider } from "./Inertia/components/InertiaThemeProvider";

const pages = import.meta.glob("./Inertia/Pages/**/*.jsx", { eager: true });

createInertiaApp({
    resolve: (name) => {
        const page = pages[`./Inertia/Pages/${name}.jsx`];
        if (!page) {
            throw new Error(`Página Inertia no encontrada: ${name}`);
        }
        return page;
    },
    setup({ el, App, props }) {
        createRoot(el).render(
            <InertiaThemeProvider>
                <BrowserRouter>
                    <AuthProvider>
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
                </AuthProvider>
            </BrowserRouter>
            </InertiaThemeProvider>
        );
    },
    progress: {
        color: "#2563eb",
    },
});
