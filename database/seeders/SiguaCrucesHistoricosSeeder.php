<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Sigua\Cruce;
use App\Models\Sigua\CruceResultado;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Cruces históricos de los últimos 3 meses para que la gráfica de tendencia del Dashboard se vea real.
 * Simula que las anomalías bajan: Mes 1 → 20, Mes 2 → 12, Mes 3 → 5.
 * Requiere: al menos 1 User, y preferiblemente SiguaAuditSeeder (empleados/cuentas) para nombres realistas.
 */
class SiguaCrucesHistoricosSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('SiguaCrucesHistoricosSeeder: 3 cruces (anomalías 20 → 12 → 5).');

        $ejecutadoPor = User::first()?->id ?? 1;
        $sistemasIncluidos = [['id' => 1, 'slug' => 'neotel'], ['id' => 2, 'slug' => 'ahevaa']];

        $meses = [
            ['anomalias' => 20, 'limpias' => 80],
            ['anomalias' => 12, 'limpias' => 88],
            ['anomalias' => 5,  'limpias' => 95],
        ];

        $now = now();
        foreach ($meses as $i => $meta) {
            $fecha = $now->copy()->subMonths(2 - $i)->startOfMonth()->addDays(rand(1, 15))->setHour(10)->setMinute(0);
            $total = $meta['anomalias'] + $meta['limpias'];
            $nombre = 'Cruce completo ' . $fecha->format('Y-m-d H:i');

            $cruce = Cruce::create([
                'import_id' => null,
                'tipo_cruce' => 'completo',
                'nombre' => $nombre,
                'sistemas_incluidos' => $sistemasIncluidos,
                'fecha_ejecucion' => $fecha,
                'total_analizados' => $total,
                'coincidencias' => $meta['limpias'],
                'sin_match' => $meta['anomalias'],
                'resultado_json' => [
                    'stats' => [
                        'ok_completo' => $meta['limpias'],
                        'cuenta_baja_pendiente' => min(5, $meta['anomalias']),
                        'cuenta_sin_rh' => min(5, max(0, $meta['anomalias'] - 5)),
                        'anomalia' => max(0, $meta['anomalias'] - 10),
                    ],
                ],
                'ejecutado_por' => $ejecutadoPor,
            ]);

            $sedes = ['Toluca', 'CDMX', 'Remoto'];
            $campanas = ['Ventas Inbound — Empresa A', 'Soporte Técnico — Empresa A', 'Retención — Empresa A'];
            $categoriasAnomalia = ['cuenta_baja_pendiente', 'cuenta_sin_rh', 'generico_sin_responsable', 'sin_cuenta_sistema', 'anomalia'];

            for ($k = 0; $k < $meta['anomalias']; $k++) {
                CruceResultado::create([
                    'cruce_id' => $cruce->id,
                    'empleado_rh_id' => $k % 3 === 0 ? null : null,
                    'num_empleado' => $k % 2 === 0 ? 'ZMB-' . (2000 + $k) : null,
                    'nombre_empleado' => 'Cuenta huérfana ' . ($k + 1) . ' — Sin RH',
                    'sede' => $sedes[$k % count($sedes)],
                    'campana' => $campanas[$k % count($campanas)],
                    'resultados_por_sistema' => [['sistema_id' => 1, 'slug' => 'neotel', 'tiene_cuenta' => true, 'identificador' => 'huerfana_' . ($k + 1)]],
                    'categoria' => $categoriasAnomalia[$k % count($categoriasAnomalia)],
                    'requiere_accion' => true,
                    'accion_sugerida' => 'Verificar con RH y vincular empleado o marcar como genérica/servicio.',
                ]);
            }
            for ($k = 0; $k < $meta['limpias']; $k++) {
                CruceResultado::create([
                    'cruce_id' => $cruce->id,
                    'empleado_rh_id' => $k + 1,
                    'num_empleado' => 'EMP-' . str_pad((string) (1000 + $k), 4, '0', STR_PAD_LEFT),
                    'nombre_empleado' => 'Empleado ' . (1000 + $k),
                    'sede' => $sedes[$k % count($sedes)],
                    'campana' => $campanas[$k % count($campanas)],
                    'resultados_por_sistema' => [['sistema_id' => 1, 'slug' => 'neotel', 'tiene_cuenta' => true]],
                    'categoria' => 'ok_completo',
                    'requiere_accion' => false,
                    'accion_sugerida' => null,
                ]);
            }
        }

        $this->command->info('SiguaCrucesHistoricosSeeder finalizado.');
    }
}
