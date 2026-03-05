<?php

namespace Database\Seeders;

use App\Models\Sigan\Asset;
use App\Models\Sigan\AssetComponent;
use App\Models\Sigan\MaintenanceRecord;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Faker\Factory as FakerFactory;

/**
 * Inventario de activos: TI (Laptops, PCs), mobiliario (Sillas, UPS).
 * Casos: 2 activos en estado Scrap (huesero) con componentes extraídos como refacciones.
 * Mantenimiento: 5 internos y 2 externos con proveedores ficticios.
 * Requiere: migración create_sigan_assets_tables ejecutada.
 */
class SiganAssetsSeeder extends Seeder
{
    private \Faker\Generator $faker;

    public function __construct()
    {
        $this->faker = FakerFactory::create('es_MX');
    }

    public function run(): void
    {
        $this->command->info('SiganAssetsSeeder: activos TI, mobiliario, canibalización y mantenimiento.');

        if (! \Illuminate\Support\Facades\Schema::hasTable('sigan_assets')) {
            $this->command->error('Ejecuta antes la migración create_sigan_assets_tables.');
            return;
        }

        $laptopsPcs = $this->seedActivosTI(30);
        $sillasUps = $this->seedActivosMobiliario(20);
        $scrapConComponentes = $this->seedScrapYCanibalizacion($laptopsPcs);
        $this->seedMantenimientos($laptopsPcs, $sillasUps);
    }

    /**
     * 30 activos de tipo TI (Laptops y PCs).
     * @return array<int, Asset>
     */
    private function seedActivosTI(int $count): array
    {
        $created = [];
        $subtipos = ['laptop', 'laptop', 'pc']; // 2/3 laptops, 1/3 pc
        $ubicaciones = ['Piso 1 - Toluca', 'Piso 2 - CDMX', 'Remoto', 'Almacén'];

        for ($i = 1; $i <= $count; $i++) {
            $subtipo = $subtipos[$i % 3];
            $nombre = $subtipo === 'laptop'
                ? 'Laptop ' . strtoupper(substr(md5((string) $i), 0, 6))
                : 'PC Escritorio ' . strtoupper(substr(md5((string) $i), 0, 6));
            $asset = Asset::create([
                'tipo' => 'ti',
                'subtipo' => $subtipo,
                'nombre' => $nombre,
                'numero_serie' => 'SN-TI-' . str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'estado' => 'activo',
                'ubicacion' => $this->faker->randomElement($ubicaciones),
                'observaciones' => $i % 5 === 0 ? 'Revisión anual pendiente.' : null,
            ]);
            $created[] = $asset;
        }

        $this->command->info("  Creados {$count} activos TI.");
        return $created;
    }

    /**
     * 20 activos de mobiliario (Sillas, UPS).
     * @return array<int, Asset>
     */
    private function seedActivosMobiliario(int $count): array
    {
        $created = [];
        $subtipos = ['silla', 'silla', 'ups'];
        $ubicaciones = ['Piso 1 - Toluca', 'Piso 2 - CDMX', 'Cubículo'];

        for ($i = 1; $i <= $count; $i++) {
            $subtipo = $subtipos[$i % 3];
            $nombre = $subtipo === 'silla'
                ? 'Silla ergonómica ' . $i
                : 'UPS ' . $i . ' 1500VA';
            $asset = Asset::create([
                'tipo' => 'mobiliario',
                'subtipo' => $subtipo,
                'nombre' => $nombre,
                'numero_serie' => $subtipo === 'ups' ? 'SN-UPS-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT) : null,
                'estado' => 'activo',
                'ubicacion' => $this->faker->randomElement($ubicaciones),
            ]);
            $created[] = $asset;
        }

        $this->command->info("  Creados {$count} activos de mobiliario.");
        return $created;
    }

