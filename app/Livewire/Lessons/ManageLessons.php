<?php

namespace App\Livewire\Lessons;

use App\Models\Lesson;
use App\Models\SmallGroup;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Lessons | True Vine World Harvest Church - Pangasinan')]
class ManageLessons extends Component
{
    public bool $showForm = false;
    public ?int $editingLessonId = null;
    public string $title = '';
    public string $description = '';
    public int $order = 1;
    public string $content = '';
    public string $status = Lesson::STATUS_DRAFT;

    #[Url]
    public ?int $edit = null;

    public function mount(): void
    {
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
            'status' => ['required', 'in:'.implode(',', Lesson::STATUSES)],
        ];
    }

    public function showCreateForm(): void
    {
        Gate::authorize('lessons.create');
        $this->resetForm();
        $this->order = (Lesson::max('order') ?? 0) + 1;
        $this->showForm = true;
    }

    public function editLesson(int $lessonId): void
    {
        Gate::authorize('lessons.update');
        $lesson = Lesson::findOrFail($lessonId);
        $this->editingLessonId = $lesson->id;
        $this->title = $lesson->title;
        $this->description = $lesson->description ?? '';
        $this->order = $lesson->order;
        $this->content = $lesson->content ?? '';
        $this->status = $lesson->status;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingLessonId) {
            Gate::authorize('lessons.update');
            $lesson = Lesson::findOrFail($this->editingLessonId);
            if ($lesson->status !== $validated['status']) {
                Gate::authorize('lessons.publish');
            }
            $lesson->update($validated);
            session()->flash('success', 'Lesson updated successfully. It is now synchronized for every cell group.');
        } else {
            Gate::authorize('lessons.create');
            if ($validated['status'] === Lesson::STATUS_PUBLISHED) {
                Gate::authorize('lessons.publish');
            }
            Lesson::create($validated);
            session()->flash('success', 'Lesson created successfully for every cell group.');
        }

        $this->resetForm();
    }

    public function deleteLesson(int $lessonId): void
    {
        Gate::authorize('lessons.delete');
        Lesson::findOrFail($lessonId)->delete();
        session()->flash('success', 'Lesson deleted successfully.');
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
        $this->order = (Lesson::max('order') ?? 0) + 1;
        $this->content = '';
        $this->status = Lesson::STATUS_DRAFT;
    }

    public function render()
    {
        $query = Lesson::query()->ordered();
        if (! Gate::allows('lessons.update')) {
            $query->published();
        }

        return view('livewire.lessons.manage-lessons', [
            'lessons' => $query->withCount(['progress as completed_count' => fn ($query) => $query->where('status', 'completed')])->get(),
            'statuses' => Lesson::STATUSES,
            'totalLessons' => Lesson::count(),
            'publishedLessons' => Lesson::published()->count(),
            'cellGroupCount' => SmallGroup::active()->count(),
        ]);
    }
}
