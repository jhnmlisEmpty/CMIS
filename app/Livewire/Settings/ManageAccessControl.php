<?php

namespace App\Livewire\Settings;

use App\Models\AttendanceSetting;
use App\Models\EventAudienceRule;
use App\Models\Role;
use App\Models\RolePermission;
use App\Services\AuditLogger;
use App\Services\PermissionResolver;
use App\Support\PermissionRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Access Control | True Vine World Harvest Church - Pangasinan')]
class ManageAccessControl extends Component
{
    public ?int $selectedRoleId = null;

    /** @deprecated Compatibility input for older clients; use selectedRoleId. */
    public string $selectedRole = Role::PASTOR;

    public array $selectedPermissions = [];

    /** @var array<string, string> */
    public array $selectedPermissionScopes = [];

    /** @var array<string, string> */
    public array $permissionScopeInputs = [];

    /** @var list<string> */
    public array $permissionNotices = [];

    /** @var list<array{permission: string, from: string, to: string, required_by: list<string>}> */
    public array $permissionAdjustments = [];

    public bool $permissionsDirty = false;

    public bool $showPermissionConfirmation = false;

    public bool $showUnsavedPrompt = false;

    public ?int $pendingRoleId = null;

    public ?int $loadedRoleVersion = null;

    public string $newRoleName = '';

    public string $editingRoleName = '';

    public bool $showCreateRole = false;

    public bool $showRenameRole = false;

    public function mount(): void
    {
        Gate::authorize('access-control.manage');
        $this->selectedRoleId = $this->configurableRoles()->first()?->id;
        $this->selectedRole = $this->selectedRole(false)?->slug ?? '';
        $this->loadPermissions();
    }

    public function updatedSelectedRole(): void
    {
        Gate::authorize('access-control.manage');
        $role = Role::query()->where('slug', $this->selectedRole)->firstOrFail();
        $this->switchRole($role->id);
    }

    /** Compatibility for tests and older clients using direct property updates. */
    public function updatedSelectedRoleId(): void
    {
        Gate::authorize('access-control.manage');
        $this->selectedRole();
        $this->selectedRole = $this->selectedRole(false)?->slug ?? '';
        $this->showRenameRole = false;
        $this->loadPermissions();
    }

    public function requestRoleChange(int $roleId): void
    {
        Gate::authorize('access-control.manage');
        Role::query()->findOrFail($roleId);
        if ($roleId === $this->selectedRoleId) {
            return;
        }

        if ($this->permissionsDirty) {
            $this->pendingRoleId = $roleId;
            $this->showUnsavedPrompt = true;

            return;
        }

        $this->switchRole($roleId);
    }

    public function discardAndSwitchRole(): void
    {
        Gate::authorize('access-control.manage');
        if ($this->pendingRoleId) {
            $this->switchRole($this->pendingRoleId);
        }
    }

    public function stayOnRole(): void
    {
        $this->pendingRoleId = null;
        $this->showUnsavedPrompt = false;
    }

    public function togglePermission(string $permission): void
    {
        $this->setPermissionEnabled($permission, ! isset($this->selectedPermissionScopes[$this->stateKey($permission)]));
    }

    public function setPermissionEnabled(string $permission, bool $enabled): void
    {
        Gate::authorize('access-control.manage');
        abort_unless(in_array($permission, PermissionRegistry::keys(), true), 422);
        $this->beginPermissionChange();

        $permissionKey = $this->stateKey($permission);
        if (! $enabled) {
            unset($this->selectedPermissionScopes[$permissionKey]);
        } else {
            $root = PermissionRegistry::scopeRootFor($permission);
            $rootKey = $root ? $this->stateKey($root) : null;
            if ($rootKey && ! isset($this->selectedPermissionScopes[$rootKey])) {
                $this->selectedPermissionScopes[$rootKey] = PermissionRegistry::SCOPE_ASSOCIATED;
            }
            $this->selectedPermissionScopes[$permissionKey] = $rootKey
                ? $this->selectedPermissionScopes[$rootKey]
                : PermissionRegistry::SCOPE_ALL;
        }

        $this->selectedPermissions = array_keys($this->draftPermissionScopes());
        $this->syncPermissionScopeInputs();
        $this->refreshPermissionPreview();
    }

