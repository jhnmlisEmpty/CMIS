<?php

namespace App\Livewire\SmallGroup;

use App\Models\SmallGroup;
use App\Models\SmallGroupLesson;
use App\Models\SmallGroupMemberProgress;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Manage Lessons | True Vine World Harvest Church - Pangasinan')]
class ManageLessons extends Component
{
    public SmallGroup $smallGroup;

    // Form fields
    public bool $showForm = false;
    public ?int $editingLessonId = null;
    public string $title = '';
    public string $description = '';
    public int $order = 1;
    public string $content = '';
    public string $status = 'draft';

    // View lesson
    public ?SmallGroupLesson $viewingLesson = null;

    #[Url]
    public ?int $edit = null;

    public function mount(SmallGroup $smallGroup): void
    {
        $this->smallGroup = $smallGroup->load(['lessons', 'members.user']);
        $this->order = $smallGroup->lessons->count() + 1;

        // Handle edit query parameter
        if ($this->edit) {
            $this->editLesson($this->edit);
            $this->edit = null;
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'order' => ['required', 'integer', 'min:1'],
            'content' => ['nullable', 'string'],
            'status' => ['required', 'in:' . implode(',', SmallGroupLesson::STATUSES)],
        ];
    }

    public function showCreateForm(): void
    {
        $this->resetForm();
        $this->order = $this->smallGroup->lessons->count() + 1;
        $this->showForm = true;
    }

    public function editLesson(int $lessonId): void
    {
        $lesson = SmallGroupLesson::find($lessonId);
        
        if ($lesson && $lesson->small_group_id === $this->smallGroup->id) {
            $this->editingLessonId = $lesson->id;
            $this->title = $lesson->title;
            $this->description = $lesson->description ?? '';
            $this->order = $lesson->order;
            $this->content = $lesson->content ?? '';
            $this->status = $lesson->status;
            $this->showForm = true;
        }
    }

    public function viewLesson(int $lessonId): void
    {
        $lesson = SmallGroupLesson::with(['progress.member.user'])->find($lessonId);
        
        if ($lesson && $lesson->small_group_id === $this->smallGroup->id) {
            $this->viewingLesson = $lesson;
        }
    }

    public function closeViewLesson(): void
    {
        $this->viewingLesson = null;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingLessonId) {
            $lesson = SmallGroupLesson::find($this->editingLessonId);
            if ($lesson && $lesson->small_group_id === $this->smallGroup->id) {
                $lesson->update($validated);
                session()->flash('success', 'Lesson updated successfully.');
            }
        } else {
            SmallGroupLesson::create([
                'small_group_id' => $this->smallGroup->id,
                ...$validated,
            ]);
            session()->flash('success', 'Lesson created successfully.');
        }

        $this->resetForm();
        $this->smallGroup->refresh();
    }

    public function deleteLesson(int $lessonId): void
    {
        $lesson = SmallGroupLesson::find($lessonId);
        
        if ($lesson && $lesson->small_group_id === $this->smallGroup->id) {
            $lesson->delete();
            $this->smallGroup->refresh();
            session()->flash('success', 'Lesson deleted successfully.');
        }
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->showForm = false;
        $this->editingLessonId = null;
        $this->title = '';
        $this->description = '';
        $this->order = $this->smallGroup->lessons->count() + 1;
        $this->content = '';
        $this->status = 'draft';
    }

    public function updateMemberProgress(int $memberId, int $lessonId, string $status): void
    {
        $progress = SmallGroupMemberProgress::firstOrNew([
            'small_group_member_id' => $memberId,
            'small_group_lesson_id' => $lessonId,
        ]);

        $progress->status = $status;
        if ($status === SmallGroupMemberProgress::STATUS_COMPLETED) {
            $progress->completed_at = now();
        } else {
            $progress->completed_at = null;
        }
        $progress->save();

        if ($this->viewingLesson && $this->viewingLesson->id === $lessonId) {
            $this->viewingLesson->refresh();
            $this->viewingLesson->load(['progress.member.user']);
        }
    }

    public function render()
    {
        return view('livewire.small-group.manage-lessons', [
            'statuses' => SmallGroupLesson::STATUSES,
            'progressStatuses' => SmallGroupMemberProgress::STATUSES,
        ]);
    }
}
