import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import react from "@vitejs/plugin-react";
import path from "path";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/inertia.jsx"],
            refresh: true,
        }),
        react(),
    ],
    server: {
        host: true,
        strictPort: true,
        port: 5173,
        cors: {
            origin: true,
            credentials: true,
        },
        allowedHosts: true,
        headers: {
            "Cache-Control": "no-store",
        },
        hmr: process.env.VITE_HMR_HOST
            ? { host: process.env.VITE_HMR_HOST, port: 5173, protocol: "ws" }
            : true,
    },
    resolve: {
        alias: {
            "@": path.resolve(__dirname, "resources/js"),
        },
        // Una sola copia de React (evita createContext undefined con Inertia).
        dedupe: ["react", "react-dom"],
    },
    build: {
        target: "es2020",
        minify: "esbuild",
        sourcemap: false,
        rollupOptions: {
            output: {
                chunkFileNames: "js/[name]-[hash].js",
                entryFileNames: "js/[name]-[hash].js",
                assetFileNames: "assets/[name]-[hash][extname]",
                manualChunks(id) {
                    if (id.includes("node_modules")) {
                        if (id.includes("react-dom") || id.includes("/react/")) {
                            return "vendor-react";
                        }
                        if (id.includes("@inertiajs")) {
                            return "vendor-inertia";
                        }
                        if (id.includes("@radix-ui")) {
                            return "vendor-radix";
                        }
                        if (id.includes("lucide-react")) {
                            return "vendor-lucide";
                        }
                        if (id.includes("recharts") || id.includes("d3-")) {
                            return "vendor-charts";
                        }
                        if (id.includes("date-fns")) {
                            return "vendor-date";
                        }
                        if (id.includes("intl") || id.includes("/i18n/")) {
                            return "vendor-i18n";
                        }
                        return;
                    }

                    if (id.includes("/resources/js/i18n/messages")) {
                        return "vendor-i18n";
                    }

                    if (id.includes("/Inertia/Pages/Catalogs/")) {
                        return "pages-catalogs";
                    }
                    if (id.includes("/Inertia/Pages/System/")) {
                        return "pages-system";
                    }
                    if (id.includes("/Inertia/Pages/Auth/")) {
                        return "pages-auth";
                    }
                },
            },
        },
        chunkSizeWarningLimit: 600,
    },
});
