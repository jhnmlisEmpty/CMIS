<?php

namespace App\Livewire\SmallGroup;

use App\Models\SmallGroup;
use App\Models\SmallGroupLesson;
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
        $this->smallGroup = $smallGroup->load(['leader', 'members.user', 'lessons.progress']);
    }

    public function deleteLesson(int $lessonId): void
    {
        $lesson = SmallGroupLesson::where('id', $lessonId)
            ->where('small_group_id', $this->smallGroup->id)
            ->first();

        if ($lesson) {
            $lesson->delete();
            $this->smallGroup->refresh();
            $this->smallGroup->load(['leader', 'members.user', 'lessons.progress']);
            session()->flash('success', 'Lesson deleted successfully.');
        }
    }

    public function render()
    {
        return view('livewire.small-group.view-small-group');
    }
}
