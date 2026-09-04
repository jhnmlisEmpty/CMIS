<?php

namespace App\Livewire\SmallGroup;

use App\Models\SmallGroupLesson;
use App\Models\SmallGroupMemberProgress;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('View Lesson | True Vine World Harvest Church - Pangasinan')]
class ViewLesson extends Component
{
    public SmallGroupLesson $lesson;

    public function mount(SmallGroupLesson $lesson): void
    {
        $this->lesson = $lesson->load(['smallGroup.members.user', 'progress']);
    }

    public function updateMemberProgress(int $memberId, string $status): void
    {
        // Validate status
        if (!in_array($status, SmallGroupMemberProgress::STATUSES)) {
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
        $progress = $this->lesson->progress->where('small_group_member_id', $memberId)->first();
        return $progress?->status ?? SmallGroupMemberProgress::STATUS_NOT_STARTED;
    }

    public function render()
    {
        $progressStatuses = SmallGroupMemberProgress::STATUSES;
        
        // Calculate progress stats
        $totalMembers = $this->lesson->smallGroup->members->count();
        $completedCount = $this->lesson->progress->where('status', SmallGroupMemberProgress::STATUS_COMPLETED)->count();
        $inProgressCount = $this->lesson->progress->where('status', SmallGroupMemberProgress::STATUS_IN_PROGRESS)->count();
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
