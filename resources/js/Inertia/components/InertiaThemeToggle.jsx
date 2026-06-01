import { Sun, Moon, Monitor } from "lucide-react";
import { useTheme } from "@/hooks/useTheme";

const OPTIONS = [
    { value: "light", Icon: Sun },
    { value: "dark", Icon: Moon },
    { value: "system", Icon: Monitor },
];

export function InertiaThemeToggle() {
    const { theme, setTheme } = useTheme();

    const currentIndex = OPTIONS.findIndex((o) => o.value === theme);
    const current = OPTIONS[currentIndex] ?? OPTIONS[2];
    const next = OPTIONS[(currentIndex + 1) % OPTIONS.length];
    const { Icon } = current;

    return (
        <button
            type="button"
            onClick={() => setTheme(next.value)}
            className="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
            title={`Tema: ${current.value} → ${next.value}`}
            aria-label="Cambiar tema"
        >
            <Icon className="h-4 w-4" />
        </button>
    );
}
