<?php

namespace App\Services;

use App\Models\Area;
use App\Models\Campaign;
use App\Models\ImpactLevel;
use App\Models\IncidentSeverity;
use App\Models\IncidentStatus;
use App\Models\IncidentType;
use App\Models\Position;
use App\Models\Priority;
use App\Models\TicketState;
use App\Models\TicketType;
use App\Models\UrgencyLevel;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clona catálogos de plataforma (operator_user_id NULL) al operador MSP.
 *
 * Compatible con unicidad global legacy en name/code: si no puede insertar una copia
 * porque ya existe la fila de plataforma, el operador sigue viéndola vía scope (herencia).
 */
class OperatorCatalogSeedService
{
    /** @var array<string, array{model: class-string, match: list<string>, attributes: list<string>}> */
    private const TABLE_DEFINITIONS = [
        'campaigns' => [
            'model' => Campaign::class,
            'match' => ['name'],
            'attributes' => ['name', 'is_active'],
        ],
        'areas' => [
            'model' => Area::class,
            'match' => ['name'],
            'attributes' => ['name', 'is_active'],
        ],
        'positions' => [
            'model' => Position::class,
            'match' => ['name'],
            'attributes' => ['name', 'is_active'],
        ],
        'priorities' => [
            'model' => Priority::class,
            'match' => ['name'],
            'attributes' => ['name', 'level', 'is_active'],
        ],
        'ticket_states' => [
            'model' => TicketState::class,
            'match' => ['code'],
            'attributes' => ['name', 'code', 'is_active', 'is_final'],
        ],
        'impact_levels' => [
            'model' => ImpactLevel::class,
            'match' => ['name'],
            'attributes' => ['name', 'weight', 'is_active'],
        ],
        'urgency_levels' => [
            'model' => UrgencyLevel::class,
            'match' => ['name'],
            'attributes' => ['name', 'weight', 'is_active'],
        ],
        'incident_types' => [
            'model' => IncidentType::class,
            'match' => ['code'],
            'attributes' => ['name', 'code', 'is_active'],
        ],
        'incident_severities' => [
            'model' => IncidentSeverity::class,
            'match' => ['code'],
            'attributes' => ['name', 'code', 'level', 'is_active'],
        ],
        'incident_statuses' => [
            'model' => IncidentStatus::class,
            'match' => ['code'],
            'attributes' => ['name', 'code', 'is_active', 'is_final'],
        ],
        'ticket_types' => [
            'model' => TicketType::class,
            'match' => ['code'],
            'attributes' => ['name', 'code', 'is_active'],
        ],
    ];

