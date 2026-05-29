import { useEffect, useState } from "react";
import { Loader2 } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Switch } from "@/components/ui/switch";
import { Checkbox } from "@/components/ui/checkbox";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

function buildInitialValues(fields, initialValues) {
    const base = {};
    fields.forEach((field) => {
        if (initialValues && initialValues[field.key] !== undefined) {
            base[field.key] = initialValues[field.key];
        } else if (field.type === "multiselect") {
            base[field.key] = Array.isArray(initialValues?.[field.key])
                ? initialValues[field.key]
                : field.defaultValue ?? [];
        } else if (field.type === "switch") {
            base[field.key] = field.defaultValue ?? true;
        } else if (field.type === "number") {
            base[field.key] = field.defaultValue ?? field.min ?? 0;
        } else {
            base[field.key] = field.defaultValue ?? "";
        }
    });
    return base;
}

export default function CatalogDialog({
    open,
    onClose,
    title,
    onSubmit,
    loading = false,
    fields = [],
    initialValues = {},
    submitLabel = "Guardar",
    errors = {},
}) {
    const [formData, setFormData] = useState(() => buildInitialValues(fields, initialValues));
    const [clientErrors, setClientErrors] = useState({});

    useEffect(() => {
        if (open) {
            setFormData(buildInitialValues(fields, initialValues));
            setClientErrors({});
        }
    }, [open, initialValues, fields]);

    const setField = (key, value) => {
        setFormData((prev) => ({ ...prev, [key]: value }));
        setClientErrors((prev) => {
            if (!prev[key]) return prev;
            const next = { ...prev };
            delete next[key];
            return next;
        });
    };

    const fieldError = (key) => {
        const apiErr = errors[key];
        if (Array.isArray(apiErr) && apiErr.length) return apiErr[0];
        if (typeof apiErr === "string") return apiErr;
        return clientErrors[key];
    };

    const validate = () => {
        const next = {};
        fields.forEach((field) => {
            if (!field.required) return;
            const val = formData[field.key];
            if (field.type === "switch") return;
            if (field.type === "multiselect") {
                if (!Array.isArray(val) || val.length === 0) {
                    next[field.key] = `${field.label} es requerido`;
                }
                return;
            }
            if (val === undefined || val === null || String(val).trim() === "") {
                next[field.key] = `${field.label} es requerido`;
            }
        });
        setClientErrors(next);
        return Object.keys(next).length === 0;
    };

    const toggleMultiselect = (key, value, checked) => {
        setFormData((prev) => {
            const current = Array.isArray(prev[key]) ? prev[key] : [];
            const num = Number(value);
            const next = checked
                ? [...current.filter((id) => id !== num), num]
                : current.filter((id) => id !== num);
            return { ...prev, [key]: next };
        });
        setClientErrors((prev) => {
            if (!prev[key]) return prev;
            const next = { ...prev };
            delete next[key];
            return next;
        });
    };

    const handleSubmit = () => {
        if (!validate()) return;
        onSubmit(formData);
    };

    return (
        <Dialog open={open} onOpenChange={(isOpen) => !isOpen && !loading && onClose()}>
            <DialogContent className="sm:max-w-md max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                </DialogHeader>

                <div className="grid gap-4 py-2">
                    {fields.map((field) => (
                        <div key={field.key} className="space-y-1.5">
                            {field.type === "switch" ? (
                                <div className="flex items-center justify-between rounded-md border px-3 py-2">
                                    <div>
                                        <p className="text-sm font-medium">{field.label}</p>
                                        {field.switchDescription && (
                                            <p className="text-xs text-muted-foreground">
                                                {field.switchDescription}
                                            </p>
                                        )}
                                    </div>
                                    <Switch
                                        id={field.key}
                                        checked={Boolean(formData[field.key])}
                                        onCheckedChange={(v) => setField(field.key, v)}
                                    />
                                </div>
                            ) : (
                                <>
                                    <Label htmlFor={field.key}>
                                        {field.label}
                                        {field.required ? " *" : ""}
                                    </Label>

                                    {field.type === "textarea" && (
                                        <Textarea
                                            id={field.key}
                                            rows={3}
                                            value={formData[field.key] ?? ""}
                                            placeholder={field.placeholder}
                                            onChange={(e) => setField(field.key, e.target.value)}
                                        />
                                    )}

                                    {field.type === "multiselect" && (
                                        <div className="flex flex-wrap gap-2 rounded-md border p-3 max-h-40 overflow-y-auto">
                                            {(field.options ?? []).map((opt) => {
                                                const num = Number(opt.value);
                                                const checked = (formData[field.key] ?? []).includes(num);
                                                return (
                                                    <label
                                                        key={String(opt.value)}
                                                        className="flex items-center gap-2 text-sm cursor-pointer w-full sm:w-auto"
                                                    >
                                                        <Checkbox
                                                            checked={checked}
                                                            onCheckedChange={(v) =>
                                                                toggleMultiselect(field.key, opt.value, Boolean(v))
                                                            }
                                                        />
                                                        {opt.label}
                                                    </label>
                                                );
                                            })}
                                        </div>
                                    )}

                                    {field.type === "select" && (
                                        <Select
                                            value={
                                                formData[field.key] !== undefined && formData[field.key] !== null
                                                    ? String(formData[field.key])
                                                    : ""
                                            }
                                            onValueChange={(v) => setField(field.key, v)}
                                        >
                                            <SelectTrigger id={field.key}>
                                                <SelectValue placeholder={field.placeholder || "Seleccionar…"} />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {(field.options ?? []).map((opt) => (
                                                    <SelectItem key={String(opt.value)} value={String(opt.value)}>
                                                        {opt.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}

                                    {(field.type === "text" ||
                                        field.type === "number" ||
                                        field.type === "color" ||
                                        !field.type) && (
                                        <Input
                                            id={field.key}
                                            type={field.type === "number" ? "number" : field.type === "color" ? "color" : "text"}
                                            min={field.min}
                                            max={field.max}
                                            value={formData[field.key] ?? ""}
                                            placeholder={field.placeholder}
                                            onChange={(e) =>
                                                setField(
                                                    field.key,
                                                    field.type === "number"
                                                        ? e.target.value === ""
                                                            ? ""
                                                            : Number(e.target.value)
                                                        : e.target.value
                                                )
                                            }
                                        />
                                    )}
                                </>
                            )}

                            {field.help && (
                                <p className="text-xs text-muted-foreground">{field.help}</p>
                            )}
                            {fieldError(field.key) && (
                                <p className="text-xs text-destructive">{fieldError(field.key)}</p>
                            )}
                        </div>
                    ))}
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" onClick={onClose} disabled={loading}>
                        Cancelar
                    </Button>
                    <Button type="button" onClick={handleSubmit} disabled={loading}>
                        {loading ? (
                            <>
                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                Guardando…
                            </>
                        ) : (
                            submitLabel
                        )}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
