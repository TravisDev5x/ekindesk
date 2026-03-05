<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\Sede;
use App\Models\User;
use App\Models\Sigua\Cruce;
use App\Models\Sigua\CruceResultado;
use App\Models\Sigua\CuentaGenerica;
use App\Models\Sigua\EmpleadoRh;
use App\Models\Sigua\FormatoCA01;
use App\Models\Sigua\Sistema;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as FakerFactory;

/**
 * Datos de auditoría SIGUA: empleados RH, cuentas (ideal, zombie, huérfanas, genéricas) y CA-01.
 * Requiere: EmpresaSedeSeeder (o sedes/campañas), SiguaPermissionsSeeder (sistemas), al menos 1 User.
 * - 35 empleados activos con cuentas nominales vinculadas (ideal).
 * - 5 empleados Baja con cuentas aún activas (zombie).
 * - 5 cuentas huérfanas (sin empleado_rh_id).
 * - 10 cuentas genéricas (ventas_isla_01, soporte_02, …).
 * De las 10 genéricas: 4 CA-01 vigente, 3 vencido (>10 días), 3 por vencer (próximos 5 días).
 */
class SiguaAuditSeeder extends Seeder
{
    private \Faker\Generator $faker;

    private int $neotelId;

    private int $ahevaaId;

    /** @var array<int, Sede> */
    private array $sedes = [];

    /** @var array<int, Campaign> */
    private array $campaigns = [];

    private User $gerenteUser;

    public function __construct()
    {
        $this->faker = FakerFactory::create('es_MX');
    }

    public function run(): void
    {
        $this->command->info('SiguaAuditSeeder: empleados, cuentas (ideal/zombie/huérfanas/genéricas) y CA-01.');

        $this->resolveDependencies();
        if (!$this->neotelId || !$this->ahevaaId || empty($this->sedes) || empty($this->campaigns)) {
            $this->command->error('Requiere: SiguaPermissionsSeeder (Neotel, Ahevaa), sedes y campañas (EmpresaSedeSeeder/FullDemoSeeder), y al menos 1 User.');
            return;
        }

        DB::transaction(function () {
            $empleadosIdeal = $this->seedEmpleadosIdeal(35);
            $empleadosZombie = $this->seedEmpleadosZombie(5);
            $this->seedCuentasHuérfanas(5);
            $cuentasGenericas = $this->seedCuentasGenericas(10);
            $this->seedCA01ParaGenericas($cuentasGenericas);
        });

        $this->command->info('SiguaAuditSeeder finalizado.');
    }

    private function resolveDependencies(): void
    {
        $neotel = Sistema::where('slug', 'neotel')->first();
        $ahevaa = Sistema::where('slug', 'ahevaa')->first();
        $this->neotelId = $neotel?->id ?? 0;
        $this->ahevaaId = $ahevaa?->id ?? 0;

        $this->sedes = Sede::where('is_active', true)->get()->all();
        $this->campaigns = Campaign::where('is_active', true)->get()->all();
        $this->gerenteUser = User::first() ?? new User(['id' => 1, 'name' => 'Admin']);
    }

    /**
     * 35 empleados activos con cuentas nominales vinculadas (limpias).
     * @return array<int, EmpleadoRh>
     */
    private function seedEmpleadosIdeal(int $count): array
    {
        $created = [];
        $sedeIds = array_map(fn ($s) => $s->id, $this->sedes);
        $campaignIds = array_map(fn ($c) => $c->id, $this->campaigns);

        for ($i = 1; $i <= $count; $i++) {
            $numEmpleado = 'EMP-' . str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT);
            if (EmpleadoRh::where('num_empleado', $numEmpleado)->exists()) {
                continue;
            }
            $empleado = EmpleadoRh::create([
                'num_empleado' => $numEmpleado,
                'nombre_completo' => $this->faker->name(),
                'sede_id' => $this->faker->randomElement($sedeIds),
                'campaign_id' => $this->faker->randomElement($campaignIds),
                'area' => $this->faker->randomElement(['Ventas', 'Soporte', 'Operaciones', 'Retención']),
                'puesto' => $this->faker->randomElement(['Agente', 'Asesor', 'Operador']),
                'estatus' => 'Activo',
                'fecha_ingreso' => $this->faker->dateTimeBetween('-2 years', '-1 month')->format('Y-m-d'),
            ]);
            $created[] = $empleado;

            $sedeId = $empleado->sede_id ?? $sedeIds[0];
            $campaignId = $empleado->campaign_id ?? $campaignIds[0];
            $usuarioCuenta = 'nom_' . str_replace('-', '', $numEmpleado) . '_' . substr(md5((string) $i), 0, 4);
            CuentaGenerica::create([
                'system_id' => $this->neotelId,
                'usuario_cuenta' => $usuarioCuenta,
                'nombre_cuenta' => $empleado->nombre_completo . ' — Neotel',
                'sede_id' => $sedeId,
                'campaign_id' => $campaignId,
                'estado' => 'activa',
                'empleado_rh_id' => $empleado->id,
                'tipo' => 'nominal',
            ]);
        }

