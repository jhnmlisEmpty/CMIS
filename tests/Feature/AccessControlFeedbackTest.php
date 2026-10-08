<?php

namespace Tests\Feature;

use App\Livewire\Settings\ManageAccessControl;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccessControlFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_dependency_adjustment_explains_why_member_visibility_changes(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::where('slug', Role::PASTOR)->firstOrFail();

        Livewire::actingAs($admin)->test(ManageAccessControl::class)
            ->set('selectedRoleId', $role->id)
            ->set('permissionScopeInputs.users__view', 'associated')
            ->call('togglePermission', 'group_members.add')
            ->assertSet('permissionScopeInputs.users__view', 'associated')
            ->assertSee('View members will change from Associated only to All records')
            ->assertSee('required by Add members')
            ->assertSee('Unsaved changes')
            ->call('save')
            ->assertSet('showPermissionConfirmation', true)
            ->call('confirmPermissionSave')
            ->assertHasNoErrors()
            ->assertSet('permissionsDirty', false);

        Livewire::actingAs($admin)->test(ManageAccessControl::class)
            ->set('selectedRoleId', $role->id)
            ->assertSet('permissionScopeInputs.users__view', 'all')
            ->assertSet('permissionScopeInputs.small_groups__view', 'associated')
            ->assertSet('selectedPermissionScopes', fn (array $scopes): bool => ($scopes['group_members__add'] ?? null) === 'associated');
    }

    public function test_reducing_visibility_explains_removal_of_add_members_and_persists(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::where('slug', Role::PASTOR)->firstOrFail();
        Livewire::actingAs($admin)->test(ManageAccessControl::class)
            ->set('selectedRoleId', $role->id)
            ->call('togglePermission', 'group_members.add')
            ->set('permissionScopeInputs.users__view', 'associated')
            ->assertSet('permissionScopeInputs.users__view', 'associated')
            ->assertSet('selectedPermissionScopes', fn (array $scopes): bool => ($scopes['group_members__add'] ?? null) === 'associated')
            ->assertSee('View members will change from Associated only to All records')
            ->call('togglePermission', 'group_members.add')
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseMissing('role_permissions', ['role_id' => $role->id, 'permission' => 'group_members.add']);
        $this->assertDatabaseHas('role_permissions', ['role_id' => $role->id, 'permission' => 'users.view', 'access_scope' => 'associated']);
    }

    public function test_failed_save_rolls_back_and_keeps_draft_with_visible_error(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::where('slug', Role::PASTOR)->firstOrFail();
        RolePermission::create(['role_id' => $role->id, 'permission' => 'users.view', 'access_scope' => 'associated']);
        $this->mock(AuditLogger::class, function ($mock): void {
            $mock->shouldReceive('log')->once()->andThrow(new \RuntimeException('Simulated audit storage failure'));
        });

        Livewire::actingAs($admin)->test(ManageAccessControl::class)
            ->set('selectedRoleId', $role->id)
            ->set('permissionScopeInputs.users__view', 'all')
            ->call('save')
            ->assertHasErrors('permissions')
            ->assertSee('Permissions were not saved')
            ->assertDontSee('Permissions updated for')
            ->assertSet('permissionScopeInputs.users__view', 'all')
            ->assertSet('permissionsDirty', true);

        $this->assertDatabaseHas('role_permissions', ['role_id' => $role->id, 'permission' => 'users.view', 'access_scope' => 'associated']);
        $this->assertDatabaseMissing('role_permissions', ['role_id' => $role->id, 'permission' => 'users.view', 'access_scope' => 'all']);
    }

    public function test_checked_permissions_do_not_revert_during_later_interactions(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::where('slug', Role::PASTOR)->firstOrFail();

        Livewire::actingAs($admin)->test(ManageAccessControl::class)
            ->set('selectedRoleId', $role->id)
            ->call('setPermissionEnabled', 'events.view', true)
            ->call('setPermissionEnabled', 'events.view', true)
            ->call('togglePermission', 'lessons.view')
            ->call('togglePermission', 'dashboard.view')
            ->call('togglePermission', 'events.create')
            ->call('togglePermission', 'lessons.update')
            ->assertSet('selectedPermissions', fn (array $permissions): bool => collect([
                'events.view', 'lessons.view', 'dashboard.view', 'events.create', 'lessons.update',
            ])->every(fn (string $permission): bool => in_array($permission, $permissions, true)))
            ->assertSet('selectedPermissionScopes', fn (array $scopes): bool => collect(array_keys($scopes))->every(
                fn (string $key): bool => ! str_contains($key, '.'),
            ))
            ->call('save')
            ->assertHasNoErrors();

        foreach (['events.view', 'lessons.view', 'dashboard.view', 'events.create', 'lessons.update'] as $permission) {
            $this->assertDatabaseHas('role_permissions', ['role_id' => $role->id, 'permission' => $permission]);
        }

        $requested = AuditLog::where('module', 'access_control')->latest('id')->firstOrFail()->new_values['requested_permissions'];
        $this->assertTrue(collect($requested)->every(fn ($scope): bool => is_string($scope)));
    }
}
