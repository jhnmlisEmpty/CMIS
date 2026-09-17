<?php

namespace App\Livewire\Settings;

use App\Models\RolePermission;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Access Control | True Vine World Harvest Church - Pangasinan')]
class ManageAccessControl extends Component
{
    private const SMALL_GROUP_LEADER_RESTRICTED_PERMISSIONS = [
        'users.create',
        'group_members.add',
    ];

    public string $selectedRole = User::ROLE_PASTOR;

    public array $selectedPermissions = [];

    public function mount(): void
    {
        Gate::authorize('access-control.manage');
        $this->loadPermissions();
    }

    public function updatedSelectedRole(): void
    {
        Gate::authorize('access-control.manage');
        abort_unless(in_array($this->selectedRole, $this->configurableRoles(), true), 422);
        $this->loadPermissions();
    }

    public function togglePermission(string $permission): void
    {
        Gate::authorize('access-control.manage');
        abort_unless(in_array($permission, PermissionRegistry::keys(), true), 422);
        abort_if($this->isRestrictedPermission($permission), 422, 'Small-group leaders may only access members already assigned to their groups.');

        if (in_array($permission, $this->selectedPermissions, true)) {
            $remove = array_merge([$permission], PermissionRegistry::dependentsOf($permission));
            $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $remove));
        } else {
            $this->selectedPermissions = PermissionRegistry::withDependencies([
                ...$this->selectedPermissions,
                $permission,
            ]);
        }
    }

    public function save(): void
    {
        Gate::authorize('access-control.manage');
        abort_unless(in_array($this->selectedRole, $this->configurableRoles(), true), 422);

        $permissions = PermissionRegistry::withDependencies($this->selectedPermissions);
        if ($this->selectedRole === User::ROLE_SMALL_GROUP_LEADER) {
            $permissions = array_values(array_diff($permissions, self::SMALL_GROUP_LEADER_RESTRICTED_PERMISSIONS));
        }

        DB::transaction(function () use ($permissions): void {
            RolePermission::where('role', $this->selectedRole)->delete();
            RolePermission::insert(array_map(fn (string $permission): array => [
                'role' => $this->selectedRole,
                'permission' => $permission,
            ], $permissions));
        });

        $this->selectedPermissions = $permissions;
        session()->flash('success', 'Permissions updated for '.str_replace('_', ' ', $this->selectedRole).'.');
    }

    private function loadPermissions(): void
    {
        $this->selectedPermissions = RolePermission::where('role', $this->selectedRole)
            ->pluck('permission')->all();

        if ($this->selectedRole === User::ROLE_SMALL_GROUP_LEADER) {
            $this->selectedPermissions = array_values(array_diff(
                $this->selectedPermissions,
                self::SMALL_GROUP_LEADER_RESTRICTED_PERMISSIONS,
            ));
        }
    }

    private function configurableRoles(): array
    {
        return array_values(array_diff(User::ROLES, [User::ROLE_ADMIN]));
    }

    public function isRestrictedPermission(string $permission): bool
    {
        return $this->selectedRole === User::ROLE_SMALL_GROUP_LEADER
            && in_array($permission, self::SMALL_GROUP_LEADER_RESTRICTED_PERMISSIONS, true);
    }

    public function render()
    {
        return view('livewire.settings.manage-access-control', [
            'permissionGroups' => PermissionRegistry::GROUPS,
            'dependencies' => PermissionRegistry::DEPENDENCIES,
            'roles' => $this->configurableRoles(),
            'rolePermissionCounts' => RolePermission::query()
                ->whereIn('role', $this->configurableRoles())
                ->selectRaw('role, count(*) as total')
                ->groupBy('role')
                ->pluck('total', 'role'),
        ]);
    }
}
