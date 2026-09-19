<?php

namespace App\Livewire\User;

use App\Models\AnalyticsSetting;
use App\Models\Event;
use App\Models\EventAttendanceExpectation;
use App\Models\EventAudienceRule;
use App\Models\Lesson;
use App\Models\SmallGroupMemberProgress;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('View Member')]
class ViewUser extends Component
{
    public User $user;

    public function mount(?User $user = null): void
    {
        $user ??= auth()->user();

        if (auth()->id() !== $user->id) {
            Gate::authorize('users.view');
            abort_unless(auth()->user()->canAccessMember($user), 403);
        }

        $this->user = $user;
    }

    public function render()
    {
        $this->user->loadMissing(['engagementStat', 'smallGroupMemberships.smallGroup']);
        $settings = AnalyticsSetting::current();
        $cutoff = now()->subDays($settings->rolling_days)->startOfDay();
        $history = $this->user->attendanceExpectations()->whereNotNull('finalized_at')->with('event')->latest('finalized_at')->get();
        $recent = $history->filter(fn ($item) => $item->event->event_date->gte($cutoff));
        $recentPresent = $recent->where('status', EventAttendanceExpectation::STATUS_PRESENT)->count();
        $recentTotal = $recent->whereIn('status', [EventAttendanceExpectation::STATUS_PRESENT, EventAttendanceExpectation::STATUS_ABSENT])->count();
        $memberTrend = collect(range(5, 0))->map(function (int $monthsAgo) use ($history): array {
            $start = now()->subMonths($monthsAgo)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $items = $history->filter(fn ($item) => $item->event->event_date->betweenIncluded($start, $end));
            $present = $items->where('status', EventAttendanceExpectation::STATUS_PRESENT)->count();
            $total = $items->whereIn('status', [EventAttendanceExpectation::STATUS_PRESENT, EventAttendanceExpectation::STATUS_ABSENT])->count();

            return ['label' => $start->format('M'), 'rate' => $total ? round($present / $total * 100) : 0, 'has_data' => $total > 0];
        });

        $activeMembershipIds = $this->user->smallGroupMemberships->where('status', 'active')->pluck('id');
        $publishedLessonCount = Lesson::published()->count();
        $completedLessons = SmallGroupMemberProgress::query()->whereIn('small_group_member_id', $activeMembershipIds)->where('status', 'completed')->count();
        $lessonOpportunities = $activeMembershipIds->count() * $publishedLessonCount;

        $groupIds = $this->user->smallGroupMemberships->where('status', 'active')->pluck('small_group_id')->map(fn ($id) => (string) $id);
        $upcomingEvents = Event::query()->where('attendance_required', true)->whereDate('event_date', '>=', today())
            ->where(function ($query) use ($groupIds): void {
                $query->whereHas('attendanceExpectations', fn ($expectation) => $expectation->where('user_id', $this->user->id))
                    ->orWhereHas('audienceRules', function ($rules) use ($groupIds): void {
                        $rules->where('audience_type', EventAudienceRule::TYPE_ALL_ACTIVE)
                            ->orWhere(fn ($q) => $q->where('audience_type', EventAudienceRule::TYPE_ROLE)->where('audience_key', $this->user->role))
                            ->orWhere(fn ($q) => $q->where('audience_type', EventAudienceRule::TYPE_USER)->where('audience_key', (string) $this->user->id));
                        if ($groupIds->isNotEmpty()) {
                            $rules->orWhere(fn ($q) => $q->where('audience_type', EventAudienceRule::TYPE_SMALL_GROUP)->whereIn('audience_key', $groupIds));
                        }
                    });
            })->orderBy('event_date')->limit(5)->get();

        return view('livewire.user.view-user', [
            'growth' => [
                'score' => $this->user->engagementStat?->current_score ?? 100,
                'current_streak' => $this->user->engagementStat?->current_streak ?? 0,
                'longest_streak' => $this->user->engagementStat?->longest_streak ?? 0,
                'required_events' => $this->user->engagementStat?->required_events ?? 0,
                'attendance_rate' => $recentTotal ? round($recentPresent / $recentTotal * 100) : null,
                'rolling_days' => $settings->rolling_days,
                'lesson_rate' => $lessonOpportunities ? round($completedLessons / $lessonOpportunities * 100) : null,
                'completed_lessons' => $completedLessons,
                'lesson_opportunities' => $lessonOpportunities,
            ],
            'attendanceHistory' => $history->take(10),
            'upcomingRequiredEvents' => $upcomingEvents,
            'memberTrend' => $memberTrend,
        ]);
    }
}
