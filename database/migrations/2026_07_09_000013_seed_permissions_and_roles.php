<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Consolidated permission/role seeding (was 5 separate migrations). Role assignments other
 * than 'admin' are best-effort (insertOrIgnore, skipped if the role doesn't exist yet) since
 * roles like supervisor/soporte/gerente are created later by database/seeders/, not by
 * migrations — this mirrors the original migrations' behavior exactly.
 */
return new class extends Migration
{
    public function up(): void
    {
        $guards = ['web', 'sanctum'];

        $core = [
            'users.manage',
            'roles.manage',
            'permissions.manage',
            'catalogs.manage',
            'notifications.manage',
        ];

        $ticketPerms = [
            'tickets.create',
            'tickets.view_own',
            'tickets.view_area',
            'tickets.filter_by_sede',
            'tickets.assign',
            'tickets.comment',
            'tickets.change_status',
            'tickets.escalate',
            'tickets.manage_all',
        ];

        $incidentPerms = [
            'incidents.create',
            'incidents.view_own',
            'incidents.view_area',
            'incidents.manage_all',
        ];

        $clientPerms = [
            'clients.view',
            'clients.create',
            'clients.edit',
            'clients.delete',
        ];

        $companyPerms = [
            'company.view',
            'company.edit',
        ];

        $allPerms = array_unique(array_merge(
            $core, $ticketPerms, $incidentPerms, $clientPerms, ['clients.view_all'], $companyPerms
        ));

        foreach ($guards as $guard) {
            foreach ($allPerms as $name) {
                DB::table('permissions')->insertOrIgnore([
                    'name' => $name,
                    'guard_name' => $guard,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $adminRoleId = DB::table('roles')->insertGetId([
            'name' => 'admin',
            'slug' => 'admin',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permIds = DB::table('permissions')->where('guard_name', 'web')->pluck('id');
        foreach ($permIds as $pid) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $pid,
                'role_id' => $adminRoleId,
            ]);
        }

        $permIdsByName = DB::table('permissions')->where('guard_name', 'web')->pluck('id', 'name');

        $rolePerms = [
            'supervisor' => array_merge(['clients.view', 'clients.create', 'clients.edit'], ['company.view']),
            'gerente' => ['company.view'],
            'soporte' => ['clients.view', 'company.view'],
            'soporte_n1' => ['clients.view', 'company.view'],
            'soporte_n2' => ['clients.view', 'company.view'],
            'soporte_n3' => ['clients.view', 'company.view'],
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

        foreach (['admin', 'super_admin'] as $roleName) {
            $roleId = DB::table('roles')->where('name', $roleName)->where('guard_name', 'web')->value('id');
            $pid = $permIdsByName['clients.view_all'] ?? null;
            if ($roleId && $pid) {
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
        DB::table('role_has_permissions')->whereIn('role_id', DB::table('roles')->where('name', 'admin')->pluck('id'))->delete();
        DB::table('roles')->where('name', 'admin')->delete();
        DB::table('permissions')->delete();
    }
};
