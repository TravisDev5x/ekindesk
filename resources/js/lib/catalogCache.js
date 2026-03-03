import axios from "@/lib/axios";

const CACHE_KEY_PREFIX = "catalogs.cache.v4";
const TTL_MS = 15 * 60 * 1000; // 15 minutos

/**
 * Normaliza módulos a string para clave de caché: array o string -> string ordenada.
 * @param {string[]|string|null} modules - ej. ['core','tickets'] o 'core,tickets' o null (full)
 * @returns {string}
 */
function modulesToCacheSuffix(modules) {
    if (modules == null || (Array.isArray(modules) && modules.length === 0)) {
        return "full";
    }
    const list = Array.isArray(modules)
        ? [...modules].sort()
        : String(modules)
              .split(",")
              .map((s) => s.trim())
              .filter(Boolean)
              .sort();
    return list.length ? list.join(",") : "full";
}

function getCacheKey(modules) {
    return `${CACHE_KEY_PREFIX}.${modulesToCacheSuffix(modules)}`;
}

function getCached(modules) {
    if (typeof sessionStorage === "undefined") return null;
    const key = getCacheKey(modules);
    const raw = sessionStorage.getItem(key);
    if (!raw) return null;
    try {
        const parsed = JSON.parse(raw);
        if (!parsed?.ts || !parsed?.data) return null;
        const isValid = Date.now() - parsed.ts < TTL_MS;
        return isValid ? parsed.data : null;
    } catch (_) {
        return null;
    }
}

function setCached(data, modules) {
    if (typeof sessionStorage === "undefined") return;
    try {
        const key = getCacheKey(modules);
        sessionStorage.setItem(key, JSON.stringify({ ts: Date.now(), data }));
    } catch (_) {}
}

/**
 * Carga catálogos desde API (con caché en sessionStorage).
 * @param {boolean} [forceFresh=false] - si true, ignora caché y pide con ?nocache=1
 * @param {string[]|string|null} [modules=null] - módulos a cargar: 'core', 'tickets', 'incidents', 'timedesk', 'sigua'. null = todos (compatibilidad)
 * @returns {Promise<object>} payload de catálogos
 */
export async function loadCatalogs(forceFresh = false, modules = null) {
    if (!forceFresh) {
        const cached = getCached(modules);
        if (cached) return cached;
    }
    const params = {};
    if (forceFresh) params.nocache = "1";
    if (modules != null) {
        params.modules = Array.isArray(modules) ? modules.join(",") : modules;
    }
    const url = "/api/catalogs";
    const { data } = await axios.get(url, { params });
    if (!forceFresh) setCached(data, modules);
    return data;
}

/**
 * Limpia toda la caché de catálogos (todas las claves que empiezan por el prefijo).
 */
export function clearCatalogCache() {
    if (typeof sessionStorage === "undefined") return;
    try {
        const keys = [];
        for (let i = 0; i < sessionStorage.length; i++) {
            const k = sessionStorage.key(i);
            if (k && k.startsWith(CACHE_KEY_PREFIX)) keys.push(k);
        }
        keys.forEach((k) => sessionStorage.removeItem(k));
    } catch (_) {}
}