    /**
     * 2 activos en estado Scrap (huesero); sus componentes RAM/SSD extraídos para uso en otros activos.
     * @param array<int, Asset> $activosTI
     * @return array<int, Asset>
     */
    private function seedScrapYCanibalizacion(array $activosTI): array
    {
        if (count($activosTI) < 3) {
            return [];
        }
        $scrap1 = Asset::create([
            'tipo' => 'ti',
            'subtipo' => 'laptop',
            'nombre' => 'Laptop Huesero 01 (Scrap)',
            'numero_serie' => 'SN-SCRAP-001',
            'estado' => 'scrap',
            'ubicacion' => 'Almacén - Área de baja',
            'observaciones' => 'Componentes extraídos para refacciones.',
        ]);
        $scrap2 = Asset::create([
            'tipo' => 'ti',
            'subtipo' => 'pc',
            'nombre' => 'PC Huesero 02 (Scrap)',
            'numero_serie' => 'SN-SCRAP-002',
            'estado' => 'scrap',
            'ubicacion' => 'Almacén - Área de baja',
            'observaciones' => 'RAM y SSD extraídos.',
        ]);

        $receptor1 = $activosTI[0];
        $receptor2 = $activosTI[1];

        AssetComponent::create([
            'asset_id' => $scrap1->id,
            'tipo_componente' => 'ram',
            'descripcion' => '8GB DDR4',
            'estado' => 'extraido',
            'usado_en_asset_id' => $receptor1->id,
        ]);
        AssetComponent::create([
            'asset_id' => $scrap1->id,
            'tipo_componente' => 'ssd',
            'descripcion' => '256GB NVMe',
            'estado' => 'extraido',
            'usado_en_asset_id' => $receptor1->id,
        ]);
        AssetComponent::create([
            'asset_id' => $scrap2->id,
            'tipo_componente' => 'ram',
            'descripcion' => '16GB DDR4',
            'estado' => 'extraido',
            'usado_en_asset_id' => $receptor2->id,
        ]);
        AssetComponent::create([
            'asset_id' => $scrap2->id,
            'tipo_componente' => 'ssd',
            'descripcion' => '512GB SATA',
            'estado' => 'extraido',
            'usado_en_asset_id' => $receptor2->id,
        ]);

        $this->command->info('  2 activos Scrap creados con componentes extraídos (canibalización).');
        return [$scrap1, $scrap2];
    }

    /**
     * 5 mantenimientos internos y 2 externos (proveedores ficticios).
     * @param array<int, Asset> $activosTI
     * @param array<int, Asset> $activosMobiliario
     */
    private function seedMantenimientos(array $activosTI, array $activosMobiliario): void
    {
        $internos = 5;
        $externos = 2;
        $todos = array_merge($activosTI, $activosMobiliario);
        $proveedores = ['TecnoReparaciones MX', 'Servicios Integrales de TI S.A. de C.V.'];

        for ($i = 0; $i < $internos; $i++) {
            $asset = $todos[$i % count($todos)];
            MaintenanceRecord::create([
                'asset_id' => $asset->id,
                'tipo' => 'interno',
                'proveedor' => null,
                'fecha' => Carbon::today()->subDays(rand(5, 90)),
                'descripcion' => 'Limpieza interna, revisión de ventilación y actualización de controladores.',
                'resultado' => 'Correctivo aplicado. Equipo operativo.',
            ]);
        }
        for ($i = 0; $i < $externos; $i++) {
            $asset = $todos[($i + 2) % count($todos)];
            MaintenanceRecord::create([
                'asset_id' => $asset->id,
                'tipo' => 'externo',
                'proveedor' => $proveedores[$i],
                'fecha' => Carbon::today()->subDays(rand(10, 60)),
                'descripcion' => 'Mantenimiento preventivo por contrato con proveedor.',
                'resultado' => 'Sin hallazgos. Próxima visita en 6 meses.',
            ]);
        }

        $this->command->info('  5 mantenimientos internos y 2 externos registrados.');
    }
}