    /** @var array<string, list<array<string, mixed>>> */
    private const FALLBACK_TEMPLATE = [
        'campaigns' => [
            ['name' => 'General', 'is_active' => true],
        ],
        'areas' => [
            ['name' => 'Sistemas / TI', 'is_active' => true],
            ['name' => 'Soporte', 'is_active' => true],
            ['name' => 'Operaciones', 'is_active' => true],
        ],
        'positions' => [
            ['name' => 'Usuario Final', 'is_active' => true],
            ['name' => 'Soporte', 'is_active' => true],
            ['name' => 'Supervisor', 'is_active' => true],
        ],
        'priorities' => [
            ['name' => 'Crítica', 'level' => 1, 'is_active' => true],
            ['name' => 'Alta', 'level' => 2, 'is_active' => true],
            ['name' => 'Media', 'level' => 3, 'is_active' => true],
            ['name' => 'Baja', 'level' => 4, 'is_active' => true],
        ],
        'ticket_states' => [
            ['name' => 'Abierto', 'code' => 'abierto', 'is_active' => true, 'is_final' => false],
            ['name' => 'En progreso', 'code' => 'en_progreso', 'is_active' => true, 'is_final' => false],
            ['name' => 'En espera', 'code' => 'en_espera', 'is_active' => true, 'is_final' => false],
            ['name' => 'Resuelto', 'code' => 'resuelto', 'is_active' => true, 'is_final' => false],
            ['name' => 'Cerrado', 'code' => 'cerrado', 'is_active' => true, 'is_final' => true],
            ['name' => 'Cancelado', 'code' => 'cancelado', 'is_active' => true, 'is_final' => true],
        ],
        'impact_levels' => [
            ['name' => 'Bajo', 'weight' => 1, 'is_active' => true],
            ['name' => 'Medio', 'weight' => 2, 'is_active' => true],
            ['name' => 'Alto', 'weight' => 3, 'is_active' => true],
            ['name' => 'Crítico', 'weight' => 4, 'is_active' => true],
        ],
        'urgency_levels' => [
            ['name' => 'Baja', 'weight' => 1, 'is_active' => true],
            ['name' => 'Media', 'weight' => 2, 'is_active' => true],
            ['name' => 'Alta', 'weight' => 3, 'is_active' => true],
            ['name' => 'Crítica', 'weight' => 4, 'is_active' => true],
        ],
        'incident_types' => [
            ['name' => 'Falla de servicio', 'code' => 'falla_de_servicio', 'is_active' => true],
            ['name' => 'Seguridad', 'code' => 'seguridad', 'is_active' => true],
            ['name' => 'Hardware', 'code' => 'hardware', 'is_active' => true],
            ['name' => 'Software', 'code' => 'software', 'is_active' => true],
        ],
        'incident_severities' => [
            ['name' => 'Crítica', 'code' => 'P1', 'level' => 1, 'is_active' => true],
            ['name' => 'Alta', 'code' => 'P2', 'level' => 2, 'is_active' => true],
            ['name' => 'Media', 'code' => 'P3', 'level' => 3, 'is_active' => true],
            ['name' => 'Baja', 'code' => 'P4', 'level' => 4, 'is_active' => true],
        ],
        'incident_statuses' => [
            ['name' => 'Reportado', 'code' => 'reportado', 'is_active' => true, 'is_final' => false],
            ['name' => 'En investigación', 'code' => 'en_investigacion', 'is_active' => true, 'is_final' => false],
            ['name' => 'Resuelto', 'code' => 'resuelto', 'is_active' => true, 'is_final' => false],
            ['name' => 'Cerrado', 'code' => 'cerrado', 'is_active' => true, 'is_final' => true],
        ],
        'ticket_types' => [
            ['name' => 'Falla de equipo', 'code' => 'falla_de_equipo', 'is_active' => true, 'areas' => ['Soporte']],
            ['name' => 'Acceso a sistema', 'code' => 'acceso_a_sistema', 'is_active' => true, 'areas' => ['Sistemas / TI', 'Soporte']],
        ],
    ];

    public function __construct(
        protected OperatorCatalogScopeService $catalogScope
    ) {}

    /**
     * @param  list<string>|null  $onlyTables
     * @return array{created: int, skipped_existing: int, skipped_inherited: int, failed: int, by_table: array<string, array{created: int, skipped_existing: int, skipped_inherited: int, failed: int}>}
     */
    public function seedForOperator(
        User $operator,
        bool $dryRun = false,
        ?int $clientId = null,
        ?array $onlyTables = null
    ): array {
        $this->assertOperatorEligible($operator, $clientId);

        $tables = $this->resolveTables($onlyTables);
        $summary = $this->emptySummary($tables);

        $run = function () use ($operator, $clientId, $tables, &$summary, $dryRun) {
            foreach ($tables as $table) {
                if ($table === 'ticket_types') {
                    $this->seedTicketTypes($operator, $clientId, $dryRun, $summary);

                    continue;
                }

                $this->seedSimpleTable($table, $operator, $clientId, $dryRun, $summary);
            }
        };

        if ($dryRun) {
            $run();
        } else {
            DB::transaction($run);
        }

        $summary['created'] = (int) collect($summary['by_table'])->sum('created');
        $summary['skipped_existing'] = (int) collect($summary['by_table'])->sum('skipped_existing');
        $summary['skipped_inherited'] = (int) collect($summary['by_table'])->sum('skipped_inherited');
        $summary['failed'] = (int) collect($summary['by_table'])->sum('failed');

        return $summary;
    }

    public function resolveOperator(int $operatorUserId): User
    {
        $user = User::query()->find($operatorUserId);

        if (! $user) {
            throw new \InvalidArgumentException("No existe el usuario #{$operatorUserId}.");
        }

        return $user;
    }

