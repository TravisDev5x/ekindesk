import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import react from "@vitejs/plugin-react";
import path from "path";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.jsx", "resources/js/inertia.jsx"],
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
        // Una sola copia de React para SPA + Inertia (evita createContext undefined).
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
            },
        },
        chunkSizeWarningLimit: 600,
    },
});
