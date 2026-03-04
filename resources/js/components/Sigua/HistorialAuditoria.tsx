import { useEffect, useState } from "react";
import { getHistorialAuditoria } from "@/services/siguaApi";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import { ScrollArea } from "@/components/ui/scroll-area";
import type { AuditLog } from "@/types/sigua";
import { History, Loader2, UserPlus, Pencil, Trash2, RotateCcw } from "lucide-react";

const ACTION_ICONS: Record<string, React.ElementType> = {
  created: UserPlus,
  updated: Pencil,
  deleted: Trash2,
  restored: RotateCcw,
};

/** Convierte claves de BD a etiquetas legibles (ej. estado → Estado, fecha_vencimiento → Fecha de vencimiento). */
function labelCampo(key: string): string {
  const map: Record<string, string> = {
    estado: "Estado",
    usuario_cuenta: "Usuario / Cuenta",
    nombre_cuenta: "Nombre de cuenta",
    sede_id: "Sede",
    system_id: "Sistema",
    campaign_id: "Campaña",
    isla: "Isla",
    perfil: "Perfil",
    ou_ad: "OU AD",
    empleado_rh_id: "Empleado RH",
    tipo: "Tipo",
    gerente_user_id: "Gerente",
    fecha_firma: "Fecha de firma",
    fecha_vencimiento: "Fecha de vencimiento",
    archivo_firmado: "Archivo firmado",
    observaciones: "Observaciones",
    created_by: "Creado por",
    name: "Nombre",
    slug: "Slug",
    description: "Descripción",
    activo: "Activo",
    fecha_incidente: "Fecha del incidente",
    descripcion: "Descripción",
    ip_origen: "IP origen",
    ca01_id: "CA-01",
    agente_identificado: "Agente identificado",
    resolucion: "Resolución",
    reportado_por: "Reportado por",
    asignado_a: "Asignado a",
    bitacora_id: "Bitácora",
    datos_log: "Datos de log",
    account_id: "Cuenta",
    system_id: "Sistema",
  };
  if (map[key]) return map[key];
  return key
    .replace(/_/g, " ")
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

function formatValor(value: unknown): string {
  if (value === null || value === undefined) return "—";
  if (typeof value === "boolean") return value ? "Sí" : "No";
  if (typeof value === "string" && /^\d{4}-\d{2}-\d{2}/.test(value)) {
    try {
      return new Date(value).toLocaleDateString("es-ES", {
        day: "numeric",
        month: "long",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
      });
    } catch {
      return value;
    }
  }
  return String(value);
}

function formatFecha(createdAt: string): string {
  try {
    return new Date(createdAt).toLocaleDateString("es-ES", {
      day: "numeric",
      month: "long",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  } catch {
    return createdAt;
  }
}

function descripcionAccion(log: AuditLog): React.ReactNode {
  const userName = log.user?.name ?? "Sistema";
  const oldV = log.old_values ?? {};
  const newV = log.new_values ?? {};

  switch (log.action) {
    case "created":
      return (
        <>
          <span className="font-medium">Registro creado</span> por {userName}.
        </>
      );
    case "deleted":
      return (
        <>
          <span className="font-medium">Registro eliminado</span> por {userName}.
        </>
      );
    case "restored":
      return (
        <>
          <span className="font-medium">Registro restaurado</span> por {userName}.
        </>
      );
    case "updated": {
      const keys = Object.keys(newV).filter((k) => !["updated_at", "created_at"].includes(k));
      if (keys.length === 0) return null;
      return (
        <>
          <span className="font-medium">{userName}</span> actualizó:
        </>
      );
    }
    default:
      return userName;
  }
}

function detalleCambios(log: AuditLog): React.ReactNode {
  if (log.action !== "updated") return null;
  const oldV = log.old_values ?? {};
  const newV = log.new_values ?? {};
  const keys = Object.keys(newV).filter((k) => !["updated_at", "created_at"].includes(k));
  if (keys.length === 0) return null;

  return (
    <ul className="mt-2 space-y-1 text-sm text-muted-foreground list-none pl-0">
      {keys.map((key) => {
        const oldVal = oldV[key];
        const newVal = newV[key];
        const label = labelCampo(key);
        return (
          <li key={key} className="flex flex-wrap gap-1 items-baseline">
            <span className="text-foreground/90">{label}:</span>
            <span>{formatValor(oldVal)}</span>
            <span>→</span>
            <span className="font-medium text-foreground">{formatValor(newVal)}</span>
          </li>
        );
      })}
    </ul>
  );
}

export interface HistorialAuditoriaProps {
  modelo: string;
  id: number | string;
  title?: string;
  description?: string;
}

export function HistorialAuditoria({ modelo, id, title = "Historial de cambios", description }: HistorialAuditoriaProps) {
  const [logs, setLogs] = useState<AuditLog[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!modelo || id === "" || id === undefined) return;
    setLoading(true);
    setError(null);
    getHistorialAuditoria(modelo, id)
      .then((res) => {
        if (res.error) setError(res.error);
        else setLogs(res.data ?? []);
      })
      .finally(() => setLoading(false));
  }, [modelo, id]);

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base flex items-center gap-2">
          <History className="h-4 w-4" />
          {title}
        </CardTitle>
        {description && <CardDescription>{description}</CardDescription>}
      </CardHeader>
      <CardContent>
        {loading ? (
          <div className="flex items-center gap-2 text-muted-foreground">
            <Loader2 className="h-4 w-4 animate-spin" />
            Cargando historial…
          </div>
        ) : error ? (
          <p className="text-sm text-destructive">{error}</p>
        ) : logs.length === 0 ? (
          <p className="text-sm text-muted-foreground">No hay registros de auditoría.</p>
        ) : (
          <ScrollArea className="h-[320px] pr-4">
            <div className="relative space-y-0">
              {/* Línea vertical de la timeline */}
              <div
                className="absolute left-[11px] top-2 bottom-2 w-px bg-border"
                aria-hidden
              />
              {logs.map((log) => {
                const Icon = ACTION_ICONS[log.action] ?? Pencil;
                return (
                  <div
                    key={log.id}
                    className="relative flex gap-4 pb-4 last:pb-0"
                  >
                    <div className="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-background bg-muted text-muted-foreground">
                      <Icon className="h-3 w-3" />
                    </div>
                    <div className="flex-1 min-w-0 space-y-0.5">
                      <p className="text-sm">
                        {descripcionAccion(log)}
                        {log.action === "updated" && detalleCambios(log)}
                      </p>
                      <p className="text-xs text-muted-foreground">
                        {formatFecha(log.created_at)}
                      </p>
                    </div>
                  </div>
                );
              })}
            </div>
          </ScrollArea>
        )}
      </CardContent>
    </Card>
  );
}
