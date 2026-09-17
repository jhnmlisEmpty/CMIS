<?php

namespace App\Livewire\SmallGroup;

use App\Models\SmallGroup;
use App\Models\Lesson;
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
        return view('livewire.small-group.view-small-group');
    }
}
