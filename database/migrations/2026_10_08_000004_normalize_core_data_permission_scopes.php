<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $families = [
            'analytics.view' => ['analytics.view', 'analytics.manage_settings'],
            'users.view' => ['users.view', 'users.create', 'users.update', 'users.delete', 'users.export', 'users.map', 'users.assign_roles'],
            'attendance.view' => ['attendance.view', 'attendance.record'],
            'small_groups.view' => ['small_groups.view', 'small_groups.create', 'small_groups.update', 'small_groups.delete', 'group_members.view', 'group_members.add', 'group_members.remove', 'group_members.update_status'],
            'lesson_progress.view' => ['lesson_progress.view', 'lesson_progress.update'],
        ];

        DB::table('roles')->orderBy('id')->pluck('id')->each(function (int $roleId) use ($families): void {
            $permissions = DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->pluck('access_scope', 'permission');

            foreach ($families as $root => $family) {
                $enabled = array_values(array_intersect($family, $permissions->keys()->all()));
                if ($enabled === []) {
                    continue;
                }

                $scope = $permissions->get($root);
                if (! in_array($scope, ['associated', 'all'], true)) {
                    $scope = collect($enabled)->contains(fn (string $permission): bool => $permissions->get($permission) === 'all')
                        ? 'all'
                        : 'associated';
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $roleId, 'permission' => $root],
                        ['access_scope' => $scope],
                    );
                }

                DB::table('role_permissions')
                    ->where('role_id', $roleId)
                    ->whereIn('permission', $family)
                    ->update(['access_scope' => $scope]);
            }
        });
    }

    public function down(): void
    {
        // Individual action scopes cannot be reconstructed after consolidation.
    }
};
