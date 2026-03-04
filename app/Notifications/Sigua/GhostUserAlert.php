<?php

namespace App\Notifications\Sigua;

use App\Models\Sigua\Cruce;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GhostUserAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Sigua\CruceResultado>  $fantasmas
     */
    public function __construct(
        public Cruce $cruce,
        public $fantasmas
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lista = $this->fantasmas->map(function ($r) {
            $sistemas = collect($r->resultados_por_sistema ?? [])
                ->filter(fn ($s) => ! empty($s['tiene_cuenta']))
                ->pluck('slug')
                ->unique()
                ->values()
                ->all();
            return sprintf(
                '%s (Nº %s) — Sistemas: %s',
                $r->nombre_empleado ?? 'Sin nombre',
                $r->num_empleado ?? '—',
                implode(', ', $sistemas) ?: '—'
            );
        })->implode("\n");

        return (new MailMessage)
            ->subject('CRÍTICO SIGUA: Usuarios fantasma detectados (bajas con accesos activos)')
            ->greeting('Alerta de ciberseguridad')
            ->line('CRÍTICO: Se han detectado accesos activos de personal con estatus de BAJA. Se requiere revocación inmediata de privilegios.')
            ->line('')
            ->line($lista)
            ->action('Ver cruce en SIGUA', url('/sigua/cruces?categoria=cuenta_baja_pendiente'))
            ->line('Cruce ejecutado: ' . $this->cruce->fecha_ejecucion?->format('d/m/Y H:i'));
    }

    public function toArray(object $notifiable): array
    {
        $detalle = $this->fantasmas->map(fn ($r) => [
            'nombre' => $r->nombre_empleado,
            'num_empleado' => $r->num_empleado,
            'sistemas' => collect($r->resultados_por_sistema ?? [])
                ->filter(fn ($s) => ! empty($s['tiene_cuenta']))
                ->pluck('slug')
                ->unique()
                ->values()
                ->all(),
        ])->all();

        return [
            'kind' => 'sigua_ghost_user_alert',
            'message' => 'CRÍTICO: Se han detectado accesos activos de personal con estatus de BAJA. Se requiere revocación inmediata de privilegios.',
            'cruce_id' => $this->cruce->id,
            'cantidad' => $this->fantasmas->count(),
            'detalle' => $detalle,
            'link' => '/sigua/cruces?categoria=cuenta_baja_pendiente',
        ];
    }
}
