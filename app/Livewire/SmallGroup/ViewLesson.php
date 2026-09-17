<?php

namespace App\Livewire\SmallGroup;

use App\Models\SmallGroup;
use App\Models\SmallGroupLesson;
use App\Models\SmallGroupMemberProgress;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('View Lesson | True Vine World Harvest Church - Pangasinan')]
class ViewLesson extends Component
{
    public SmallGroupLesson $lesson;

    public function mount(SmallGroup $smallGroup, SmallGroupLesson $lesson): void
    {
        abort_unless($lesson->small_group_id === $smallGroup->id, 404);
        $relations = Gate::allows('lesson_progress.view')
            ? ['smallGroup.members.user', 'progress']
            : ['smallGroup'];
        $this->lesson = $lesson->load($relations);
    }

    public function updateMemberProgress(int $memberId, string $status): void
    {
        Gate::authorize('lesson_progress.update');
        abort_unless($this->lesson->smallGroup->members()->whereKey($memberId)->exists(), 404);
        // Validate status
        if (! in_array($status, SmallGroupMemberProgress::STATUSES)) {
            return;
        }

        // Find or create progress record
        $progress = SmallGroupMemberProgress::firstOrNew([
            'small_group_member_id' => $memberId,
            'small_group_lesson_id' => $this->lesson->id,
        ]);

        $progress->status = $status;

        // Set completed_at if status is completed
        if ($status === SmallGroupMemberProgress::STATUS_COMPLETED) {
            $progress->completed_at = now();
        } else {
            $progress->completed_at = null;
        }

        $progress->save();

        // Refresh lesson to get updated progress
        $this->lesson->refresh();
        $this->lesson->load(['smallGroup.members.user', 'progress']);
    }

    public function getMemberProgress(int $memberId): string
    {
        Gate::authorize('lesson_progress.view');
        abort_unless($this->lesson->smallGroup->members()->whereKey($memberId)->exists(), 404);
        $progress = $this->lesson->progress->where('small_group_member_id', $memberId)->first();

        return $progress?->status ?? SmallGroupMemberProgress::STATUS_NOT_STARTED;
    }

    public function render()
    {
        $progressStatuses = SmallGroupMemberProgress::STATUSES;

        // Calculate progress stats
        $canViewProgress = Gate::allows('lesson_progress.view');
        $totalMembers = $canViewProgress ? $this->lesson->smallGroup->members->count() : 0;
        $completedCount = $canViewProgress ? $this->lesson->progress->where('status', SmallGroupMemberProgress::STATUS_COMPLETED)->count() : 0;
        $inProgressCount = $canViewProgress ? $this->lesson->progress->where('status', SmallGroupMemberProgress::STATUS_IN_PROGRESS)->count() : 0;
        $notStartedCount = $totalMembers - $completedCount - $inProgressCount;

        return view('livewire.small-group.view-lesson', [
            'progressStatuses' => $progressStatuses,
            'totalMembers' => $totalMembers,
            'completedCount' => $completedCount,
            'inProgressCount' => $inProgressCount,
            'notStartedCount' => $notStartedCount,
        ]);
    }
}
