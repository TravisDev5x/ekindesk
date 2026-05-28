<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed de datos mínimos de la aplicación.
     *
     * - Catálogos base (campañas, áreas, puestos, sedes, tickets).
     * - 4 roles principales y permisos de helpdesk.
     * - 1 usuario administrador.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            FullDemoSeeder::class,       // Configuración mínima (catálogos, roles, admin)
            PriorityMatrixSeeder::class, // Matriz Impacto x Urgencia -> Prioridad (requiere priorities)
            EmpresaSedeSeeder::class,    // 2 sedes (Toluca, CDMX) y 5 campañas
            FakerFullSeeder::class,      // Admin + usuarios, tickets e incidencias (Faker)
        ]);
    }
}