import "../css/app.css";
import "sileo/styles.css";
import { createRoot } from "react-dom/client";
import { createInertiaApp } from "@inertiajs/react";
import { Toaster } from "sileo";

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
            <>
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
            </>
        );
    },
    progress: {
        color: "#2563eb",
    },
});