    private function assertOperatorEligible(User $operator, ?int $clientId): void
    {
        if ($clientId !== null && ! Schema::hasColumn('priorities', 'client_id')) {
            throw new \InvalidArgumentException('client_id en catálogos no disponible (migración pendiente).');
        }

        if ($this->catalogScope->usesPerClientCatalogInPortal() && $clientId === null) {
            throw new \InvalidArgumentException(
                'TENANCY_CATALOG_PER_CLIENT=true requiere --client={client_id} para sembrar catálogos por empresa.'
            );
        }

        $ownsClients = DB::table('clients')->where('operator_user_id', $operator->id)->exists();

        if (! $operator->is_operator && ! $ownsClients) {
            throw new \InvalidArgumentException(
                "El usuario #{$operator->id} no es operador MSP (is_operator) ni tiene clientes asignados."
            );
        }
    }

    /**
     * @param  list<string>|null  $onlyTables
     * @return list<string>
     */
    private function resolveTables(?array $onlyTables): array
    {
        $default = array_values(array_filter(
            array_keys(self::TABLE_DEFINITIONS),
            fn (string $table) => Schema::hasTable($table)
                && Schema::hasColumn($table, 'operator_user_id')
        ));

        if ($onlyTables === null || $onlyTables === []) {
            return $default;
        }

        $unknown = array_diff($onlyTables, $default);
        if ($unknown !== []) {
            throw new \InvalidArgumentException(
                'Tablas no válidas o sin migración de operador: '.implode(', ', $unknown)
            );
        }

        return array_values(array_intersect($default, $onlyTables));
    }

    /**
     * @param  list<string>  $tables
     * @return array{created: int, skipped_existing: int, skipped_inherited: int, failed: int, by_table: array<string, array{created: int, skipped_existing: int, skipped_inherited: int, failed: int}>}
     */
    private function emptySummary(array $tables): array
    {
        $byTable = [];
        foreach ($tables as $table) {
            $byTable[$table] = [
                'created' => 0,
                'skipped_existing' => 0,
                'skipped_inherited' => 0,
                'failed' => 0,
            ];
        }

        return [
            'created' => 0,
            'skipped_existing' => 0,
            'skipped_inherited' => 0,
            'failed' => 0,
            'by_table' => $byTable,
        ];
    }

    /**
     * @param  array{created: int, skipped_existing: int, skipped_inherited: int, failed: int, by_table: array<string, array{created: int, skipped_existing: int, skipped_inherited: int, failed: int}>}  $summary
     */
    private function seedSimpleTable(
        string $table,
        User $operator,
        ?int $clientId,
        bool $dryRun,
        array &$summary
    ): void {
        $definition = self::TABLE_DEFINITIONS[$table];
        $modelClass = $definition['model'];

        foreach ($this->collectSourceRows($table, $modelClass, $clientId) as $source) {
            $attributes = $this->extractAttributes($source, $definition['attributes']);
            $match = $this->matchAttributes($attributes, $definition['match']);

            if ($this->operatorRowExists($modelClass, $operator->id, $clientId, $match)) {
                $summary['by_table'][$table]['skipped_existing']++;

                continue;
            }

            if ($this->platformRowExistsGlobally($table, $attributes, $definition['match'])) {
                $summary['by_table'][$table]['skipped_inherited']++;

                continue;
            }

            if ($dryRun) {
                $summary['by_table'][$table]['created']++;

                continue;
            }

            try {
                $modelClass::create(array_merge($attributes, $this->tenantAttributes($operator->id, $clientId)));
                $summary['by_table'][$table]['created']++;
            } catch (QueryException $e) {
                if ($this->isUniqueConstraintViolation($e)) {
                    $summary['by_table'][$table]['skipped_inherited']++;
                } else {
                    $summary['by_table'][$table]['failed']++;
                }
            }
        }
    }

