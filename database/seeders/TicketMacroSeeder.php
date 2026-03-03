<?php

namespace Database\Seeders;

use App\Models\TicketMacro;
use Illuminate\Database\Seeder;

class TicketMacroSeeder extends Seeder
{
    public function run(): void
    {
        $macros = [
            [
                'name' => 'Saludo inicial',
                'content' => "Buenos días/tardes,\n\nHemos recibido su solicitud y la estamos atendiendo. Le daremos seguimiento a la brevedad.\n\nSaludos cordiales.",
                'category' => 'Atención inicial',
                'is_active' => true,
            ],
            [
                'name' => 'Cierre por falta de respuesta',
                'content' => "Estimado/a,\n\nTras varios intentos de contacto sin respuesta, procedemos a cerrar este ticket. Si el tema persiste, puede abrir una nueva solicitud.\n\nQuedamos atentos.",
                'category' => 'Cierre',
                'is_active' => true,
            ],
        ];

        foreach ($macros as $macro) {
            TicketMacro::firstOrCreate(
                ['name' => $macro['name']],
                $macro
            );
        }
    }
}