        return $created;
    }

    /**
     * 5 empleados con estatus Baja que aún tienen cuentas activas (zombie).
     * @return array<int, EmpleadoRh>
     */
    private function seedEmpleadosZombie(int $count): array
    {
        $created = [];
        $sedeIds = array_map(fn ($s) => $s->id, $this->sedes);
        $campaignIds = array_map(fn ($c) => $c->id, $this->campaigns);

        for ($i = 1; $i <= $count; $i++) {
            $numEmpleado = 'ZMB-' . str_pad((string) (2000 + $i), 4, '0', STR_PAD_LEFT);
            if (EmpleadoRh::where('num_empleado', $numEmpleado)->exists()) {
                continue;
            }
            $empleado = EmpleadoRh::create([
                'num_empleado' => $numEmpleado,
                'nombre_completo' => $this->faker->name(),
                'sede_id' => $this->faker->randomElement($sedeIds),
                'campaign_id' => $this->faker->randomElement($campaignIds),
                'area' => 'Baja',
                'puesto' => 'Ex Agente',
                'estatus' => 'Baja',
                'fecha_ingreso' => $this->faker->dateTimeBetween('-3 years', '-1 year')->format('Y-m-d'),
            ]);
            $created[] = $empleado;

            $sedeId = $empleado->sede_id ?? $sedeIds[0];
            $campaignId = $empleado->campaign_id ?? $campaignIds[0];
            CuentaGenerica::create([
                'system_id' => $this->neotelId,
                'usuario_cuenta' => 'zombie_' . $numEmpleado,
                'nombre_cuenta' => $empleado->nombre_completo . ' (baja) — cuenta activa',
                'sede_id' => $sedeId,
                'campaign_id' => $campaignId,
                'estado' => 'activa',
                'empleado_rh_id' => $empleado->id,
                'tipo' => 'nominal',
            ]);
        }

        return $created;
    }

    /**
     * 5 cuentas sin empleado_rh_id (huérfanas, anomalía en cruce).
     */
    private function seedCuentasHuérfanas(int $count): void
    {
        $sedeIds = array_map(fn ($s) => $s->id, $this->sedes);
        $campaignIds = array_map(fn ($c) => $c->id, $this->campaigns);

        for ($i = 1; $i <= $count; $i++) {
            $usuario = 'huerfana_' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
            if (CuentaGenerica::where('usuario_cuenta', $usuario)->exists()) {
                continue;
            }
            CuentaGenerica::create([
                'system_id' => $this->neotelId,
                'usuario_cuenta' => $usuario,
                'nombre_cuenta' => 'Cuenta huérfana ' . $i . ' — Sin RH',
                'sede_id' => $this->faker->randomElement($sedeIds),
                'campaign_id' => $this->faker->randomElement($campaignIds),
                'estado' => 'activa',
                'empleado_rh_id' => null,
                'tipo' => 'desconocida',
            ]);
        }
    }

    /**
     * 10 cuentas genéricas (ventas_isla_01, soporte_02, …).
     * @return array<int, CuentaGenerica>
     */
    private function seedCuentasGenericas(int $count): array
    {
        $created = [];
        $prefixes = ['ventas_isla', 'soporte', 'retencion', 'atención_isla', 'backoffice'];
        $sedeIds = array_map(fn ($s) => $s->id, $this->sedes);
        $campaignIds = array_map(fn ($c) => $c->id, $this->campaigns);

        for ($i = 1; $i <= $count; $i++) {
            $pref = $prefixes[($i - 1) % count($prefixes)];
            $suf = str_pad((string) (($i - 1) % 2 + 1), 2, '0', STR_PAD_LEFT);
            $usuario = $pref . '_' . $suf;
            if (CuentaGenerica::where('usuario_cuenta', $usuario)->exists()) {
                continue;
            }
            $cuenta = CuentaGenerica::create([
                'system_id' => $this->neotelId,
                'usuario_cuenta' => $usuario,
                'nombre_cuenta' => ucfirst(str_replace('_', ' ', $pref)) . ' ' . $suf . ' — Genérica',
                'sede_id' => $this->faker->randomElement($sedeIds),
                'isla' => 'Isla ' . (($i % 4) + 1),
                'campaign_id' => $this->faker->randomElement($campaignIds),
                'estado' => 'activa',
                'empleado_rh_id' => null,
                'tipo' => 'generica',
            ]);
            $created[] = $cuenta;
        }

        return $created;
    }

    /**
     * De las 10 genéricas: 4 CA-01 vigente, 3 vencido (>10 días), 3 por vencer (próximos 5 días).
     * @param array<int, CuentaGenerica> $cuentasGenericas
     */
    private function seedCA01ParaGenericas(array $cuentasGenericas): void
    {
        if (count($cuentasGenericas) < 10) {
            return;
        }
        $campaignId = $this->campaigns[0]->id ?? Campaign::first()?->id;
        $sedeId = $this->sedes[0]->id ?? Sede::first()?->id;
        $userId = $this->gerenteUser->id ?? 1;

        $now = now();
        $vigenteCount = 0;
        $vencidoCount = 0;
        $porVencerCount = 0;

        foreach ($cuentasGenericas as $idx => $cuenta) {
            if ($vigenteCount < 4) {
                $firma = Carbon::today()->subDays(rand(30, 90));
                $venc = $firma->copy()->addMonths(6);
                $estado = 'vigente';
                $vigenteCount++;
            } elseif ($vencidoCount < 3) {
                $venc = Carbon::today()->subDays(rand(11, 60));
                $firma = $venc->copy()->subMonths(6);
                $estado = 'vencido';
                $vencidoCount++;
            } else {
                $venc = Carbon::today()->addDays(rand(1, 5));
                $firma = $venc->copy()->subMonths(6);
                $estado = 'vigente';
                $porVencerCount++;
            }

            $ca01 = FormatoCA01::create([
                'gerente_user_id' => $userId,
                'campaign_id' => $campaignId,
                'sede_id' => $sedeId,
                'system_id' => $this->neotelId,
                'fecha_firma' => $firma,
                'fecha_vencimiento' => $venc,
                'estado' => $estado,
                'observaciones' => 'Seeder — ' . $estado,
                'created_by' => $userId,
            ]);
            DB::table('sigua_ca01_accounts')->insert([
                'ca01_id' => $ca01->id,
                'account_id' => $cuenta->id,
                'justificacion' => 'Cuenta genérica Contact Center.',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
