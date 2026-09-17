<?php

namespace App\Livewire\Lessons;

use App\Models\Lesson;
use App\Models\SmallGroup;
use App\Models\SmallGroupMemberProgress;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Lesson Details | True Vine World Harvest Church - Pangasinan')]
class ViewLesson extends Component
{
    public Lesson $lesson;

    #[Url(as: 'group')]
    public ?int $groupId = null;

    public function mount(Lesson $lesson): void
    {
        abort_unless($lesson->isPublished() || Gate::allows('lessons.update'), 404);
        $this->lesson = $lesson;

        if (! $this->groupId && Gate::allows('lesson_progress.view')) {
            $this->groupId = SmallGroup::query()->visibleTo(auth()->user())->active()->value('id');
        }
    }

    public function updatedGroupId(): void
    {
        $this->groupId = $this->groupId ? (int) $this->groupId : null;
    }

    public function updateMemberProgress(int $memberId, string $status): void
    {
        Gate::authorize('lesson_progress.update');
        abort_unless(in_array($status, SmallGroupMemberProgress::STATUSES, true), 422);
        $group = $this->selectedGroup();
        abort_unless($group && $group->members()->whereKey($memberId)->exists(), 404);

        $progress = SmallGroupMemberProgress::firstOrNew([
            'small_group_member_id' => $memberId,
            'lesson_id' => $this->lesson->id,
        ]);
        $progress->status = $status;
        $progress->completed_at = $status === SmallGroupMemberProgress::STATUS_COMPLETED ? now() : null;
        $progress->save();
        $this->lesson->refresh();
    }

    private function selectedGroup(): ?SmallGroup
    {
        if (! $this->groupId) {
            return null;
        }

        return SmallGroup::query()
            ->visibleTo(auth()->user())
            ->with(['members.user'])
            ->find($this->groupId);
    }

    public function render()
    {
        $group = Gate::allows('lesson_progress.view') ? $this->selectedGroup() : null;
        $progress = $group ? $this->lesson->progress()->whereIn('small_group_member_id', $group->members->pluck('id'))->get() : collect();
        $completedCount = $progress->where('status', 'completed')->count();
        $inProgressCount = $progress->where('status', 'in_progress')->count();
        $totalMembers = $group?->members->count() ?? 0;

        return view('livewire.lessons.view-lesson', [
            'groups' => Gate::allows('lesson_progress.view') ? SmallGroup::query()->visibleTo(auth()->user())->active()->orderBy('name')->get() : collect(),
            'group' => $group,
            'progress' => $progress,
            'totalMembers' => $totalMembers,
            'completedCount' => $completedCount,
            'inProgressCount' => $inProgressCount,
            'notStartedCount' => max(0, $totalMembers - $completedCount - $inProgressCount),
        ]);
    }
}
