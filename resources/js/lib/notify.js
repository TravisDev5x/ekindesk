/**
 * Wrapper sobre Sileo para toasts unificados (top-center en toda la app).
 * Sustituye el uso de useToast() / toast() del sistema anterior.
 *
 * Uso:
 *   import { notify } from '@/lib/notify';
 *   notify.success('Guardado');
 *   notify.error('No se pudo guardar');
 *   notify.success({ title: 'Éxito', description: 'Ticket creado' });
 */
import { sileo } from "sileo";

const DEFAULT_DURATION = { success: 4000, info: 4000, warning: 6000, error: 8000 };

/** Fondos por tipo; siguen --toast-* en :root y .dark (app.css). */
const FILL_BY_TYPE = {
    success: "hsl(var(--toast-success))",
    error: "hsl(var(--toast-error))",
    warning: "hsl(var(--toast-warning))",
    info: "hsl(var(--toast-info))",
};

function normalizePayload(msgOrPayload, defaultTitle) {
    if (typeof msgOrPayload === "string") {
        return { title: defaultTitle, description: msgOrPayload };
    }
    return {
        title: defaultTitle,
        description: "",
        ...msgOrPayload,
    };
}

export const notify = {
    success(msgOrPayload) {
        const payload = normalizePayload(msgOrPayload, "Éxito");
        return sileo.success({
            ...payload,
            fill: payload.fill ?? FILL_BY_TYPE.success,
            duration: payload.duration ?? DEFAULT_DURATION.success,
        });
    },
    error(msgOrPayload) {
        const payload = normalizePayload(msgOrPayload, "Error");
        return sileo.error({
            ...payload,
            fill: payload.fill ?? FILL_BY_TYPE.error,
            duration: payload.duration ?? DEFAULT_DURATION.error,
        });
    },
    warning(msgOrPayload) {
        const payload = normalizePayload(msgOrPayload, "Advertencia");
        return sileo.warning({
            ...payload,
            fill: payload.fill ?? FILL_BY_TYPE.warning,
            duration: payload.duration ?? DEFAULT_DURATION.warning,
        });
    },
    info(msgOrPayload) {
        const payload = normalizePayload(msgOrPayload, "Información");
        return sileo.info({
            ...payload,
            fill: payload.fill ?? FILL_BY_TYPE.info,
            duration: payload.duration ?? DEFAULT_DURATION.info,
        });
    },
    promise(promise, messages = {}) {
        return sileo.promise(promise, {
            loading: messages.loading ?? "Cargando...",
            success: messages.success ?? "Listo",
            error: messages.error ?? "Ha ocurrido un error",
        });
    },
    dismiss: (id) => sileo.dismiss(id),
    clear: (position) => sileo.clear(position),
};