    public function updatedPermissionScopeInputs(mixed $scope, string $key): void
    {
        $this->setPermissionScope(str_replace('__', '.', $key), (string) $scope);
    }

    public function setPermissionScope(string $permission, string $scope): void
    {
        Gate::authorize('access-control.manage');
        abort_unless(PermissionRegistry::isScopeable($permission), 422);
        abort_unless($scope === 'none' || in_array($scope, PermissionRegistry::SCOPES, true), 422);
        $this->beginPermissionChange();

        if ($scope === 'none') {
            foreach (PermissionRegistry::scopeFamily($permission) as $relatedPermission) {
                unset($this->selectedPermissionScopes[$this->stateKey($relatedPermission)]);
            }
        } else {
            foreach (PermissionRegistry::scopeFamily($permission) as $relatedPermission) {
                $relatedKey = $this->stateKey($relatedPermission);
                if ($relatedPermission === $permission || isset($this->selectedPermissionScopes[$relatedKey])) {
                    $this->selectedPermissionScopes[$relatedKey] = $scope;
                }
            }
        }

        $this->selectedPermissions = array_keys($this->draftPermissionScopes());
        $this->syncPermissionScopeInputs();
        $this->refreshPermissionPreview();
    }

    public function save(PermissionResolver $resolver, AuditLogger $audit): void
    {
        Gate::authorize('access-control.manage');
        $this->resetErrorBag('permissions');
        session()->forget('success');
        $role = $this->selectedRole();
        abort_if($role->slug === Role::ADMIN, 422, 'Admin always has full access and does not need configurable permissions.');

        $resolution = $resolver->resolve($this->draftPermissionScopes());
        $this->setPermissionPreview($resolution->adjustments);
        if ($resolution->hasAdjustments()) {
            $this->showPermissionConfirmation = true;

            return;
        }

        $this->persistPermissions($resolution->effective, $audit);
    }

    public function saveAndSwitchRole(PermissionResolver $resolver, AuditLogger $audit): void
    {
        $this->showUnsavedPrompt = false;
        $this->save($resolver, $audit);
    }

    public function confirmPermissionSave(PermissionResolver $resolver, AuditLogger $audit): void
    {
        Gate::authorize('access-control.manage');
        $role = $this->selectedRole();
        abort_if($role->slug === Role::ADMIN, 422, 'Admin always has full access and does not need configurable permissions.');

        $resolution = $resolver->resolve($this->draftPermissionScopes());
        $this->setPermissionPreview($resolution->adjustments);
        $this->persistPermissions($resolution->effective, $audit);
    }

    public function cancelPermissionConfirmation(): void
    {
        $this->showPermissionConfirmation = false;
    }

    public function createRole(AuditLogger $audit): void
    {
        Gate::authorize('access-control.manage');
        $validated = $this->validate([
            'newRoleName' => ['required', 'string', 'min:2', 'max:80', Rule::unique('roles', 'name')],
        ]);

        $name = trim($validated['newRoleName']);
        if (Role::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            $this->addError('newRoleName', 'A role with this name already exists.');

            return;
        }

        $baseSlug = Str::slug($name, '_') ?: 'custom_role';
        $slug = $baseSlug;
        $suffix = 2;
        while (Role::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'_'.$suffix++;
        }

        $role = Role::create(['name' => $name, 'slug' => $slug, 'is_system' => false]);
        $audit->log('created', 'access_control', 'Role created: '.$role->name, $role, [], ['name' => $role->name, 'slug' => $role->slug]);
        $this->selectedRoleId = $role->id;
        $this->selectedRole = $role->slug;
        $this->loadPermissions();
        $this->newRoleName = '';
        $this->showCreateRole = false;
        session()->flash('success', $role->name.' was created with no permissions.');
    }

