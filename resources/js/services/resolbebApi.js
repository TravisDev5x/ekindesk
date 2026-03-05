/**
 * API del dashboard operativo RESOLBEB (Helpdesk).
 * Usa la instancia axios del proyecto (withCredentials para Sanctum).
 */
import axios from "@/lib/axios";

const PREFIX = "/api/tickets";

/**
 * Obtiene los datos del dashboard operativo.
 * @param {Object} [filters] - { sede_id, assigned_user_id }
 * @returns {Promise<{ data: Object | null, error: string | null }>}
 */
export async function getResolbebDashboard(filters = {}) {
  try {
    const params = {};
    if (filters.sede_id != null && filters.sede_id !== "") params.sede_id = filters.sede_id;
    if (filters.assigned_user_id != null && filters.assigned_user_id !== "") params.assigned_user_id = filters.assigned_user_id;

    const response = await axios.get(`${PREFIX}/dashboard-operativo`, {
      params,
      withCredentials: true,
    });
    return { data: response.data, error: null };
  } catch (err) {
    const status = err?.response?.status;
    // Re-lanzar 401/419 para que el Dashboard (modo Wallboard) pueda hacer auto-recuperación
    if (status === 401 || status === 419) {
      throw err;
    }
    const message = err?.response?.data?.message;
    let errorMessage = "Error al cargar el dashboard.";
    if (typeof message === "string" && message) {
      errorMessage = message;
    } else if (err?.message) {
      errorMessage = err.message;
    }
    return { data: null, error: errorMessage };
  }
}
