<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventAttendanceExpectation;
use App\Models\EventAudienceRule;
use App\Models\SmallGroup;
use App\Models\SmallGroupMember;
use App\Models\User;
use App\Services\AttendanceAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_due_events_snapshot_a_deduplicated_audience_and_apply_custom_points_once(): void
    {
        Carbon::setTestNow('2026-09-18 09:00:00');
        $leader = User::factory()->create(['role' => User::ROLE_SMALL_GROUP_LEADER]);
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $other = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $inactive = User::factory()->inactive()->create(['role' => User::ROLE_MEMBER]);
        $group = SmallGroup::create(['name' => 'Grace Group', 'leader_id' => $leader->id, 'status' => 'active']);
        SmallGroupMember::create(['small_group_id' => $group->id, 'user_id' => $member->id, 'status' => 'active', 'joined_at' => now()]);

        $first = $this->requiredEvent('First gathering', '2026-09-16', 8, 15);
        $first->audienceRules()->createMany([
            ['audience_type' => EventAudienceRule::TYPE_ROLE, 'audience_key' => User::ROLE_MEMBER],
            ['audience_type' => EventAudienceRule::TYPE_SMALL_GROUP, 'audience_key' => (string) $group->id],
        ]);
        Attendance::create(['event_id' => $first->id, 'user_id' => $member->id, 'check_in_time' => now(), 'source' => 'manual']);

        $second = $this->requiredEvent('Second gathering', '2026-09-17', 6, 15);
        $second->audienceRules()->create(['audience_type' => EventAudienceRule::TYPE_USER, 'audience_key' => (string) $member->id]);

        $service = app(AttendanceAnalyticsService::class);
        $service->processDueEvents();
        $service->processDueEvents();

        $this->assertDatabaseCount('event_attendance_expectations', 3);
        $this->assertDatabaseMissing('event_attendance_expectations', ['user_id' => $inactive->id]);
        $this->assertDatabaseHas('event_attendance_expectations', [
            'event_id' => $first->id, 'user_id' => $member->id, 'status' => 'present', 'points_delta' => 8, 'score_after' => 100,
        ]);
        $this->assertDatabaseHas('event_attendance_expectations', [
            'event_id' => $second->id, 'user_id' => $member->id, 'status' => 'absent', 'points_delta' => -15, 'score_after' => 85,
        ]);
        $this->assertDatabaseHas('member_engagement_stats', [
            'user_id' => $member->id, 'current_score' => 85, 'current_streak' => 0, 'longest_streak' => 1,
            'required_events' => 2, 'attended_events' => 1, 'missed_events' => 1,
        ]);
        $this->assertDatabaseHas('event_attendance_expectation_groups', ['small_group_id' => $group->id]);
        $this->assertNotNull($other->fresh()->engagementStat);
    }

    public function test_post_finalization_correction_updates_attendance_and_replays_score_and_streak(): void
    {
        Carbon::setTestNow('2026-09-18 09:00:00');
        $member = User::factory()->create();
        $first = $this->requiredEvent('First', '2026-09-16', 10, 10);
        $second = $this->requiredEvent('Second', '2026-09-17', 10, 20);
        foreach ([$first, $second] as $event) {
            $event->audienceRules()->create(['audience_type' => EventAudienceRule::TYPE_USER, 'audience_key' => (string) $member->id]);
        }
        Attendance::create(['event_id' => $first->id, 'user_id' => $member->id, 'check_in_time' => now(), 'source' => 'manual']);

        $service = app(AttendanceAnalyticsService::class);
        $service->processDueEvents();
        $this->assertSame(80, $member->fresh()->engagementStat->current_score);

        $expectation = EventAttendanceExpectation::where('event_id', $second->id)->where('user_id', $member->id)->firstOrFail();
        $service->correctExpectation($expectation, EventAttendanceExpectation::STATUS_PRESENT);

        $stat = $member->fresh()->engagementStat;
        $this->assertSame(100, $stat->current_score);
        $this->assertSame(2, $stat->current_streak);
        $this->assertSame(2, $stat->longest_streak);
        $this->assertDatabaseHas('attendances', ['event_id' => $second->id, 'user_id' => $member->id, 'source' => 'correction']);
    }

    public function test_check_in_applies_reward_points_before_event_finalization(): void
    {
        Carbon::setTestNow('2026-09-18 09:00:00');
        $member = User::factory()->create();
        $event = $this->requiredEvent('Sunday service', '2026-09-18', 12, 8);
        $event->audienceRules()->create([
            'audience_type' => EventAudienceRule::TYPE_USER,
            'audience_key' => (string) $member->id,
        ]);

        $service = app(AttendanceAnalyticsService::class);
        $service->recordCheckIn($event, $member->id, 'manual');

        $this->assertDatabaseHas('event_attendance_expectations', [
            'event_id' => $event->id,
            'user_id' => $member->id,
            'status' => EventAttendanceExpectation::STATUS_PRESENT,
            'points_delta' => 12,
            'score_after' => 100,
        ]);
        $this->assertSame(100, $member->fresh()->engagementStat->current_score);
    }

    public function test_analytics_workspace_is_visible_to_admins_and_forbidden_to_unpermitted_members(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($admin)->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('Church analytics')
            ->assertSee('Required attendance trend');

        $this->actingAs($member)->get(route('analytics.index'))->assertForbidden();
        $this->actingAs($member)->get(route('profile'))->assertOk()->assertSee('Engagement score');
    }

    private function requiredEvent(string $title, string $date, int $reward, int $penalty): Event
    {
        return Event::create([
            'title' => $title,
            'event_date' => $date,
            'location' => 'Main hall',
            'event_type' => 'service',
            'attendance_required' => true,
            'attendance_reward_points' => $reward,
            'absence_penalty_points' => $penalty,
        ]);
    }
}
