<?php

namespace App\Livewire\SmallGroup;

use App\Models\SmallGroup;
use App\Models\Lesson;
use App\Models\EventAttendanceExpectation;
use App\Models\SmallGroupMemberProgress;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('View Small Group | True Vine World Harvest Church - Pangasinan')]
class ViewSmallGroup extends Component
{
    public SmallGroup $smallGroup;

    public function mount(SmallGroup $smallGroup): void
    {
        abort_unless(auth()->user()->canAccessSmallGroup($smallGroup), 403);
        $this->smallGroup = $smallGroup->load(['leader', 'members.user']);
        $this->loadSharedLessons();
    }

    public function deleteLesson(int $lessonId): void
    {
        Gate::authorize('lessons.delete');
        abort_unless(auth()->user()->canAccessSmallGroup($this->smallGroup), 403);
        Lesson::findOrFail($lessonId)->delete();
        $this->loadSharedLessons();
        session()->flash('success', 'Lesson deleted successfully.');
    }

    private function loadSharedLessons(): void
    {
        $this->smallGroup->setRelation('lessons', Lesson::ordered()->with('progress')->get());
    }

    public function render()
    {
        $activeMemberIds = $this->smallGroup->members->where('status', 'active')->pluck('id');
        $attendance = EventAttendanceExpectation::query()->whereHas('groups', fn ($query) => $query->whereKey($this->smallGroup->id))->whereNotNull('finalized_at')->get();
        $present = $attendance->where('status', 'present')->count();
        $attendanceTotal = $attendance->whereIn('status', ['present', 'absent'])->count();
        $publishedLessons = Lesson::published()->count();
        $completed = SmallGroupMemberProgress::query()->whereIn('small_group_member_id', $activeMemberIds)->where('status', 'completed')->count();
        $lessonTotal = $activeMemberIds->count() * $publishedLessons;

        return view('livewire.small-group.view-small-group', [
            'groupAnalytics' => [
                'attendance_rate' => $attendanceTotal ? round($present / $attendanceTotal * 100) : null,
                'present' => $present,
                'absent' => $attendance->where('status', 'absent')->count(),
                'lesson_rate' => $lessonTotal ? round($completed / $lessonTotal * 100) : null,
                'completed_lessons' => $completed,
                'lesson_total' => $lessonTotal,
            ],
        ]);
    }
}
