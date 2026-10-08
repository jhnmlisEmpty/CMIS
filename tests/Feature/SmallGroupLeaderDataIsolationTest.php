<?php

namespace Tests\Feature;

use App\Livewire\Attendance\ViewEvent;
use App\Livewire\Lessons\ViewLesson;
use App\Livewire\Settings\ManageAccessControl;
use App\Livewire\SmallGroup\CreateSmallGroup;
use App\Livewire\SmallGroup\ManageMembers;
use App\Livewire\SmallGroup\UpdateSmallGroup;
use App\Livewire\User\CreateUser;
use App\Livewire\User\IndexUser;
use App\Models\Event;
use App\Models\Lesson;
use App\Models\RolePermission;
use App\Models\Role;
use App\Models\SmallGroup;
use App\Models\SmallGroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SmallGroupLeaderDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $leader;

    private User $otherLeader;

    private User $coLeader;

    private SmallGroup $elevate;

    private SmallGroup $otherGroup;

    private User $elevateMember;

    private User $inactiveElevateMember;

    private User $otherMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->leader = User::factory()->create(['role' => User::ROLE_SMALL_GROUP_LEADER]);
        $this->otherLeader = User::factory()->create(['role' => User::ROLE_SMALL_GROUP_LEADER]);
        $this->coLeader = User::factory()->create(['role' => User::ROLE_SMALL_GROUP_LEADER]);
        $this->elevate = SmallGroup::create(['name' => 'Elevate', 'status' => SmallGroup::STATUS_ACTIVE]);
        $this->elevate->leaders()->attach([$this->leader->id, $this->coLeader->id]);
        $this->otherGroup = SmallGroup::create(['name' => 'Other Group', 'status' => SmallGroup::STATUS_ACTIVE]);
        $this->otherGroup->leaders()->attach($this->otherLeader);
        $this->elevateMember = User::factory()->create(['name' => 'Elevate Member']);
        $this->inactiveElevateMember = User::factory()->create(['name' => 'Inactive Elevate Member']);
        $this->otherMember = User::factory()->create(['name' => 'Other Member']);

        SmallGroupMember::create(['small_group_id' => $this->elevate->id, 'user_id' => $this->elevateMember->id, 'status' => SmallGroupMember::STATUS_ACTIVE, 'joined_at' => now()]);
        SmallGroupMember::create(['small_group_id' => $this->elevate->id, 'user_id' => $this->inactiveElevateMember->id, 'status' => SmallGroupMember::STATUS_INACTIVE, 'joined_at' => now()]);
        SmallGroupMember::create(['small_group_id' => $this->otherGroup->id, 'user_id' => $this->otherMember->id, 'status' => SmallGroupMember::STATUS_ACTIVE, 'joined_at' => now()]);
    }

    public function test_leader_member_and_group_access_is_controlled_by_permissions(): void
    {
        $this->grant('users.view', 'users.update', 'small_groups.view', 'group_members.view');

        Livewire::actingAs($this->leader)
            ->test(IndexUser::class)
            ->assertViewHas('users', function ($users): bool {
                $ids = $users->pluck('id');

                return $ids->contains($this->leader->id)
                    && $ids->contains($this->elevateMember->id)
                    && $ids->contains($this->inactiveElevateMember->id)
                    && $ids->contains($this->otherMember->id);
            });

        $this->actingAs($this->leader)->get(route('users.show', $this->elevateMember))->assertOk();
        $this->actingAs($this->leader)->get(route('users.show', $this->otherMember))->assertOk();
        $this->actingAs($this->leader)->get(route('users.edit', $this->otherMember))->assertOk();
        $this->actingAs($this->leader)->get(route('small-groups.show', $this->otherGroup))->assertOk();

        $this->actingAs($this->coLeader)->get(route('small-groups.show', $this->elevate))->assertOk();
        $this->actingAs($this->coLeader)->get(route('users.show', $this->elevateMember))->assertOk();
    }

    public function test_leader_export_and_attendance_follow_permissions_without_role_scoping(): void
    {
        $this->grant('users.view', 'users.export', 'events.view', 'attendance.view', 'attendance.record');

        $csv = $this->actingAs($this->leader)->get(route('users.export'))->streamedContent();
        $this->assertStringContainsString('Elevate Member', $csv);
        $this->assertStringContainsString('Other Member', $csv);

        $event = Event::create(['title' => 'Gathering', 'event_date' => now(), 'location' => 'Hall', 'event_type' => 'service']);

        $component = Livewire::actingAs($this->leader)
            ->test(ViewEvent::class, ['event' => $event])
            ->set('searchName', 'Other Member');

        $this->assertTrue($component->instance()->getSearchResults()->contains('id', $this->otherMember->id));

        $component
            ->call('checkInByUserId', $this->otherMember->id)
            ->assertSet('messageType', 'success');

        $this->assertDatabaseHas('attendances', ['event_id' => $event->id, 'user_id' => $this->otherMember->id]);
    }

    public function test_group_member_management_requires_access_control_permissions(): void
    {
        $this->grant('users.view', 'users.create', 'small_groups.view', 'group_members.view');
        $availableMember = User::factory()->create(['name' => 'Available Member']);
        $memberLeader = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $this->elevate->leaders()->attach($memberLeader);

        $this->actingAs($this->leader)->get(route('users.create'))->assertOk();

        Livewire::actingAs($this->leader)
            ->test(CreateUser::class)
            ->assertOk();

        Livewire::actingAs($this->leader)
            ->test(ManageMembers::class, ['smallGroup' => $this->elevate])
            ->assertViewHas('canAddMembers', false)
            ->call('addMember', $availableMember->id)
            ->assertForbidden();

        $this->grant('group_members.add');

        Livewire::actingAs($this->otherLeader)
            ->test(ManageMembers::class, ['smallGroup' => $this->otherGroup])
            ->assertViewHas('canAddMembers', true)
            ->call('addMember', $availableMember->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('small_group_members', [
            'small_group_id' => $this->otherGroup->id,
            'user_id' => $availableMember->id,
            'status' => SmallGroupMember::STATUS_ACTIVE,
        ]);

        Livewire::actingAs($memberLeader)
            ->test(ManageMembers::class, ['smallGroup' => $this->elevate])
            ->assertForbidden();
    }

    public function test_lesson_progress_group_selector_follows_access_control_permissions(): void
    {
        $this->grant('lessons.view', 'small_groups.view', 'lesson_progress.view', 'lesson_progress.update');
        $lesson = Lesson::create(['title' => 'Shared Lesson', 'order' => 1, 'status' => Lesson::STATUS_PUBLISHED]);

        Livewire::actingAs($this->leader)
            ->test(ViewLesson::class, ['lesson' => $lesson])
            ->assertViewHas('groups', fn ($groups) => $groups->pluck('id')->contains($this->elevate->id)
                && $groups->pluck('id')->contains($this->otherGroup->id))
            ->set('groupId', $this->otherGroup->id)
            ->call('updateMemberProgress', $this->otherGroup->members()->firstOrFail()->id, 'completed')
            ->assertHasNoErrors();
    }

    public function test_active_users_from_any_role_can_be_assigned_as_group_leaders(): void
    {
        $admin = User::factory()->admin()->create();
        $pastor = User::factory()->pastor()->create();
        $ministryHead = User::factory()->create(['role' => User::ROLE_MINISTRY_HEAD]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $inactiveMember = User::factory()->inactive()->create(['role' => User::ROLE_MEMBER]);
        $leaderIds = [$admin->id, $pastor->id, $ministryHead->id, $this->leader->id, $member->id];

        Livewire::actingAs($admin)
            ->test(CreateSmallGroup::class)
            ->assertViewHas('users', fn ($users) => collect($leaderIds)->diff($users->pluck('id'))->isEmpty()
                && ! $users->pluck('id')->contains($inactiveMember->id)
                && $users->every(fn ($user) => $user->isActive()))
            ->set('name', 'New Group')
            ->set('leader_ids', $leaderIds)
            ->set('status', SmallGroup::STATUS_ACTIVE)
            ->call('save')
            ->assertHasNoErrors();

        $newGroup = SmallGroup::where('name', 'New Group')->firstOrFail();
        $this->assertEqualsCanonicalizing($leaderIds, $newGroup->leaders()->pluck('users.id')->all());

        Livewire::actingAs($admin)
            ->test(CreateSmallGroup::class)
            ->set('name', 'Inactive Leader Group')
            ->set('leader_ids', [$inactiveMember->id])
            ->set('status', SmallGroup::STATUS_ACTIVE)
            ->call('save')
            ->assertHasErrors(['leader_ids.0']);
    }

    public function test_leader_picker_search_filters_users_without_clearing_selections(): void
    {
        $admin = User::factory()->admin()->create();
        $pastor = User::factory()->pastor()->create([
            'name' => 'Searchable Pastor',
            'email' => 'searchable@example.com',
        ]);
        $ministryHead = User::factory()->create([
            'name' => 'Ministry Coordinator',
            'role' => User::ROLE_MINISTRY_HEAD,
        ]);

        Livewire::actingAs($admin)
            ->test(CreateSmallGroup::class)
            ->set('leader_ids', [$pastor->id])
            ->set('leaderSearch', 'searchable@example.com')
            ->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$pastor->id])
            ->assertSet('leader_ids', [$pastor->id])
            ->set('leaderSearch', 'ministry head')
            ->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$ministryHead->id])
            ->assertSet('leader_ids', [$pastor->id]);

        Livewire::actingAs($admin)
            ->test(UpdateSmallGroup::class, ['smallGroup' => $this->elevate])
            ->set('leaderSearch', 'no matching user')
            ->assertViewHas('users', fn ($users) => $users->isEmpty())
            ->assertSet('leader_ids', fn ($leaderIds) => collect($leaderIds)->sort()->values()->all() === collect([$this->leader->id, $this->coLeader->id])->sort()->values()->all());
    }

    public function test_group_requires_at_least_one_leader_and_syncs_multiple_leaders(): void
    {
        $admin = User::factory()->admin()->create();
        $thirdLeader = User::factory()->create(['role' => User::ROLE_MEMBER]);

        Livewire::actingAs($admin)
            ->test(CreateSmallGroup::class)
            ->set('name', 'Leaderless Group')
            ->set('leader_ids', [])
            ->set('status', SmallGroup::STATUS_ACTIVE)
            ->call('save')
            ->assertHasErrors(['leader_ids']);

        Livewire::actingAs($admin)
            ->test(UpdateSmallGroup::class, ['smallGroup' => $this->elevate])
            ->assertSet('leader_ids', fn ($leaderIds) => collect($leaderIds)->sort()->values()->all() === collect([$this->leader->id, $this->coLeader->id])->sort()->values()->all())
            ->set('leader_ids', [$this->coLeader->id, $thirdLeader->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('small_group_leaders', [
            'small_group_id' => $this->elevate->id,
            'user_id' => $this->leader->id,
        ]);
        $this->assertDatabaseHas('small_group_leaders', [
            'small_group_id' => $this->elevate->id,
            'user_id' => $thirdLeader->id,
        ]);
    }

    public function test_assigned_leader_can_change_role_but_cannot_be_deactivated_or_deleted(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(\App\Livewire\User\UpdateUser::class, ['user' => $this->leader])
            ->assertSet('leaderStatusLocked', true)
            ->assertSee('Status is locked')
            ->assertSee('Elevate')
            ->set('role', User::ROLE_MEMBER)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(User::ROLE_MEMBER, $this->leader->fresh()->role);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\User\UpdateUser::class, ['user' => $this->leader->fresh()])
            ->assertSet('leaderStatusLocked', true)
            ->set('status', User::STATUS_INACTIVE)
            ->call('save')
            ->assertStatus(422);

        Livewire::actingAs($admin)
            ->test(IndexUser::class)
            ->call('deleteUser', $this->leader->id)
            ->assertStatus(422);

        $this->assertSame(User::STATUS_ACTIVE, $this->leader->fresh()->status);
        $this->assertDatabaseHas('users', ['id' => $this->leader->id]);
    }

    public function test_access_control_can_assign_all_registered_permissions_to_small_group_leaders(): void
    {
        $admin = User::factory()->admin()->create();
        $leaderRoleId = Role::where('slug', Role::SMALL_GROUP_LEADER)->value('id');
        RolePermission::create(['role' => User::ROLE_SMALL_GROUP_LEADER, 'permission' => 'users.create']);
        RolePermission::create(['role' => User::ROLE_SMALL_GROUP_LEADER, 'permission' => 'group_members.add']);

        Livewire::actingAs($admin)
            ->test(ManageAccessControl::class)
            ->set('selectedRole', User::ROLE_SMALL_GROUP_LEADER)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_permissions', ['role_id' => $leaderRoleId, 'permission' => 'users.create']);
        $this->assertDatabaseHas('role_permissions', ['role_id' => $leaderRoleId, 'permission' => 'group_members.add']);
    }

    private function grant(string ...$permissions): void
    {
        $roleId = Role::where('slug', Role::SMALL_GROUP_LEADER)->value('id');
        foreach ($permissions as $permission) {
            RolePermission::firstOrCreate(['role_id' => $roleId, 'permission' => $permission]);
        }
    }
}
