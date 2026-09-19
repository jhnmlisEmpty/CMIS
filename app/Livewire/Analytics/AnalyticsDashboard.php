<?php

namespace App\Livewire\Analytics;

use App\Models\AnalyticsSetting;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventAttendanceExpectation;
use App\Models\Lesson;
use App\Models\SmallGroup;
use App\Models\SmallGroupMemberProgress;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Analytics | True Vine World Harvest Church - Pangasinan')]
class AnalyticsDashboard extends Component
{
    #[Url]
    public string $tab = 'overview';

    #[Url]
    public string $month = '';

    #[Url]
    public string $search = '';

    public int $lowScoreThreshold = 60;
    public int $lowAttendanceThreshold = 60;
    public int $rollingDays = 90;
    public int $minimumRequiredEvents = 3;

    public function mount(): void
    {
        Gate::authorize('analytics.view');
        $this->month = preg_match('/^\d{4}-\d{2}$/', $this->month) ? $this->month : now()->format('Y-m');
        $settings = AnalyticsSetting::current();
        $this->lowScoreThreshold = $settings->low_score_threshold;
        $this->lowAttendanceThreshold = $settings->low_attendance_threshold;
        $this->rollingDays = $settings->rolling_days;
        $this->minimumRequiredEvents = $settings->minimum_required_events;
    }

    public function saveSettings(): void
    {
        Gate::authorize('analytics.manage_settings');
        $values = $this->validate([
            'lowScoreThreshold' => ['required', 'integer', 'min:0', 'max:100'],
            'lowAttendanceThreshold' => ['required', 'integer', 'min:0', 'max:100'],
            'rollingDays' => ['required', 'integer', 'min:7', 'max:730'],
            'minimumRequiredEvents' => ['required', 'integer', 'min:1', 'max:50'],
        ]);
        AnalyticsSetting::current()->update([
            'low_score_threshold' => $values['lowScoreThreshold'],
            'low_attendance_threshold' => $values['lowAttendanceThreshold'],
            'rolling_days' => $values['rollingDays'],
            'minimum_required_events' => $values['minimumRequiredEvents'],
        ]);
        session()->flash('success', 'Analytics thresholds updated.');
    }

    public function render()
    {
        $viewer = auth()->user();
        $monthStart = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $visibleUsers = User::query()->visibleTo($viewer);
        $visibleUserIds = (clone $visibleUsers)->pluck('id');

        $monthAttendances = Attendance::query()
            ->whereIn('user_id', $visibleUserIds)
            ->whereHas('event', fn ($query) => $query->whereBetween('event_date', [$monthStart, $monthEnd]))
            ->get();
        $monthExpectations = EventAttendanceExpectation::query()
            ->whereIn('user_id', $visibleUserIds)
            ->whereNotNull('finalized_at')
            ->whereHas('event', fn ($query) => $query->whereBetween('event_date', [$monthStart, $monthEnd]))
            ->with(['event', 'user'])
            ->get();

        $present = $monthExpectations->where('status', EventAttendanceExpectation::STATUS_PRESENT)->count();
        $absent = $monthExpectations->where('status', EventAttendanceExpectation::STATUS_ABSENT)->count();
        $requiredTotal = $present + $absent;

        $trend = collect(range(11, 0))->map(function (int $monthsAgo) use ($visibleUserIds): array {
            $start = now()->subMonths($monthsAgo)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $items = EventAttendanceExpectation::query()
                ->whereIn('user_id', $visibleUserIds)
                ->whereNotNull('finalized_at')
                ->whereHas('event', fn ($query) => $query->whereBetween('event_date', [$start, $end]))
                ->get(['status']);
            $monthPresent = $items->where('status', 'present')->count();
            $total = $items->whereIn('status', ['present', 'absent'])->count();

            return ['label' => $start->format('M Y'), 'rate' => $total ? round($monthPresent / $total * 100) : 0, 'has_data' => $total > 0];
        });

        $eventRows = Event::query()
            ->whereBetween('event_date', [$monthStart, $monthEnd])
            ->withCount([
                'attendances as checkins_count' => fn ($query) => $query->whereIn('user_id', $visibleUserIds),
                'attendanceExpectations as required_count' => fn ($query) => $query->whereIn('user_id', $visibleUserIds),
                'attendanceExpectations as present_count' => fn ($query) => $query->whereIn('user_id', $visibleUserIds)->where('status', 'present'),
                'attendanceExpectations as absent_count' => fn ($query) => $query->whereIn('user_id', $visibleUserIds)->where('status', 'absent'),
            ])->orderByDesc('event_date')->get();

        $publishedLessons = Lesson::published()->count();
        $groupRows = SmallGroup::query()->visibleTo($viewer)->active()->withCount(['activeMembers'])->orderBy('name')->get()->map(function (SmallGroup $group) use ($publishedLessons): array {
            $memberIds = $group->activeMembers()->pluck('id');
            $attendance = EventAttendanceExpectation::query()->whereHas('groups', fn ($query) => $query->whereKey($group->id))->whereNotNull('finalized_at')->get();
            $groupPresent = $attendance->where('status', 'present')->count();
            $attendanceTotal = $attendance->whereIn('status', ['present', 'absent'])->count();
            $completed = SmallGroupMemberProgress::query()->whereIn('small_group_member_id', $memberIds)->where('status', 'completed')->count();
            $lessonTotal = $memberIds->count() * $publishedLessons;

            return [
                'model' => $group,
                'members' => $memberIds->count(),
                'attendance_rate' => $attendanceTotal ? round($groupPresent / $attendanceTotal * 100) : null,
                'lesson_rate' => $lessonTotal ? round($completed / $lessonTotal * 100) : null,
            ];
        });

        $settings = AnalyticsSetting::current();
        $cutoff = now()->subDays($settings->rolling_days)->startOfDay();
        $watchlist = User::query()->visibleTo($viewer)->where('status', User::STATUS_ACTIVE)
            ->with(['engagementStat', 'smallGroups' => fn ($query) => $query->where('small_group_members.status', 'active')])
            ->when($this->search, fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->orderBy('name')->get()->map(function (User $user) use ($settings, $cutoff): ?array {
                $recent = $user->attendanceExpectations()->whereNotNull('finalized_at')->whereHas('event', fn ($query) => $query->whereDate('event_date', '>=', $cutoff))->get();
                $recentPresent = $recent->where('status', 'present')->count();
                $recentTotal = $recent->whereIn('status', ['present', 'absent'])->count();
                $rate = $recentTotal ? round($recentPresent / $recentTotal * 100) : null;
                $score = $user->engagementStat?->current_score ?? 100;
                $lowScore = $score < $settings->low_score_threshold;
                $lowRate = $recentTotal >= $settings->minimum_required_events && $rate < $settings->low_attendance_threshold;
                if (! $lowScore && ! $lowRate) {
                    return null;
                }

                return compact('user', 'score', 'rate', 'recentTotal', 'lowScore', 'lowRate');
            })->filter()->sortBy([['score', 'asc'], ['rate', 'asc']])->values();

        return view('livewire.analytics.analytics-dashboard', [
            'monthLabel' => $monthStart->format('F Y'),
            'metrics' => [
                'checkins' => $monthAttendances->count(),
                'unique_attendees' => $monthAttendances->pluck('user_id')->unique()->count(),
                'present' => $present,
                'absent' => $absent,
                'rate' => $requiredTotal ? round($present / $requiredTotal * 100) : null,
            ],
            'trend' => $trend,
            'eventRows' => $eventRows,
            'groupRows' => $groupRows,
            'watchlist' => $watchlist,
        ]);
    }
}
