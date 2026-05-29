import { useState } from "react";
import { Link } from "@inertiajs/react";
import { Menu, Sun, Moon } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from "@/components/ui/sheet";

const NAV_LINKS = [
    { href: "#mission", label: "Misión" },
    { href: "#features", label: "En qué creemos" },
    { href: "#how-it-works", label: "Cómo empezar" },
    { href: "#pricing", label: "Planes" },
    { href: "#faq", label: "Preguntas" },
    { href: "#footer", label: "Contacto" },
];

function BrandLogo() {
    return (
        <Link href="/" className="flex items-center gap-2.5 shrink-0">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-cyan-400 to-blue-600 text-sm font-black text-white shadow-lg shadow-cyan-500/20">
                E
            </div>
            <span className="text-lg font-bold text-white tracking-tight">EkinDesk</span>
        </Link>
    );
}

function LandingThemeToggle() {
    const [override, setOverride] = useState(() => sessionStorage.getItem("landing_theme") || null);

    const isDark = override
        ? override === "dark"
        : window.matchMedia("(prefers-color-scheme: dark)").matches;

    const toggle = () => {
        const newTheme = isDark ? "light" : "dark";
        sessionStorage.setItem("landing_theme", newTheme);
        setOverride(newTheme);

        const root = document.documentElement;
        root.classList.remove("light", "dark");
        if (newTheme === "dark") {
            root.classList.add("dark");
            root.style.colorScheme = "dark";
        } else {
            root.style.colorScheme = "light";
        }
    };

    return (
        <button
            type="button"
            onClick={toggle}
            className="rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-800/50 hover:text-slate-200"
            title={isDark ? "Cambiar a modo claro" : "Cambiar a modo oscuro"}
            aria-label="Cambiar tema de la landing"
        >
            {isDark ? <Sun className="h-4 w-4" /> : <Moon className="h-4 w-4" />}
        </button>
    );
}

export default function Navbar() {
    const [open, setOpen] = useState(false);

    return (
        <header className="sticky top-0 z-50 border-b border-slate-800 bg-slate-950/80 backdrop-blur-md">
            <div className="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-6 py-4">
                <BrandLogo />

                <nav className="hidden lg:flex items-center gap-8">
                    {NAV_LINKS.map((link) => (
                        <a
                            key={link.href}
                            href={link.href}
                            className="text-sm text-slate-400 transition-colors hover:text-white"
                        >
                            {link.label}
                        </a>
                    ))}
                </nav>

                <div className="hidden lg:flex items-center gap-3">
                    <LandingThemeToggle />
                    <Button variant="ghost" className="text-slate-300 hover:text-white" asChild>
                        <Link href="/login">Iniciar sesión</Link>
                    </Button>
                    <Button
                        className="bg-gradient-to-r from-cyan-500 to-blue-600 font-semibold text-white hover:from-cyan-400 hover:to-blue-500 border-0"
                        asChild
                    >
                        <Link href="/register">Crear cuenta</Link>
                    </Button>
                </div>

                <Sheet open={open} onOpenChange={setOpen}>
                    <SheetTrigger asChild className="lg:hidden">
                        <Button variant="ghost" size="icon" className="text-slate-300">
                            <Menu className="h-5 w-5" />
                            <span className="sr-only">Menú</span>
                        </Button>
                    </SheetTrigger>
                    <SheetContent
                        side="right"
                        className="border-slate-800 bg-slate-900 text-slate-100 w-[min(100vw,20rem)]"
                    >
                        <SheetHeader>
                            <SheetTitle className="text-white text-left">Menú</SheetTitle>
                        </SheetHeader>
                        <nav className="mt-8 flex flex-col gap-4">
                            {NAV_LINKS.map((link) => (
                                <a
                                    key={link.href}
                                    href={link.href}
                                    onClick={() => setOpen(false)}
                                    className="text-slate-300 hover:text-cyan-400 transition-colors"
                                >
                                    {link.label}
                                </a>
                            ))}
                        </nav>
                        <div className="mt-8 flex flex-col gap-3">
                            <div className="flex justify-center pb-2">
                                <LandingThemeToggle />
                            </div>
                            <Button variant="outline" className="border-slate-700 text-slate-300" asChild>
                                <Link href="/login" onClick={() => setOpen(false)}>
                                    Iniciar sesión
                                </Link>
                            </Button>
                            <Button
                                className="bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-semibold"
                                asChild
                            >
                                <Link href="/register" onClick={() => setOpen(false)}>
                                    Crear cuenta
                                </Link>
                            </Button>
                        </div>
                    </SheetContent>
                </Sheet>
            </div>
        </header>
    );
}
