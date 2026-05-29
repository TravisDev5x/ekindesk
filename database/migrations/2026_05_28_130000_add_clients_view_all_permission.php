<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['web', 'sanctum'] as $guard) {
            DB::table('permissions')->insertOrIgnore([
                'name' => 'clients.view_all',
                'guard_name' => $guard,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $permId = DB::table('permissions')
            ->where('name', 'clients.view_all')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $permId) {
            return;
        }

        foreach (['admin', 'super_admin'] as $roleName) {
            $roleId = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->value('id');
            if ($roleId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permId,
                    'role_id' => $roleId,
                ]);
            }
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $permIds = DB::table('permissions')->where('name', 'clients.view_all')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->where('name', 'clients.view_all')->delete();
    }
};