    /**
     * @param  array{created: int, skipped_existing: int, skipped_inherited: int, failed: int, by_table: array<string, array{created: int, skipped_existing: int, skipped_inherited: int, failed: int}>}  $summary
     */
    private function seedTicketTypes(User $operator, ?int $clientId, bool $dryRun, array &$summary): void
    {
        $table = 'ticket_types';

        foreach ($this->collectSourceRows($table, TicketType::class, $clientId) as $source) {
            $attributes = $this->extractAttributes($source, self::TABLE_DEFINITIONS[$table]['attributes']);
            $match = $this->matchAttributes($attributes, ['code']);
            $areaNames = $this->resolveTicketTypeAreaNames($source);

            if ($this->operatorRowExists(TicketType::class, $operator->id, $clientId, $match)) {
                $summary['by_table'][$table]['skipped_existing']++;

                continue;
            }

            if ($this->platformRowExistsGlobally($table, $attributes, ['code'])) {
                $summary['by_table'][$table]['skipped_inherited']++;

                continue;
            }

            if ($dryRun) {
                $summary['by_table'][$table]['created']++;

                continue;
            }

            try {
                $type = TicketType::create(array_merge($attributes, $this->tenantAttributes($operator->id, $clientId)));
                $areaIds = $this->resolveOperatorAreaIds($operator->id, $clientId, $areaNames);
                if ($areaIds !== []) {
                    $type->areas()->sync($areaIds);
                }
                $summary['by_table'][$table]['created']++;
            } catch (QueryException $e) {
                if ($this->isUniqueConstraintViolation($e)) {
                    $summary['by_table'][$table]['skipped_inherited']++;
                } else {
                    $summary['by_table'][$table]['failed']++;
                }
            }
        }
    }

    /**
     * Filas de plataforma + plantilla mínima (sin duplicar claves de match).
     *
     * @param  class-string  $modelClass
     * @return list<array<string, mixed>|object>
     */
    private function collectSourceRows(string $table, string $modelClass, ?int $clientId): array
    {
        $definition = self::TABLE_DEFINITIONS[$table];
        $rows = [];
        $seenKeys = [];

        foreach ($this->platformSources($modelClass, $clientId) as $platformRow) {
            $payload = is_array($platformRow)
                ? $platformRow
                : $this->sourcePayload($platformRow, $definition, $table);
            $key = $this->matchKey(
                $this->extractAttributes($payload, $definition['attributes']),
                $definition['match']
            );
            $rows[] = $payload;
            $seenKeys[$key] = true;
        }

        foreach (self::FALLBACK_TEMPLATE[$table] ?? [] as $templateRow) {
            $key = $this->matchKey($templateRow, $definition['match']);
            if (! isset($seenKeys[$key])) {
                $rows[] = $templateRow;
                $seenKeys[$key] = true;
            }
        }

        return $rows;
    }

    /**
     * @param  list<string>  $matchColumns
     */
    private function matchKey(array $attributes, array $matchColumns): string
    {
        return implode('|', array_map(
            fn (string $column) => $column.':'.($attributes[$column] ?? ''),
            $matchColumns
        ));
    }

    /**
     * @param  array{attributes: list<string>, match: list<string>}  $definition
     * @return array<string, mixed>
     */
    private function sourcePayload(object $row, array $definition, string $table): array
    {
        $payload = $this->extractAttributes($row, $definition['attributes']);

        if ($table === 'ticket_types' && method_exists($row, 'areas')) {
            $payload['areas'] = $row->areas()->pluck('areas.name')->all();
        }

        return $payload;
    }

