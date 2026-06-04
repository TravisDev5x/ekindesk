<?php

namespace Database\Seeders\Concerns;

use App\Models\Cliente;
use App\Models\Sede;
use App\Support\Database\TenantBackfill;
use App\Support\Database\TenantIntegrity;
use Illuminate\Support\Facades\Schema;

trait AssignsTenantToSites
{
    protected function ensureSitesHaveTenantClient(): void
    {
        TenantIntegrity::ensurePlatformClientOnSites();

        $clientId = Cliente::where('code', TenantIntegrity::PLATFORM_CLIENT_CODE)->value('id');

        if ($clientId) {
            Sede::whereNull('client_id')->update(['client_id' => $clientId]);
        }

        if (Schema::hasColumn('tickets', 'client_id')) {
            TenantBackfill::syncClientIdFromSites('tickets');
        }
        if (Schema::hasTable('incidents') && Schema::hasColumn('incidents', 'client_id')) {
            TenantBackfill::syncClientIdFromSites('incidents');
        }
    }
}
