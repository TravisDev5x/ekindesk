import { Sun, Moon, Monitor } from 'lucide-react';
import { useTheme } from '@/hooks/useTheme';

const OPTIONS = [
    { value: 'light', icon: Sun, label: 'Claro' },
    { value: 'dark', icon: Moon, label: 'Oscuro' },
    { value: 'system', icon: Monitor, label: 'Sistema' },
];

export function ThemeToggle({ variant = 'icon', value, onValueChange }) {
    const { theme: contextTheme, setTheme } = useTheme();
    const theme = value ?? contextTheme;

    const applyTheme = (next) => {
        if (onValueChange) {
            onValueChange(next);
            return;
        }
        setTheme(next);
    };

    if (variant === 'icon') {
        const current = OPTIONS.find((o) => o.value === theme) ?? OPTIONS[2];
        const next = OPTIONS[(OPTIONS.indexOf(current) + 1) % OPTIONS.length];
        const Icon = current.icon;

        return (
            <button
                type="button"
                onClick={() => applyTheme(next.value)}
                className="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                title={`Tema: ${current.label} → ${next.label}`}
                aria-label="Cambiar tema"
            >
                <Icon className="h-4 w-4" />
            </button>
        );
    }

    if (variant === 'select') {
        return (
            <div className="flex items-center gap-1 rounded-lg bg-muted p-1">
                {OPTIONS.map(({ value: optValue, icon: Icon, label }) => (
                    <button
                        key={optValue}
                        type="button"
                        onClick={() => applyTheme(optValue)}
                        className={`flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition-all ${
                            theme === optValue
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        }`}
                        title={label}
                    >
                        <Icon className="h-3.5 w-3.5" />
                        <span className="hidden sm:inline">{label}</span>
                    </button>
                ))}
            </div>
        );
    }

    return null;
}
