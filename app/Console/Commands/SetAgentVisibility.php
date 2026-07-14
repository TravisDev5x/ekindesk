<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;

/**
 * Sub-fase 5.2 del sprint maestro: activa/desactiva clients.show_agent_names
 * para un tenant específico. Hasta que exista una pantalla de configuración
 * en el portal del operador (fuera de esta sub-fase, ver auditoría), esta
 * es la única forma real de cambiarlo sin editar la base a mano.
 */
class SetAgentVisibility extends Command
{
    protected $signature = 'tenants:set-agent-visibility
        {portal_slug : portal_slug del client a modificar}
        {--show : Mostrar nombre/email real del agente al solicitante}
        {--hide : Ocultar nombre/email del agente, mostrar etiqueta genérica}';

    protected $description = 'Activa o desactiva show_agent_names para el tenant con el portal_slug indicado';

    public function handle(): int
    {
        $slug = (string) $this->argument('portal_slug');
        $show = (bool) $this->option('show');
        $hide = (bool) $this->option('hide');

        if ($show === $hide) {
            $this->error('Especifica exactamente una opción: --show o --hide.');

            return self::FAILURE;
        }

        $client = Client::where('portal_slug', $slug)->first();
        if (! $client) {
            $this->error("No existe ningún client con portal_slug '{$slug}'.");

            return self::FAILURE;
        }

        $client->update(['show_agent_names' => $show]);

        $this->info(sprintf(
            '%s: show_agent_names ahora es %s.',
            $client->name,
            $show ? 'true (nombre visible)' : 'false (nombre oculto)'
        ));

        return self::SUCCESS;
    }
}
