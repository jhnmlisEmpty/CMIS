<?php

namespace App\Livewire\Lessons;

use App\Livewire\Concerns\ManagesTags;
use App\Models\Lesson;
use App\Models\SmallGroup;
use App\Models\Tag;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Lessons | True Vine World Harvest Church - Pangasinan')]
class ManageLessons extends Component
{
    use ManagesTags;

    public bool $showForm = false;

    public ?int $editingLessonId = null;

    public string $title = '';

    public string $description = '';

    public int $order = 1;

    public string $content = '';

    public string $status = Lesson::STATUS_DRAFT;

    #[Url]
    public string $search = '';

    #[Url]
    public string $tagFilter = '';

    #[Url]
    public ?int $edit = null;

    protected function tagType(): string
    {
        return Tag::TYPE_LESSON;
    }

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
            ...$this->tagRules(),
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
        $this->tagSearch = '';
        $this->pending_tags = [];
        $this->resetErrorBag();
        $lesson = Lesson::findOrFail($lessonId);
        $this->editingLessonId = $lesson->id;
        $this->title = $lesson->title;
        $this->description = $lesson->description ?? '';
        $this->order = $lesson->order;
        $this->content = $lesson->content ?? '';
        $this->status = $lesson->status;
        $this->tag_ids = $lesson->tags()->pluck('tags.id')->all();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $tagIds = $validated['tag_ids'];
        $pendingTags = $validated['pending_tags'];
        $lessonData = Arr::except($validated, ['tag_ids', 'pending_tags']);

        DB::transaction(function () use ($lessonData, $pendingTags, $tagIds): void {
            if ($this->editingLessonId) {
                Gate::authorize('lessons.update');
                $lesson = Lesson::findOrFail($this->editingLessonId);
                if ($lesson->status !== $lessonData['status']) {
                    Gate::authorize('lessons.publish');
                }
                $lesson->update($lessonData);
                session()->flash('success', 'Lesson updated successfully. It is now synchronized for every cell group.');
            } else {
                Gate::authorize('lessons.create');
                if ($lessonData['status'] === Lesson::STATUS_PUBLISHED) {
                    Gate::authorize('lessons.publish');
                }
                $lesson = Lesson::create($lessonData);
                session()->flash('success', 'Lesson created successfully for every cell group.');
            }

            $this->syncTags($lesson, $tagIds, $pendingTags);
        });

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

    public function clearFilters(): void
    {
        $this->reset(['search', 'tagFilter']);
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
        $this->tagSearch = '';
        $this->tag_ids = [];
        $this->pending_tags = [];
        $this->resetErrorBag();
    }

    public function render()
    {
        $query = Lesson::query()
            ->with('tags')
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%")
                    ->orWhereHas('tags', fn ($tagQuery) => $tagQuery->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->tagFilter !== '', fn ($query) => $query->whereHas('tags', fn ($tagQuery) => $tagQuery->whereKey($this->tagFilter)))
            ->ordered();
        if (! Gate::allows('lessons.update')) {
            $query->published();
        }

        return view('livewire.lessons.manage-lessons', [
            'lessons' => $query->withCount(['progress as completed_count' => fn ($query) => $query->where('status', 'completed')])->get(),
            'statuses' => Lesson::STATUSES,
            'totalLessons' => Lesson::count(),
            'publishedLessons' => Lesson::published()->count(),
            'cellGroupCount' => SmallGroup::active()->count(),
            'filterTags' => Tag::forType(Tag::TYPE_LESSON)->orderBy('name')->get(),
            ...$this->tagViewData(),
        ]);
    }
}
