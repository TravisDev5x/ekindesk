<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $clientPerms = [
            'clients.view',
            'clients.create',
            'clients.edit',
            'clients.delete',
        ];

        foreach (['web', 'sanctum'] as $guard) {
            foreach ($clientPerms as $name) {
                DB::table('permissions')->insertOrIgnore([
                    'name' => $name,
                    'guard_name' => $guard,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $permIdsByName = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', $clientPerms)
            ->pluck('id', 'name');

        $rolePerms = [
            'admin' => $clientPerms,
            'supervisor' => ['clients.view', 'clients.create', 'clients.edit'],
            'soporte' => ['clients.view'],
            'soporte_n1' => ['clients.view'],
            'soporte_n2' => ['clients.view'],
            'soporte_n3' => ['clients.view'],
        ];

        foreach ($rolePerms as $roleName => $perms) {
            $roleId = DB::table('roles')->where('name', $roleName)->where('guard_name', 'web')->value('id');
            if (! $roleId) {
                continue;
            }
            foreach ($perms as $permName) {
                $pid = $permIdsByName[$permName] ?? null;
                if (! $pid) {
                    continue;
                }
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $pid,
                    'role_id' => $roleId,
                ]);
            }
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $names = ['clients.view', 'clients.create', 'clients.edit', 'clients.delete'];
        $permIds = DB::table('permissions')->whereIn('name', $names)->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $permIds)->delete();
        DB::table('permissions')->whereIn('name', $names)->delete();
    }
};
