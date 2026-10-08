<?php

namespace App\Services;

use App\Models\Role;
use App\Models\SmallGroup;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Database\Eloquent\Builder;

class AccessManager
{
    /** @var array<int, array<string, string>> */
    private array $permissionMaps = [];

    /** @return array<string, string> */
    public function permissionMap(User $user): array
    {
        if (! $user->isActive()) {
            return [];
        }

        return $this->permissionMaps[$user->id] ??= $this->loadPermissionMap($user);
    }

    public function permissionScope(User $user, string $permission): ?string
    {
        return $this->permissionMap($user)[$permission] ?? null;
    }

    public function forget(User|int $user): void
    {
        unset($this->permissionMaps[$user instanceof User ? $user->id : $user]);
    }

    public function scopeMembers(Builder $query, ?User $viewer, string $permission): Builder
    {
        $scope = $viewer ? $this->permissionScope($viewer, $permission) : null;
        if ($scope === PermissionRegistry::SCOPE_ALL) {
            return $query;
        }
        if ($scope !== PermissionRegistry::SCOPE_ASSOCIATED || ! $viewer) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $query) use ($viewer): void {
            $query->whereKey($viewer->id)
                ->orWhereHas('smallGroupMemberships.smallGroup.leaders', fn (Builder $leaders) => $leaders->whereKey($viewer->id));
        });
    }

    public function scopeSmallGroups(Builder $query, ?User $viewer, string $permission): Builder
    {
        $scope = $viewer ? $this->permissionScope($viewer, $permission) : null;
        if ($scope === PermissionRegistry::SCOPE_ALL) {
            return $query;
        }
        if ($scope !== PermissionRegistry::SCOPE_ASSOCIATED || ! $viewer) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('leaders', fn (Builder $leaders) => $leaders->whereKey($viewer->id));
    }

    public function canAccessMember(User $viewer, User $member, string $permission): bool
    {
        return $this->scopeMembers(User::query(), $viewer, $permission)->whereKey($member->id)->exists();
    }

    public function canAccessSmallGroup(User $viewer, SmallGroup $group, string $permission): bool
    {
        return $this->scopeSmallGroups(SmallGroup::query(), $viewer, $permission)->whereKey($group->id)->exists();
    }

    /** @param list<int|string> $roleIds */
    public function canAssignRoles(User $actor, array $roleIds, ?User $target = null): bool
    {
        if ($this->permissionScope($actor, 'users.assign_roles') === null) {
            return false;
        }
        if ($target && ! $this->canAccessMember($actor, $target, 'users.assign_roles')) {
            return false;
        }

        $adminRoleId = Role::query()->where('slug', Role::ADMIN)->value('id');
        $changesAdminMembership = in_array((int) $adminRoleId, array_map('intval', $roleIds), true)
            || $target?->isAdmin();

        return ! $changesAdminMembership || $actor->isAdmin();
    }

    /** @return array<string, string> */
    private function loadPermissionMap(User $user): array
    {
        $roles = $user->roles()->with('permissions')->get();
        if ($roles->contains('slug', Role::ADMIN)) {
            return array_fill_keys(PermissionRegistry::keys(), PermissionRegistry::SCOPE_ALL);
        }

        $map = [];
        $rootScopes = [];
        foreach ($roles as $role) {
            $rolePermissions = $role->permissions->keyBy('permission');
            foreach (PermissionRegistry::SCOPE_FAMILIES as $root => $family) {
                $rootGrant = $rolePermissions->get($root);
                $roleScope = $rootGrant?->access_scope;
                if (! $roleScope) {
                    foreach ($family as $permission) {
                        $roleScope = PermissionRegistry::broaderScope(
                            $roleScope,
                            $rolePermissions->get($permission)?->access_scope,
                        );
                    }
                }
                if ($roleScope) {
                    $rootScopes[$root] = PermissionRegistry::broaderScope($rootScopes[$root] ?? null, $roleScope);
                }
            }

            foreach ($role->permissions as $permission) {
                $map[$permission->permission] = PermissionRegistry::broaderScope(
                    $map[$permission->permission] ?? null,
                    $permission->access_scope,
                );
            }
        }

        foreach ($map as $permission => $scope) {
            $root = PermissionRegistry::scopeRootFor($permission);
            if ($root && isset($rootScopes[$root])) {
                $map[$permission] = $rootScopes[$root];
            }
        }

        return $map;
    }
}
