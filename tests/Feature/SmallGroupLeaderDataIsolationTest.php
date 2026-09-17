<?php

namespace Tests\Feature;

use App\Livewire\Attendance\ViewEvent;
use App\Livewire\Lessons\ViewLesson;
use App\Livewire\Settings\ManageAccessControl;
use App\Livewire\SmallGroup\CreateSmallGroup;
use App\Livewire\SmallGroup\ManageMembers;
use App\Livewire\User\CreateUser;
use App\Livewire\User\IndexUser;
use App\Models\Event;
use App\Models\Lesson;
use App\Models\RolePermission;
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
        $this->elevate = SmallGroup::create(['name' => 'Elevate', 'leader_id' => $this->leader->id, 'status' => SmallGroup::STATUS_ACTIVE]);
        $this->otherGroup = SmallGroup::create(['name' => 'Other Group', 'leader_id' => $this->otherLeader->id, 'status' => SmallGroup::STATUS_ACTIVE]);
        $this->elevateMember = User::factory()->create(['name' => 'Elevate Member']);
        $this->inactiveElevateMember = User::factory()->create(['name' => 'Inactive Elevate Member']);
        $this->otherMember = User::factory()->create(['name' => 'Other Member']);

        SmallGroupMember::create(['small_group_id' => $this->elevate->id, 'user_id' => $this->elevateMember->id, 'status' => SmallGroupMember::STATUS_ACTIVE, 'joined_at' => now()]);
        SmallGroupMember::create(['small_group_id' => $this->elevate->id, 'user_id' => $this->inactiveElevateMember->id, 'status' => SmallGroupMember::STATUS_INACTIVE, 'joined_at' => now()]);
        SmallGroupMember::create(['small_group_id' => $this->otherGroup->id, 'user_id' => $this->otherMember->id, 'status' => SmallGroupMember::STATUS_ACTIVE, 'joined_at' => now()]);
    }

    public function test_leader_member_directory_and_direct_pages_are_scoped_to_led_groups(): void
    {
        $this->grant('users.view', 'users.update', 'small_groups.view', 'group_members.view');

        Livewire::actingAs($this->leader)
            ->test(IndexUser::class)
            ->assertViewHas('users', function ($users): bool {
                $ids = $users->pluck('id');

                return $ids->contains($this->leader->id)
                    && $ids->contains($this->elevateMember->id)
                    && $ids->contains($this->inactiveElevateMember->id)
                    && ! $ids->contains($this->otherMember->id);
            });

        $this->actingAs($this->leader)->get(route('users.show', $this->elevateMember))->assertOk();
        $this->actingAs($this->leader)->get(route('users.show', $this->otherMember))->assertForbidden();
        $this->actingAs($this->leader)->get(route('users.edit', $this->otherMember))->assertForbidden();
        $this->actingAs($this->leader)->get(route('small-groups.show', $this->otherGroup))->assertForbidden();
    }

    public function test_leader_export_and_attendance_are_scoped(): void
    {
        $this->grant('users.view', 'users.export', 'events.view', 'attendance.view', 'attendance.record');

        $csv = $this->actingAs($this->leader)->get(route('users.export'))->streamedContent();
        $this->assertStringContainsString('Elevate Member', $csv);
        $this->assertStringNotContainsString('Other Member', $csv);

        $event = Event::create(['title' => 'Gathering', 'event_date' => now(), 'location' => 'Hall', 'event_type' => 'service']);

        $component = Livewire::actingAs($this->leader)
            ->test(ViewEvent::class, ['event' => $event])
            ->set('searchName', 'Other Member');

        $this->assertTrue($component->instance()->getSearchResults()->isEmpty());

        $component
            ->call('checkInByUserId', $this->otherMember->id)
            ->assertSet('messageType', 'error');

        $this->assertDatabaseMissing('attendances', ['event_id' => $event->id, 'user_id' => $this->otherMember->id]);
    }

    public function test_leader_cannot_browse_unassigned_people_or_create_users_even_with_stale_permissions(): void
    {
        $this->grant('users.view', 'users.create', 'small_groups.view', 'group_members.view', 'group_members.add');

        $this->actingAs($this->leader)->get(route('users.create'))->assertForbidden();

        Livewire::actingAs($this->leader)
            ->test(CreateUser::class)
            ->assertForbidden();

        Livewire::actingAs($this->leader)
            ->test(ManageMembers::class, ['smallGroup' => $this->elevate])
            ->assertViewHas('availableUsers', fn ($users) => $users->isEmpty())
            ->call('addMember', $this->otherMember->id)
            ->assertForbidden();
    }

    public function test_lesson_progress_group_selector_is_limited_to_led_groups(): void
    {
        $this->grant('lessons.view', 'small_groups.view', 'lesson_progress.view', 'lesson_progress.update');
        $lesson = Lesson::create(['title' => 'Shared Lesson', 'order' => 1, 'status' => Lesson::STATUS_PUBLISHED]);

        Livewire::actingAs($this->leader)
            ->test(ViewLesson::class, ['lesson' => $lesson])
            ->assertViewHas('groups', fn ($groups) => $groups->pluck('id')->all() === [$this->elevate->id])
            ->set('groupId', $this->otherGroup->id)
            ->call('updateMemberProgress', $this->otherGroup->members()->firstOrFail()->id, 'completed')
            ->assertNotFound();
    }

    public function test_only_active_small_group_leaders_can_be_assigned_as_group_leader(): void
    {
        $admin = User::factory()->admin()->create();
        $pastor = User::factory()->pastor()->create();

        Livewire::actingAs($admin)
            ->test(CreateSmallGroup::class)
            ->assertViewHas('users', fn ($users) => $users->every(fn ($user) => $user->role === User::ROLE_SMALL_GROUP_LEADER && $user->isActive()))
            ->set('name', 'New Group')
            ->set('leader_id', $pastor->id)
            ->set('status', SmallGroup::STATUS_ACTIVE)
            ->call('save')
            ->assertHasErrors(['leader_id']);
    }

    public function test_assigned_leader_cannot_be_demoted_or_deactivated_until_groups_are_reassigned(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(\App\Livewire\User\UpdateUser::class, ['user' => $this->leader])
            ->assertSet('leaderAssignmentLocked', true)
            ->assertSee('Role and status are locked')
            ->assertSee('Elevate')
            ->set('role', User::ROLE_MEMBER)
            ->call('save')
            ->assertStatus(422);

        $this->assertSame(User::ROLE_SMALL_GROUP_LEADER, $this->leader->fresh()->role);
    }

    public function test_access_control_removes_permissions_that_would_expose_unassigned_people(): void
    {
        $admin = User::factory()->admin()->create();
        RolePermission::create(['role' => User::ROLE_SMALL_GROUP_LEADER, 'permission' => 'users.create']);
        RolePermission::create(['role' => User::ROLE_SMALL_GROUP_LEADER, 'permission' => 'group_members.add']);

        Livewire::actingAs($admin)
            ->test(ManageAccessControl::class)
            ->set('selectedRole', User::ROLE_SMALL_GROUP_LEADER)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('role_permissions', ['role' => User::ROLE_SMALL_GROUP_LEADER, 'permission' => 'users.create']);
        $this->assertDatabaseMissing('role_permissions', ['role' => User::ROLE_SMALL_GROUP_LEADER, 'permission' => 'group_members.add']);
    }

    private function grant(string ...$permissions): void
    {
        foreach ($permissions as $permission) {
            RolePermission::firstOrCreate(['role' => User::ROLE_SMALL_GROUP_LEADER, 'permission' => $permission]);
        }
    }
}
