<?php

namespace Tests\Feature;

use App\Livewire\Attendance\IndexEvent;
use App\Livewire\Settings\ManageAccessControl;
use App\Livewire\User\CreateUser;
use App\Livewire\User\UpdateUser;
use App\Models\Event;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RolePermissionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_has_full_access_and_only_admin_can_manage_permissions(): void
    {
        $admin = User::factory()->admin()->create();
        $pastor = User::factory()->pastor()->create();

        $this->actingAs($admin)->get(route('settings.access-control'))->assertOk();
        $this->actingAs($admin)->get(route('events.create'))->assertOk();
        $this->actingAs($pastor)->get(route('settings.access-control'))->assertForbidden();
        $this->actingAs($pastor)->get(route('events.index'))->assertForbidden();
    }

    public function test_admin_can_assign_permissions_and_dependencies_to_a_role(): void
    {
        $admin = User::factory()->admin()->create();
        $pastor = User::factory()->pastor()->create();

        Livewire::actingAs($admin)
            ->test(ManageAccessControl::class)
            ->set('selectedRole', User::ROLE_PASTOR)
            ->call('togglePermission', 'events.create')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_permissions', ['role' => User::ROLE_PASTOR, 'permission' => 'events.create']);
        $this->assertDatabaseHas('role_permissions', ['role' => User::ROLE_PASTOR, 'permission' => 'events.view']);
        $this->actingAs($pastor)->get(route('events.create'))->assertOk();
    }

    public function test_unchecked_permission_blocks_livewire_mutation(): void
    {
        $pastor = User::factory()->pastor()->create();
        RolePermission::create(['role' => User::ROLE_PASTOR, 'permission' => 'events.view']);
        $event = Event::create([
            'title' => 'Sunday Gathering',
            'event_date' => now()->addDay(),
            'location' => 'Main Hall',
            'event_type' => 'service',
        ]);

        Livewire::actingAs($pastor)
            ->test(IndexEvent::class)
            ->call('deleteEvent', $event->id)
            ->assertForbidden();

        $this->assertDatabaseHas('events', ['id' => $event->id]);
    }

    public function test_user_without_role_assignment_permission_cannot_forge_a_role(): void
    {
        $pastor = User::factory()->pastor()->create();
        foreach (['users.view', 'users.create', 'users.update'] as $permission) {
            RolePermission::create(['role' => User::ROLE_PASTOR, 'permission' => $permission]);
        }

        Livewire::actingAs($pastor)
            ->test(CreateUser::class)
            ->set('name', 'New Person')
            ->set('email', 'new-person@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('gender', User::GENDER_FEMALE)
            ->set('role', User::ROLE_ADMIN)
            ->set('status', User::STATUS_INACTIVE)
            ->call('save')
            ->assertHasNoErrors();

        $created = User::where('email', 'new-person@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_MEMBER, $created->role);
        $this->assertSame(User::STATUS_INACTIVE, $created->status);

        Livewire::actingAs($pastor)
            ->test(UpdateUser::class, ['user' => $created])
            ->set('role', User::ROLE_ADMIN)
            ->set('status', User::STATUS_ACTIVE)
            ->call('save')
            ->assertHasNoErrors();

        $created->refresh();
        $this->assertSame(User::ROLE_MEMBER, $created->role);
        $this->assertSame(User::STATUS_ACTIVE, $created->status);
    }

    public function test_admin_cannot_delete_the_final_active_admin(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(\App\Livewire\User\IndexUser::class)
            ->call('deleteUser', $admin->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
