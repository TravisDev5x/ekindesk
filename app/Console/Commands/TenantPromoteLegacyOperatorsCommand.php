<?php

namespace App\Console\Commands;

use App\Models\Cliente;
use App\Services\OperatorScopeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TenantPromoteLegacyOperatorsCommand extends Command
{
    protected $signature = 'tenant:promote-legacy-operators
                            {--apply : Marca is_operator=true en candidatos legacy}
                            {--assign-orphan-clients : Asigna clients.operator_user_id NULL al operador promovido (un solo candidato)}';

    protected $description = 'Promueve admins legacy (manage_all sin operador MSP) a is_operator antes de desactivar TENANCY_LEGACY_MSP_WIDE_ACCESS';

    public function handle(OperatorScopeService $operatorScope): int
    {
        $candidates = $operatorScope->legacyOperatorCandidates();

        if ($candidates->isEmpty()) {
            $this->info('No hay usuarios candidatos a operador MSP legacy.');

            return self::SUCCESS;
        }

        $rows = $candidates->map(fn ($user) => [
            $user->id,
            $user->email,
            $user->employee_number,
            implode(', ', $user->getRoleNames()->all()),
        ])->all();

        $this->table(['ID', 'Correo', 'Empleado', 'Roles'], $rows);

        if (! $this->option('apply')) {
            $this->comment('Simulación. Usa --apply para marcar is_operator=true.');

            return self::SUCCESS;
        }

        if ($this->option('assign-orphan-clients') && $candidates->count() !== 1) {
            $this->error('--assign-orphan-clients requiere exactamente un candidato.');

            return self::FAILURE;
        }

        $promoted = 0;

        DB::transaction(function () use ($candidates, &$promoted) {
            foreach ($candidates as $user) {
                $user->update(['is_operator' => true, 'onboarding_completed' => true]);
                $promoted++;
            }

            if ($this->option('assign-orphan-clients') && $candidates->count() === 1) {
                $operatorId = (int) $candidates->first()->id;
                $updated = Cliente::query()
                    ->whereNull('operator_user_id')
                    ->update(['operator_user_id' => $operatorId]);
                $this->info("Clientes huérfanos actualizados: {$updated}");
            }
        });

        $this->info("Operadores promovidos: {$promoted}");
        $this->comment('En producción desactiva TENANCY_LEGACY_MSP_WIDE_ACCESS=false tras verificar accesos.');

        return self::SUCCESS;
    }
}
