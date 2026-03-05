/**
 * API del hub principal (página de inicio: SIGUA + RESOLBEB).
 */
import axios from "@/lib/axios";

export async function getHubSummary() {
  try {
    const response = await axios.get("/api/dashboard/hub-summary", {
      withCredentials: true,
    });
    return { data: response.data, error: null };
  } catch (err) {
    const status = err?.response?.status;
    const message = err?.response?.data?.message;
    let errorMessage = "Error al cargar el resumen.";
    if (status === 401) {
      errorMessage = "Sesión expirada. Inicia sesión de nuevo.";
    } else if (typeof message === "string" && message) {
      errorMessage = message;
    } else if (err?.message) {
      errorMessage = err.message;
    }
    return { data: null, error: errorMessage };
  }
}
