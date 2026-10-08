<?php

namespace Tests\Feature;

use App\Livewire\Settings\ManageAccessControl;
use App\Livewire\User\CreateUser;
use App\Livewire\User\UpdateUser;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\PermissionResolver;
use App\Support\PermissionRegistry;
use Database\Seeders\CompleteDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AccessControlHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_resolver_reports_required_scope_changes_without_mutating_request(): void
    {
        $resolution = app(PermissionResolver::class)->resolve([
            'group_members.add' => PermissionRegistry::SCOPE_ASSOCIATED,
            'users.view' => PermissionRegistry::SCOPE_ASSOCIATED,
        ]);

        $this->assertSame(PermissionRegistry::SCOPE_ASSOCIATED, $resolution->requested['users.view']);
        $this->assertSame(PermissionRegistry::SCOPE_ALL, $resolution->effective['users.view']);
        $this->assertContains('group_members.add', collect($resolution->adjustments)->firstWhere('permission', 'users.view')['required_by']);
    }

    public function test_non_admin_role_manager_cannot_grant_admin_to_self_or_a_new_user(): void
    {
        $manager = User::factory()->pastor()->create();
        $pastorRole = Role::where('slug', Role::PASTOR)->firstOrFail();
        foreach (['users.view', 'users.create', 'users.update', 'users.assign_roles'] as $permission) {
            RolePermission::create(['role_id' => $pastorRole->id, 'permission' => $permission, 'access_scope' => 'all']);
        }
        $adminRoleId = Role::where('slug', Role::ADMIN)->value('id');

        Livewire::actingAs($manager)->test(UpdateUser::class, ['user' => $manager])
            ->set('role_ids', [$adminRoleId])
            ->call('save')
            ->assertForbidden();
        $this->assertFalse($manager->fresh()->isAdmin());

        Livewire::actingAs($manager)->test(CreateUser::class)
            ->set('name', 'Forged Admin')
            ->set('email', 'forged-admin@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('gender', User::GENDER_MALE)
            ->set('role_ids', [$adminRoleId])
            ->call('save')
            ->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'forged-admin@example.com']);
    }

    public function test_private_member_photo_requires_authentication_and_record_scope(): void
    {
        Storage::fake('local');
        $leader = User::factory()->create(['role' => Role::SMALL_GROUP_LEADER]);
        $other = User::factory()->create(['profile_photo_path' => 'profile-photos/other.jpg']);
        Storage::disk('local')->put('profile-photos/other.jpg', 'photo');
        RolePermission::create([
            'role_id' => Role::where('slug', Role::SMALL_GROUP_LEADER)->value('id'),
            'permission' => 'users.view',
            'access_scope' => PermissionRegistry::SCOPE_ASSOCIATED,
        ]);

        $this->get(route('profile-photo', ['filename' => 'other.jpg']))->assertRedirect(route('login'));
        $this->actingAs($leader)->get(route('profile-photo', ['filename' => 'other.jpg']))->assertForbidden();
        $this->actingAs($other)->get(route('profile-photo', ['filename' => 'other.jpg']))->assertOk();
    }

    public function test_inactive_authenticated_user_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)->get(route('profile'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_permission_map_is_loaded_once_per_request_scope(): void
    {
        $user = User::factory()->pastor()->create();
        $roleId = Role::where('slug', Role::PASTOR)->value('id');
        RolePermission::create(['role_id' => $roleId, 'permission' => 'users.view', 'access_scope' => 'all']);
        RolePermission::create(['role_id' => $roleId, 'permission' => 'events.view', 'access_scope' => 'all']);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->assertTrue($user->hasPermission('users.view'));
        $this->assertTrue($user->hasPermission('events.view'));
        $this->assertLessThanOrEqual(2, count(DB::getQueryLog()));
    }

    public function test_concurrent_permission_editor_rejects_stale_save(): void
    {
        $admin = User::factory()->admin()->create();
        $role = Role::where('slug', Role::PASTOR)->firstOrFail();
        $first = Livewire::actingAs($admin)->test(ManageAccessControl::class)->set('selectedRoleId', $role->id);
        $second = Livewire::actingAs($admin)->test(ManageAccessControl::class)->set('selectedRoleId', $role->id);

        $first->call('togglePermission', 'dashboard.view')->call('save')->assertHasNoErrors();
        $second->call('togglePermission', 'events.view')->call('save')
            ->assertHasErrors('permissions')
            ->assertSee('changed by another administrator');
    }

    public function test_reseeding_preserves_saved_permissions_and_multiple_role_assignments(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@cmis.com',
            'birthdate' => '1988-04-13',
            'name' => 'Existing Administrator',
        ]);
        $custom = Role::create(['name' => 'Care Team', 'slug' => 'care_team']);
        $admin->roles()->attach($custom->id);
        $pastor = Role::where('slug', Role::PASTOR)->firstOrFail();
        RolePermission::create(['role_id' => $pastor->id, 'permission' => 'dashboard.view', 'access_scope' => 'associated']);

        $this->seed(CompleteDatabaseSeeder::class);

        $this->assertEqualsCanonicalizing(
            [Role::ADMIN, 'care_team'],
            $admin->fresh()->roles()->pluck('slug')->all(),
        );
        $this->assertSame('Existing Administrator', $admin->fresh()->name);
        $this->assertSame('1988-04-13', $admin->fresh()->birthdate->toDateString());
        $this->assertSame(
            ['dashboard.view' => 'associated'],
            $pastor->permissions()->pluck('access_scope', 'permission')->all(),
        );
    }
}