    public function beginRename(): void
    {
        Gate::authorize('access-control.manage');
        $role = $this->selectedRole();
        $this->editingRoleName = $role->name;
        $this->showRenameRole = true;
    }

    public function renameRole(AuditLogger $audit): void
    {
        Gate::authorize('access-control.manage');
        $role = $this->selectedRole();
        $validated = $this->validate([
            'editingRoleName' => ['required', 'string', 'min:2', 'max:80', Rule::unique('roles', 'name')->ignore($role->id)],
        ]);
        $name = trim($validated['editingRoleName']);
        if (Role::query()->whereKeyNot($role->id)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->exists()) {
            $this->addError('editingRoleName', 'A role with this name already exists.');

            return;
        }

        $before = $role->name;
        $role->update(['name' => $name]);
        $audit->log('updated', 'access_control', 'Role renamed to '.$name, $role, ['name' => $before], ['name' => $name]);
        $this->showRenameRole = false;
        $this->loadedRoleVersion = $this->roleVersion($role->fresh());
        session()->flash('success', 'Role renamed to '.$name.'.');
    }

    public function deleteRole(AuditLogger $audit): void
    {
        Gate::authorize('access-control.manage');
        $role = $this->selectedRole();
        abort_if($role->slug === Role::ADMIN, 422, 'The Admin role cannot be deleted because it controls access to system settings.');
        abort_if($role->slug === Role::MEMBER, 422, 'The Member role cannot be deleted because it is the default role for new users.');
        abort_if(EventAudienceRule::query()->where('audience_type', EventAudienceRule::TYPE_ROLE)->where('audience_key', $role->slug)->exists(), 422, 'This role is still used by an event audience.');
        abort_if(AttendanceSetting::query()->get()->contains(fn (AttendanceSetting $setting) => in_array($role->slug, $setting->role_audience ?? [], true)), 422, 'This role is still used by attendance defaults.');
        $fallbackRole = Role::defaultForNewUsers($role->id);
        $usersNeedingFallback = $role->users()
            ->whereDoesntHave('roles', fn ($query) => $query->whereKeyNot($role->id))
            ->pluck('users.id');
        abort_if($usersNeedingFallback->isNotEmpty() && ! $fallbackRole, 422, 'The Member role is required before deleting a role assigned as a member\'s only role.');

        $assignedUserCount = $role->users()->count();
        $snapshot = ['name' => $role->name, 'slug' => $role->slug, 'permissions' => $role->permissions()->pluck('permission')->all(), 'assigned_users' => $assignedUserCount];
        $name = $role->name;
        DB::transaction(function () use ($role, $usersNeedingFallback, $fallbackRole): void {
            if ($fallbackRole && $usersNeedingFallback->isNotEmpty()) {
                $now = now();
                DB::table('role_user')->insertOrIgnore($usersNeedingFallback->map(fn ($userId): array => [
                    'role_id' => $fallbackRole->id,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            }
            $role->delete();
        });
        $audit->log('deleted', 'access_control', 'Role deleted: '.$name, null, $snapshot, [
            'fallback_role' => $fallbackRole?->slug,
            'members_reassigned' => $usersNeedingFallback->count(),
        ]);

        $this->selectedRoleId = $this->configurableRoles()->first()?->id;
        $this->selectedRole = $this->selectedRole(false)?->slug ?? '';
        $this->loadPermissions();
        session()->flash('success', $name.' was deleted.');
    }

    private function persistPermissions(array $permissions, AuditLogger $audit): void
    {
        $role = $this->selectedRole();
        try {
            DB::transaction(function () use ($role, $permissions, $audit): void {
                $lockedRole = Role::query()->lockForUpdate()->findOrFail($role->id);
                if ($this->roleVersion($lockedRole) !== $this->loadedRoleVersion) {
                    throw new \RuntimeException('stale-permission-edit');
                }

                $previous = $lockedRole->permissions()->get(['permission', 'access_scope'])
                    ->mapWithKeys(fn (RolePermission $item) => [$item->permission => $item->access_scope])->all();
                $lockedRole->permissions()->delete();
                $lockedRole->permissions()->createMany(collect($permissions)->map(
                    fn (string $scope, string $permission): array => ['permission' => $permission, 'access_scope' => $scope],
                )->values()->all());
                $lockedRole->increment('permissions_version');
                $lockedRole->touch();
                $audit->log('updated', 'access_control', 'Permissions updated for '.$lockedRole->name, $lockedRole, ['permissions' => $previous], [
                    'requested_permissions' => $this->draftPermissionScopes(),
                    'effective_permissions' => $permissions,
                    'automatic_adjustments' => $this->permissionAdjustments,
                ]);
            });
        } catch (\Throwable $exception) {
            if ($exception instanceof \RuntimeException && $exception->getMessage() === 'stale-permission-edit') {
                $this->addError('permissions', 'This role was changed by another administrator. Your draft is still visible. Reload the role before saving again.');
                $this->showPermissionConfirmation = false;

                return;
            }

            report($exception);
            $this->permissionsDirty = true;
            $this->addError('permissions', 'Permissions were not saved because the database or audit log could not be updated. Previous saved permissions remain unchanged. Your selections are still here; retry saving.');

            return;
        }

        $this->setDraftPermissionScopes($permissions);
        $this->selectedPermissions = array_keys($permissions);
        $this->syncPermissionScopeInputs();
        $this->permissionsDirty = false;
        $this->showPermissionConfirmation = false;
        $this->permissionAdjustments = [];
        $this->permissionNotices = [];
        $this->loadedRoleVersion = $this->roleVersion($role->fresh());
        session()->flash('success', 'Permissions updated for '.$role->name.'.');

        if ($this->pendingRoleId) {
            $this->switchRole($this->pendingRoleId);
        }
    }

    private function loadPermissions(): void
    {
        $this->permissionNotices = [];
        $this->permissionAdjustments = [];
        $this->permissionsDirty = false;
        $this->showPermissionConfirmation = false;
        $this->showUnsavedPrompt = false;
        $this->pendingRoleId = null;
        $this->resetErrorBag('permissions');
        $role = $this->selectedRole(false);
        $savedPermissions = $role?->permissions()->get(['permission', 'access_scope'])->mapWithKeys(
            fn (RolePermission $item): array => [$item->permission => $item->access_scope],
        )->all() ?? [];
        $permissions = $role?->slug === Role::ADMIN
            ? array_fill_keys(PermissionRegistry::keys(), PermissionRegistry::SCOPE_ALL)
            : $this->canonicalizeDraftScopes($savedPermissions);
        $this->setDraftPermissionScopes($permissions);
        $this->selectedPermissions = array_keys($permissions);
        $this->loadedRoleVersion = $role ? $this->roleVersion($role) : null;
        $this->syncPermissionScopeInputs();
    }

    private function beginPermissionChange(): void
    {
        $this->permissionNotices = [];
        $this->permissionAdjustments = [];
        $this->permissionsDirty = true;
        $this->showPermissionConfirmation = false;
        $this->resetErrorBag('permissions');
        session()->forget('success');
    }

    private function refreshPermissionPreview(): void
    {
        $this->setPermissionPreview(app(PermissionResolver::class)->resolve($this->draftPermissionScopes())->adjustments);
    }

    private function setPermissionPreview(array $adjustments): void
    {
        $this->permissionAdjustments = $adjustments;
        $labels = array_merge(...array_values(PermissionRegistry::GROUPS));
        $scopeLabels = ['none' => 'No access', 'associated' => 'Associated only', 'all' => 'All records'];
        $this->permissionNotices = collect($adjustments)->map(function (array $adjustment) use ($labels, $scopeLabels): string {
            $requiredBy = collect($adjustment['required_by'])
                ->map(fn (string $key): string => $labels[$key] ?? $key)
                ->join(', ');

            return ($labels[$adjustment['permission']] ?? $adjustment['permission'])
                .' will change from '.($scopeLabels[$adjustment['from']] ?? $adjustment['from'])
                .' to '.($scopeLabels[$adjustment['to']] ?? $adjustment['to'])
                .' because it is required by '.($requiredBy ?: 'another enabled permission').'.';
        })->all();
    }

    private function syncPermissionScopeInputs(): void
    {
        $permissions = $this->draftPermissionScopes();
        $this->permissionScopeInputs = collect(PermissionRegistry::SCOPEABLE)
            ->mapWithKeys(fn (string $permission): array => [
                $this->stateKey($permission) => $permissions[$permission] ?? 'none',
            ])->all();
    }

    /** @return array<string, string> */
    private function draftPermissionScopes(): array
    {
        $permissions = [];
        foreach ($this->selectedPermissionScopes as $key => $scope) {
            if (! is_string($scope)) {
                continue;
            }

            $permission = str_replace('__', '.', (string) $key);
            if (in_array($permission, PermissionRegistry::keys(), true) && in_array($scope, PermissionRegistry::SCOPES, true)) {
                $permissions[$permission] = $scope;
            }
        }

        return $permissions;
    }

    /** @param array<string, string> $permissions */
    private function setDraftPermissionScopes(array $permissions): void
    {
        $this->selectedPermissionScopes = collect($permissions)->mapWithKeys(
            fn (string $scope, string $permission): array => [$this->stateKey($permission) => $scope],
        )->all();
    }

    private function stateKey(string $permission): string
    {
        return str_replace('.', '__', $permission);
    }

    /** @param array<string, string> $permissions */
    private function canonicalizeDraftScopes(array $permissions): array
    {
        foreach (PermissionRegistry::SCOPE_FAMILIES as $root => $family) {
            $enabled = array_values(array_intersect($family, array_keys($permissions)));
            if ($enabled === []) {
                continue;
            }

            $scope = isset($permissions[$root]) && in_array($permissions[$root], PermissionRegistry::SCOPES, true)
                ? $permissions[$root]
                : collect($enabled)->reduce(
                    fn (?string $current, string $permission): ?string => PermissionRegistry::broaderScope($current, $permissions[$permission] ?? null),
                );
            $scope ??= PermissionRegistry::SCOPE_ASSOCIATED;
            $permissions[$root] = $scope;
            foreach ($enabled as $permission) {
                $permissions[$permission] = $scope;
            }
        }

        return $permissions;
    }

    private function switchRole(int $roleId): void
    {
        $this->selectedRoleId = $roleId;
        $this->selectedRole = $this->selectedRole()?->slug ?? '';
        $this->showRenameRole = false;
        $this->loadPermissions();
    }

    private function roleVersion(Role $role): int
    {
        return (int) $role->permissions_version;
    }

    private function configurableRoles()
    {
        return Role::query()->withCount('users')->orderByDesc('is_system')->orderBy('id')->get();
    }

    private function selectedRole(bool $fail = true): ?Role
    {
        $query = Role::query()->whereKey($this->selectedRoleId);

        return $fail ? $query->firstOrFail() : $query->first();
    }

    public function render()
    {
        $roles = $this->configurableRoles();
        $selectedRole = $roles->firstWhere('id', $this->selectedRoleId);

        return view('livewire.settings.manage-access-control', [
            'permissionGroups' => PermissionRegistry::GROUPS,
            'dependencies' => PermissionRegistry::DEPENDENCIES,
            'scopeablePermissions' => PermissionRegistry::SCOPEABLE,
            'roles' => $roles,
            'selectedRoleRecord' => $selectedRole,
            'rolePermissionCounts' => RolePermission::query()
                ->whereIn('role_id', $roles->pluck('id'))
                ->selectRaw('role_id, count(*) as total')
                ->groupBy('role_id')
                ->pluck('total', 'role_id'),
        ]);
    }
}
