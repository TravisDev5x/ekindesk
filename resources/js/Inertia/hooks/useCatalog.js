import { useState } from "react";
import axios from "@/lib/axios";
import { notify } from "@/lib/notify";
import { clearCatalogCache } from "@/lib/catalogCache";
import { getApiErrorMessage, handleAuthError } from "@/lib/apiErrors";

/**
 * Hook CRUD para catálogos simples (API JSON + recarga Inertia).
 * @param {string} apiEndpoint - ej. '/api/areas'
 * @param {() => void} [onSuccess] - ej. () => router.reload({ only: ['areas'] })
 * @param {{ lazyLoadOnEdit?: boolean }} [options]
 */
export default function useCatalog(apiEndpoint, onSuccess, options = {}) {
    const { lazyLoadOnEdit = false } = options;
    const [loading, setLoading] = useState(false);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editTarget, setEditTarget] = useState(null);
    const [dialogErrors, setDialogErrors] = useState({});

    const openCreate = () => {
        setEditTarget(null);
        setDialogErrors({});
        setDialogOpen(true);
    };

    const openEdit = async (row) => {
        setDialogErrors({});
        if (lazyLoadOnEdit && row?.id) {
            setLoading(true);
            try {
                const { data } = await axios.get(`${apiEndpoint}/${row.id}`);
                setEditTarget(data?.data ?? data);
                setDialogOpen(true);
            } catch (err) {
                if (handleAuthError(err)) return;
                notify.error(getApiErrorMessage(err, "No se pudo cargar el registro"));
            } finally {
                setLoading(false);
            }
            return;
        }
        setEditTarget(row);
        setDialogOpen(true);
    };

    const closeDialog = () => {
        setDialogOpen(false);
        setEditTarget(null);
        setDialogErrors({});
    };

    const handleSubmit = async (formData) => {
        setLoading(true);
        setDialogErrors({});
        try {
            if (editTarget) {
                await axios.put(`${apiEndpoint}/${editTarget.id}`, formData);
            } else {
                await axios.post(apiEndpoint, formData);
            }
            clearCatalogCache();
            closeDialog();
            onSuccess?.();
            notify.success(editTarget ? "Actualizado correctamente" : "Creado correctamente");
        } catch (err) {
            if (handleAuthError(err)) return;
            if (err.response?.status === 422) {
                setDialogErrors(err.response.data.errors ?? {});
            } else {
                notify.error(getApiErrorMessage(err, "Error inesperado"));
            }
        } finally {
            setLoading(false);
        }
    };

    const handleDelete = async (row) => {
        setLoading(true);
        try {
            await axios.delete(`${apiEndpoint}/${row.id}`);
            clearCatalogCache();
            onSuccess?.();
            notify.success("Eliminado correctamente");
        } catch (err) {
            if (!handleAuthError(err)) {
                notify.error(getApiErrorMessage(err, "No se pudo eliminar"));
            }
        } finally {
            setLoading(false);
        }
    };

    const handleToggle = async (row) => {
        try {
            await axios.put(`${apiEndpoint}/${row.id}`, {
                ...row,
                is_active: !row.is_active,
            });
            clearCatalogCache();
            onSuccess?.();
        } catch (err) {
            if (!handleAuthError(err)) {
                notify.error(getApiErrorMessage(err, "No se pudo actualizar el estado"));
            }
        }
    };

    return {
        loading,
        dialogOpen,
        editTarget,
        dialogErrors,
        openCreate,
        openEdit,
        closeDialog,
        handleSubmit,
        handleDelete,
        handleToggle,
    };
}
