<?php

namespace Tests\Feature;

use App\Livewire\Attendance\ViewEvent;
use App\Livewire\Settings\ManageAccessControl;
use App\Livewire\SmallGroup\IndexSmallGroup;
use App\Livewire\User\IndexUser;
use App\Models\Event;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\SmallGroup;
use App\Models\SmallGroupMember;
use App\Models\User;
use App\Support\PermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssociatedAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_associated_member_and_group_access_only_exposes_led_group_data_and_self(): void
    {
        $leader = User::factory()->create(['role' => Role::SMALL_GROUP_LEADER]);
        $otherLeader = User::factory()->create(['role' => Role::SMALL_GROUP_LEADER]);
        $ledGroup = SmallGroup::create(['name' => 'Led Group', 'status' => SmallGroup::STATUS_ACTIVE]);
        $ledGroup->leaders()->attach($leader);
        $otherGroup = SmallGroup::create(['name' => 'Other Group', 'status' => SmallGroup::STATUS_ACTIVE]);
        $otherGroup->leaders()->attach($otherLeader);
        $associatedMember = User::factory()->create(['name' => 'Associated Member']);
        $otherMember = User::factory()->create(['name' => 'Other Member']);
        SmallGroupMember::create(['small_group_id' => $ledGroup->id, 'user_id' => $associatedMember->id, 'status' => SmallGroupMember::STATUS_INACTIVE]);
        SmallGroupMember::create(['small_group_id' => $otherGroup->id, 'user_id' => $otherMember->id, 'status' => SmallGroupMember::STATUS_ACTIVE]);

        $this->grant(Role::SMALL_GROUP_LEADER, PermissionRegistry::SCOPE_ASSOCIATED,
            'users.view', 'users.update', 'users.delete', 'users.export',
            'small_groups.view', 'small_groups.update', 'small_groups.delete');

        Livewire::actingAs($leader)->test(IndexUser::class)
            ->assertViewHas('users', fn ($users) => $users->pluck('id')->contains($leader->id)
                && $users->pluck('id')->contains($associatedMember->id)
                && ! $users->pluck('id')->contains($otherMember->id));

        Livewire::actingAs($leader)->test(IndexSmallGroup::class)
            ->assertViewHas('smallGroups', fn ($groups) => $groups->pluck('id')->all() === [$ledGroup->id]);

        $this->actingAs($leader)->get(route('users.show', $associatedMember))->assertOk();
        $this->actingAs($leader)->get(route('users.show', $otherMember))->assertForbidden();
        $this->actingAs($leader)->get(route('users.edit', $otherMember))->assertForbidden();
        $this->actingAs($leader)->get(route('small-groups.show', $otherGroup))->assertForbidden();

        $csv = $this->actingAs($leader)->get(route('users.export'))->streamedContent();
        $this->assertStringContainsString('Associated Member', $csv);
        $this->assertStringNotContainsString('Other Member', $csv);
    }

    public function test_associated_attendance_scope_limits_search_and_check_in_targets(): void
    {
        $leader = User::factory()->create(['role' => Role::SMALL_GROUP_LEADER]);
        $group = SmallGroup::create(['name' => 'Led Group', 'status' => SmallGroup::STATUS_ACTIVE]);
        $group->leaders()->attach($leader);
        $associatedMember = User::factory()->create(['name' => 'Associated Member']);
        $otherMember = User::factory()->create(['name' => 'Other Member']);
        SmallGroupMember::create(['small_group_id' => $group->id, 'user_id' => $associatedMember->id, 'status' => SmallGroupMember::STATUS_ACTIVE]);
        $this->grant(Role::SMALL_GROUP_LEADER, PermissionRegistry::SCOPE_ASSOCIATED, 'attendance.view', 'attendance.record');
        $this->grant(Role::SMALL_GROUP_LEADER, PermissionRegistry::SCOPE_ALL, 'events.view');
        $event = Event::create(['title' => 'Gathering', 'event_date' => now(), 'location' => 'Hall', 'event_type' => 'service']);

        $component = Livewire::actingAs($leader)->test(ViewEvent::class, ['event' => $event]);
        $component->set('searchName', 'Associated Member');
        $this->assertTrue($component->instance()->getSearchResults()->contains('id', $associatedMember->id));
        $component->set('searchName', 'Other Member');
        $this->assertTrue($component->instance()->getSearchResults()->isEmpty());
        $component->call('checkInByUserId', $otherMember->id)->assertSet('messageType', 'error');
        $this->assertDatabaseMissing('attendances', ['event_id' => $event->id, 'user_id' => $otherMember->id]);
    }

    public function test_multiple_roles_use_the_broadest_permission_scope(): void
    {
        $associatedRole = Role::create(['name' => 'Associated', 'slug' => 'associated']);
        $allRole = Role::create(['name' => 'All Members', 'slug' => 'all_members']);
        RolePermission::create(['role_id' => $associatedRole->id, 'permission' => 'users.view', 'access_scope' => 'associated']);
        RolePermission::create(['role_id' => $allRole->id, 'permission' => 'users.view', 'access_scope' => 'all']);
        $user = User::factory()->create();
        $user->roles()->sync([$associatedRole->id, $allRole->id]);

        $this->assertSame(PermissionRegistry::SCOPE_ALL, $user->permissionScope('users.view'));
    }

    public function test_broadest_core_scope_applies_to_a_related_feature_from_another_role(): void
    {
        $editorRole = Role::create(['name' => 'Associated Editor', 'slug' => 'associated_editor']);
        $viewerRole = Role::create(['name' => 'All Members', 'slug' => 'all_members']);
        RolePermission::create(['role_id' => $editorRole->id, 'permission' => 'users.view', 'access_scope' => 'associated']);
        RolePermission::create(['role_id' => $editorRole->id, 'permission' => 'users.update', 'access_scope' => 'associated']);
        RolePermission::create(['role_id' => $viewerRole->id, 'permission' => 'users.view', 'access_scope' => 'all']);
        $user = User::factory()->create();
        $user->roles()->sync([$editorRole->id, $viewerRole->id]);

        $this->assertSame(PermissionRegistry::SCOPE_ALL, $user->permissionScope('users.update'));
    }

    public function test_access_control_persists_scopes_and_matches_dependency_scope(): void
    {
        $admin = User::factory()->admin()->create();
        $pastorRole = Role::where('slug', Role::PASTOR)->firstOrFail();

        Livewire::actingAs($admin)
            ->test(ManageAccessControl::class)
            ->set('selectedRole', Role::PASTOR)
            ->call('setPermissionScope', 'users.view', PermissionRegistry::SCOPE_ASSOCIATED)
            ->call('togglePermission', 'users.update')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $pastorRole->id,
            'permission' => 'users.update',
            'access_scope' => PermissionRegistry::SCOPE_ASSOCIATED,
        ]);
        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $pastorRole->id,
            'permission' => 'users.view',
            'access_scope' => PermissionRegistry::SCOPE_ASSOCIATED,
        ]);
    }

    public function test_scope_selector_binding_keeps_changed_value_and_persists_it(): void
    {
        $admin = User::factory()->admin()->create();
        $pastorRole = Role::where('slug', Role::PASTOR)->firstOrFail();

        Livewire::actingAs($admin)
            ->test(ManageAccessControl::class)
            ->set('selectedRoleId', $pastorRole->id)
            ->set('permissionScopeInputs.users__view', PermissionRegistry::SCOPE_ASSOCIATED)
            ->call('togglePermission', 'users.update')
            ->assertSet('permissionScopeInputs.users__view', PermissionRegistry::SCOPE_ASSOCIATED)
            ->assertSet('selectedPermissionScopes', fn (array $scopes): bool => ($scopes['users__update'] ?? null) === PermissionRegistry::SCOPE_ASSOCIATED)
            ->call('save')
            ->assertSet('permissionScopeInputs.users__view', PermissionRegistry::SCOPE_ASSOCIATED)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_permissions', [
            'role_id' => $pastorRole->id,
            'permission' => 'users.update',
            'access_scope' => PermissionRegistry::SCOPE_ASSOCIATED,
        ]);
    }

    public function test_core_member_scope_applies_to_every_enabled_member_feature(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::where('slug', Role::PASTOR)->firstOrFail();

        Livewire::actingAs($admin)->test(ManageAccessControl::class)
            ->set('selectedRoleId', $role->id)
            ->set('permissionScopeInputs.users__view', PermissionRegistry::SCOPE_ASSOCIATED)
            ->call('togglePermission', 'users.update')
            ->call('togglePermission', 'users.delete')
            ->set('permissionScopeInputs.users__view', PermissionRegistry::SCOPE_ALL)
            ->assertSet('selectedPermissionScopes', fn (array $scopes): bool => ($scopes['users__update'] ?? null) === PermissionRegistry::SCOPE_ALL
                && ($scopes['users__delete'] ?? null) === PermissionRegistry::SCOPE_ALL)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_permissions', ['role_id' => $role->id, 'permission' => 'users.update', 'access_scope' => 'all']);
        $this->assertDatabaseHas('role_permissions', ['role_id' => $role->id, 'permission' => 'users.delete', 'access_scope' => 'all']);
    }

    public function test_no_core_access_disables_all_related_features(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::where('slug', Role::PASTOR)->firstOrFail();

        Livewire::actingAs($admin)->test(ManageAccessControl::class)
            ->set('selectedRoleId', $role->id)
            ->set('permissionScopeInputs.users__view', PermissionRegistry::SCOPE_ASSOCIATED)
            ->call('togglePermission', 'users.update')
            ->set('permissionScopeInputs.users__view', 'none')
            ->assertSet('selectedPermissionScopes', fn (array $scopes): bool => collect(array_keys($scopes))->filter(fn (string $permission): bool => str_starts_with($permission, 'users__'))->isEmpty())
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('role_permissions', ['role_id' => $role->id, 'permission' => 'users.view']);
        $this->assertDatabaseMissing('role_permissions', ['role_id' => $role->id, 'permission' => 'users.update']);
    }

    public function test_add_member_permission_requires_all_member_visibility(): void
    {
        $resolved = PermissionRegistry::withScopedDependencies([
            'group_members.add' => PermissionRegistry::SCOPE_ASSOCIATED,
        ]);

        $this->assertSame(PermissionRegistry::SCOPE_ASSOCIATED, $resolved['group_members.add']);
        $this->assertSame(PermissionRegistry::SCOPE_ASSOCIATED, $resolved['group_members.view']);
        $this->assertSame(PermissionRegistry::SCOPE_ASSOCIATED, $resolved['small_groups.view']);
        $this->assertSame(PermissionRegistry::SCOPE_ALL, $resolved['users.view']);
    }

    private function grant(string $roleSlug, string $scope, string ...$permissions): void
    {
        $roleId = Role::where('slug', $roleSlug)->value('id');
        foreach ($permissions as $permission) {
            RolePermission::create([
                'role_id' => $roleId,
                'permission' => $permission,
                'access_scope' => $scope,
            ]);
        }
    }
}
