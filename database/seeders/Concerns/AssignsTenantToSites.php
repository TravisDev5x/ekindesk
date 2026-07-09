<?php

namespace Database\Seeders\Concerns;

use App\Models\Client;
use App\Models\Site;
use App\Support\Database\TenantBackfill;
use App\Support\Database\TenantIntegrity;
use Illuminate\Support\Facades\Schema;

trait AssignsTenantToSites
{
    protected function ensureSitesHaveTenantClient(): void
    {
        TenantIntegrity::ensurePlatformClientOnSites();

        $clientId = Client::where('code', TenantIntegrity::PLATFORM_CLIENT_CODE)->value('id');

        if ($clientId) {
            Site::whereNull('client_id')->update(['client_id' => $clientId]);
        }

        if (Schema::hasColumn('tickets', 'client_id')) {
            TenantBackfill::syncClientIdFromSites('tickets');
        }
        if (Schema::hasTable('incidents') && Schema::hasColumn('incidents', 'client_id')) {
            TenantBackfill::syncClientIdFromSites('incidents');
        }
    }
}
