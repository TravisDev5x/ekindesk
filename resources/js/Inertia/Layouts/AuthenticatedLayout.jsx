import { Head, Link } from "@inertiajs/react";
import { Building2, Home } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function AuthenticatedLayout({ children, title }) {
    return (
        <div className="min-h-[100dvh] bg-background text-foreground">
            <Head title={title} />
            <header className="border-b border-border bg-card/50 backdrop-blur sticky top-0 z-10">
                <div className="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <Building2 className="h-5 w-5 text-primary" />
                        <span className="font-semibold text-sm">EkinDesk</span>
                    </div>
                    <nav className="flex items-center gap-2">
                        <Button asChild variant="ghost" size="sm">
                            <Link href="/clients">
                                <Building2 className="h-4 w-4 mr-1" />
                                Clientes
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <a href="/">
                                <Home className="h-4 w-4 mr-1" />
                                Panel
                            </a>
                        </Button>
                    </nav>
                </div>
            </header>
            <main className="max-w-6xl mx-auto px-4 py-8">{children}</main>
        </div>
    );
}
