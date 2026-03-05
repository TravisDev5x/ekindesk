<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Sede;
use Illuminate\Database\Seeder;

/**
 * Estructura organizacional para simular un Contact Center.
 * - 2 sedes (Toluca, CDMX).
 * - 5 campañas repartidas entre “empresas” (nombres indicativos).
 * Ejecutar después de FullDemoSeeder si se desean solo estas sedes/campañas adicionales.
 */
class EmpresaSedeSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('EmpresaSedeSeeder: 2 sedes y 5 campañas.');

        // 2 sedes (empresa lógica por nombre; no existe tabla empresas en el esquema actual)
        $sedes = [
            ['name' => 'Toluca',   'code' => 'TOLUCA',   'type' => 'physical'],
            ['name' => 'CDMX',     'code' => 'CDMX',     'type' => 'physical'],
        ];
        foreach ($sedes as $s) {
            Sede::firstOrCreate(
                ['name' => $s['name']],
                ['code' => $s['code'], 'type' => $s['type'], 'is_active' => true]
            );
        }

        // 5 campañas (2–3 “Empresa A”, 2–3 “Empresa B” por nombre)
        $campaigns = [
            ['name' => 'Ventas Inbound — Empresa A',  'is_active' => true],
            ['name' => 'Soporte Técnico — Empresa A', 'is_active' => true],
            ['name' => 'Retención — Empresa A',      'is_active' => true],
            ['name' => 'Ventas Outbound — Empresa B', 'is_active' => true],
            ['name' => 'Atención a Clientes — Empresa B', 'is_active' => true],
        ];
        foreach ($campaigns as $c) {
            Campaign::firstOrCreate(
                ['name' => $c['name']],
                ['is_active' => $c['is_active']]
            );
        }

        $this->command->info('EmpresaSedeSeeder finalizado.');
    }
}
