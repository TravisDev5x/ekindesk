import "../css/app.css";
import { createRoot } from "react-dom/client";
import { createInertiaApp } from "@inertiajs/react";

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
        createRoot(el).render(<App {...props} />);
    },
    progress: {
        color: "#2563eb",
    },
});
