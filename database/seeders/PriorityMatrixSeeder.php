<?php

namespace Database\Seeders;

use App\Models\ImpactLevel;
use App\Models\UrgencyLevel;
use App\Models\Priority;
use App\Models\PriorityMatrix;
use Illuminate\Database\Seeder;

class PriorityMatrixSeeder extends Seeder
{
    /**
     * Rellena impact_levels, urgency_levels y la matriz Impacto x Urgencia -> Prioridad.
     * Las prioridades (Crítica, Alta, Media, Baja) deben existir en la tabla priorities.
     */
    public function run(): void
    {
        $prioritiesByLevel = Priority::orderBy('level')->get()->keyBy('level');
        if ($prioritiesByLevel->isEmpty()) {
            $this->command->warn('No hay prioridades en la tabla priorities. Ejecuta antes un seeder que cree Crítica, Alta, Media, Baja.');
            return;
        }

        $impactLevels = [
            ['name' => 'Bajo', 'weight' => 1],
            ['name' => 'Medio', 'weight' => 2],
            ['name' => 'Alto', 'weight' => 3],
            ['name' => 'Crítico', 'weight' => 4],
        ];
        foreach ($impactLevels as $row) {
            ImpactLevel::firstOrCreate(
                ['name' => $row['name']],
                ['weight' => $row['weight'], 'is_active' => true]
            );
        }

        $urgencyLevels = [
            ['name' => 'Baja', 'weight' => 1],
            ['name' => 'Media', 'weight' => 2],
            ['name' => 'Alta', 'weight' => 3],
            ['name' => 'Crítica', 'weight' => 4],
        ];
        foreach ($urgencyLevels as $row) {
            UrgencyLevel::firstOrCreate(
                ['name' => $row['name']],
                ['weight' => $row['weight'], 'is_active' => true]
            );
        }

        $impacts = ImpactLevel::orderBy('weight')->get();
        $urgencies = UrgencyLevel::orderBy('weight')->get();

        foreach ($impacts as $impact) {
            foreach ($urgencies as $urgency) {
                $combined = $impact->weight + $urgency->weight;
                $level = (int) min(4, max(1, (int) ceil($combined / 2)));
                $priority = $prioritiesByLevel->get($level) ?? $prioritiesByLevel->first();
                if (!$priority) {
                    continue;
                }
                PriorityMatrix::updateOrCreate(
                    [
                        'impact_level_id' => $impact->id,
                        'urgency_level_id' => $urgency->id,
                    ],
                    ['priority_id' => $priority->id]
                );
            }
        }
    }
}
