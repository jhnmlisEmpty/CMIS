<?php

namespace Tests\Feature;

use App\Livewire\Settings\ManageAccessControl;
use App\Livewire\User\CreateUser;
use App\Livewire\User\UpdateUser;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Tests\TestCase;

class DynamicRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_rename_and_delete_an_unused_custom_role(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(ManageAccessControl::class)
            ->set('newRoleName', 'Worship Coordinator')
            ->call('createRole')
            ->assertHasNoErrors()
            ->set('editingRoleName', 'Worship Director')
            ->call('renameRole')
            ->assertHasNoErrors();

        $role = Role::where('slug', 'worship_coordinator')->firstOrFail();
        $this->assertSame('Worship Director', $role->name);
        $this->assertFalse($role->is_system);
        $this->assertSame(0, $role->permissions()->count());

        Livewire::actingAs($admin)
            ->test(ManageAccessControl::class)
            ->set('selectedRoleId', $role->id)
            ->call('deleteRole')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_built_in_roles_can_be_renamed_and_deleted_when_unused(): void
    {
        $admin = User::factory()->admin()->create();
        $adminRole = Role::where('slug', Role::ADMIN)->firstOrFail();
        $pastor = Role::where('slug', Role::PASTOR)->firstOrFail();

        Livewire::actingAs($admin)
            ->test(ManageAccessControl::class)
            ->set('selectedRoleId', $adminRole->id)
            ->call('beginRename')
            ->set('editingRoleName', 'Church Administrator')
            ->call('renameRole')
            ->assertHasNoErrors();

        $this->assertSame('Church Administrator', $adminRole->fresh()->name);
        $this->assertTrue($admin->fresh()->isAdmin());

        Livewire::actingAs($admin)
            ->test(ManageAccessControl::class)
            ->set('selectedRoleId', $pastor->id)
            ->call('beginRename')
            ->set('editingRoleName', 'Senior Pastor')
            ->call('renameRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', ['id' => $pastor->id, 'name' => 'Senior Pastor', 'is_system' => true]);

        Livewire::actingAs($admin)
            ->test(ManageAccessControl::class)
            ->set('selectedRoleId', $pastor->id)
            ->call('deleteRole')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('roles', ['id' => $pastor->id]);
    }

    public function test_deleting_an_assigned_role_detaches_it_and_preserves_a_role_for_every_member(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $singleRoleMember = User::factory()->create();
        $custom = Role::create(['name' => 'Care Coordinator', 'slug' => 'care_coordinator']);
        $member->roles()->attach($custom->id);
        $singleRoleMember->roles()->sync([$custom->id]);

        Livewire::actingAs($admin)
            ->test(ManageAccessControl::class)
            ->set('selectedRoleId', $custom->id)
            ->call('deleteRole')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('roles', ['id' => $custom->id]);
        $this->assertTrue($member->fresh()->hasRole(Role::MEMBER));
        $this->assertTrue($singleRoleMember->fresh()->hasRole(Role::MEMBER));
        $this->assertGreaterThanOrEqual(1, $member->fresh()->roles()->count());
        $this->assertGreaterThanOrEqual(1, $singleRoleMember->fresh()->roles()->count());
    }

    public function test_multiple_roles_union_permissions_without_role_specific_data_scope(): void
    {
        $custom = Role::create(['name' => 'Event Coordinator', 'slug' => 'event_coordinator']);
        RolePermission::create(['role_id' => $custom->id, 'permission' => 'events.view']);
        RolePermission::create(['role_id' => $custom->id, 'permission' => 'events.create']);
        RolePermission::create(['role_id' => $custom->id, 'permission' => 'users.view']);

        $leader = User::factory()->create(['role' => Role::SMALL_GROUP_LEADER]);
        $otherMember = User::factory()->create();
        $memberRoleId = Role::where('slug', Role::MEMBER)->value('id');
        $leader->roles()->sync([$memberRoleId, $custom->id, Role::where('slug', Role::SMALL_GROUP_LEADER)->value('id')]);
        $leader->unsetRelation('roles');

        $this->assertTrue($leader->hasRole(Role::SMALL_GROUP_LEADER));
        $this->assertTrue($leader->hasPermission('events.create'));
        $this->assertTrue(Gate::forUser($leader)->allows('events.create'));
        $this->assertTrue(User::query()->visibleTo($leader)->whereKey($otherMember)->exists());
    }

    public function test_member_forms_assign_multiple_roles_and_require_at_least_one(): void
    {
        $admin = User::factory()->admin()->create();
        $memberRole = Role::where('slug', Role::MEMBER)->firstOrFail();
        $custom = Role::create(['name' => 'Prayer Team', 'slug' => 'prayer_team']);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->set('name', 'Multi Role Member')
            ->set('email', 'multi-role@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('gender', User::GENDER_FEMALE)
            ->set('role_ids', [$memberRole->id, $custom->id])
            ->call('save')
            ->assertHasNoErrors();

        $created = User::where('email', 'multi-role@example.com')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [Role::MEMBER, 'prayer_team'],
            $created->roles()->pluck('slug')->all(),
        );

        Livewire::actingAs($admin)
            ->test(UpdateUser::class, ['user' => $created])
            ->set('role_ids', [])
            ->call('save')
            ->assertHasErrors(['role_ids']);
    }

    public function test_final_active_admin_cannot_lose_admin_among_multiple_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $memberRole = Role::where('slug', Role::MEMBER)->firstOrFail();
        $admin->roles()->attach($memberRole->id);

        Livewire::actingAs($admin)
            ->test(UpdateUser::class, ['user' => $admin])
            ->set('role_ids', [$memberRole->id])
            ->call('save')
            ->assertStatus(422);

        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_role_list_shows_the_number_of_assigned_members(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::create(['name' => 'Welcome Team', 'slug' => 'welcome_team']);
        $members = User::factory(2)->create();
        foreach ($members as $member) {
            $member->roles()->attach($role->id);
        }

        Livewire::actingAs($admin)
            ->test(ManageAccessControl::class)
            ->assertSee('Welcome Team')
            ->assertSee('2 members');
    }

    public function test_member_is_the_only_default_role_and_cannot_be_deleted(): void
    {
        $admin = User::factory()->admin()->create();
        $memberRole = Role::where('slug', Role::MEMBER)->firstOrFail();
        Role::create(['name' => 'Unassigned Custom Role', 'slug' => 'unassigned_custom_role']);

        $user = User::factory()->create();

        $this->assertTrue($user->hasRole(Role::MEMBER));
        $this->assertSame($memberRole->id, Role::defaultForNewUsers()?->id);
        $this->assertNull(Role::defaultForNewUsers($memberRole->id));

        Livewire::actingAs($admin)
            ->test(ManageAccessControl::class)
            ->set('selectedRoleId', $memberRole->id)
            ->call('deleteRole')
            ->assertStatus(422);

        $this->assertDatabaseHas('roles', ['id' => $memberRole->id, 'slug' => Role::MEMBER]);
    }
}