    /**
     * @param  class-string  $modelClass
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    private function platformSources(string $modelClass, ?int $clientId)
    {
        $table = (new $modelClass)->getTable();
        $query = $modelClass::query();

        if ($this->catalogScope->usesPerClientCatalogInPortal() && $clientId !== null) {
            $query->where(function ($q) use ($table, $clientId) {
                $q->where(function ($inner) use ($table) {
                    $inner->whereNull('operator_user_id');
                    if (Schema::hasColumn($table, 'client_id')) {
                        $inner->whereNull('client_id');
                    }
                })->orWhere(function ($inner) use ($clientId) {
                    $inner->whereNull('operator_user_id')->where('client_id', $clientId);
                });
            });
        } else {
            $query->whereNull('operator_user_id');
            if (Schema::hasColumn($table, 'client_id')) {
                $query->whereNull('client_id');
            }
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $source
     * @param  list<string>  $columns
     * @return array<string, mixed>
     */
    private function extractAttributes(array|object $source, array $columns): array
    {
        $row = is_array($source) ? $source : $source->toArray();
        $attributes = [];

        foreach ($columns as $column) {
            if (array_key_exists($column, $row)) {
                $attributes[$column] = $row[$column];
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $matchColumns
     * @return array<string, mixed>
     */
    private function matchAttributes(array $attributes, array $matchColumns): array
    {
        return collect($matchColumns)
            ->filter(fn (string $column) => array_key_exists($column, $attributes))
            ->mapWithKeys(fn (string $column) => [$column => $attributes[$column]])
            ->all();
    }

    /**
     * @param  class-string  $modelClass
     * @param  array<string, mixed>  $match
     */
    private function operatorRowExists(string $modelClass, int $operatorUserId, ?int $clientId, array $match): bool
    {
        $query = $modelClass::query()
            ->where('operator_user_id', $operatorUserId);

        if ($clientId !== null && Schema::hasColumn((new $modelClass)->getTable(), 'client_id')) {
            $query->where('client_id', $clientId);
        } elseif (Schema::hasColumn((new $modelClass)->getTable(), 'client_id')) {
            $query->whereNull('client_id');
        }

        foreach ($match as $column => $value) {
            $query->where($column, $value);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $matchColumns
     */
    private function platformRowExistsGlobally(string $table, array $attributes, array $matchColumns): bool
    {
        $query = DB::table($table)->whereNull('operator_user_id');

        if (Schema::hasColumn($table, 'client_id')) {
            $query->whereNull('client_id');
        }

        foreach ($this->matchAttributes($attributes, $matchColumns) as $column => $value) {
            $query->where($column, $value);
        }

        return $query->exists();
    }

    /** @return array{operator_user_id: int, client_id?: int|null} */
    private function tenantAttributes(int $operatorUserId, ?int $clientId): array
    {
        $attrs = ['operator_user_id' => $operatorUserId];

        if ($clientId !== null) {
            $attrs['client_id'] = $clientId;
        } elseif (Schema::hasColumn('priorities', 'client_id')) {
            $attrs['client_id'] = null;
        }

        return $attrs;
    }

    /**
     * @param  array<string, mixed>|object  $source
     * @return list<string>
     */
    private function resolveTicketTypeAreaNames(array|object $source): array
    {
        if (is_array($source) && isset($source['areas']) && is_array($source['areas'])) {
            return array_values($source['areas']);
        }

        if (is_object($source) && method_exists($source, 'areas')) {
            return $source->areas()->pluck('areas.name')->all();
        }

        return [];
    }

    /**
     * @param  list<string>  $areaNames
     * @return list<int>
     */
    private function resolveOperatorAreaIds(int $operatorUserId, ?int $clientId, array $areaNames): array
    {
        if ($areaNames === []) {
            return [];
        }

        $query = Area::query()->where('operator_user_id', $operatorUserId);

        if ($clientId !== null && Schema::hasColumn('areas', 'client_id')) {
            $query->where('client_id', $clientId);
        } else {
            $query->whereNull('client_id');
        }

        $operatorAreas = $query->whereIn('name', $areaNames)->pluck('id', 'name');

        if ($operatorAreas->count() === count($areaNames)) {
            return $operatorAreas->values()->all();
        }

        $fallback = Area::query()
            ->whereNull('operator_user_id')
            ->whereNull('client_id')
            ->whereIn('name', $areaNames)
            ->pluck('id', 'name');

        return collect($areaNames)
            ->map(fn (string $name) => $operatorAreas->get($name) ?? $fallback->get($name))
            ->filter()
            ->values()
            ->all();
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;

        if (in_array($sqlState, ['23000', '23505'], true) || $driverCode === 1062 || $driverCode === 19) {
            return true;
        }

        return str_contains(strtolower($exception->getMessage()), 'unique constraint failed')
            || str_contains(strtolower($exception->getMessage()), 'duplicate');
    }
}
