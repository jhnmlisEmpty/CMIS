<?php

namespace App\Livewire\Settings;

use App\Models\Tag;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Tag Management | Settings')]
class ManageTags extends Component
{
    use WithPagination;

    public string $type = Tag::TYPE_MEMBER;
    public string $search = '';
    public ?int $editingTagId = null;
    public string $editingName = '';
    public ?int $mergeSourceId = null;
    public ?int $mergeTargetId = null;

    public function updatedType(): void
    {
        abort_unless(in_array($this->type, Tag::TYPES, true), 422);
        $this->reset(['editingTagId', 'editingName', 'mergeSourceId', 'mergeTargetId']);
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function edit(int $tagId): void
    {
        $tag = Tag::forType($this->type)->findOrFail($tagId);
        $this->editingTagId = $tag->id;
        $this->editingName = $tag->name;
    }

    public function rename(): void
    {
        Gate::authorize('access-control.manage');
        $tag = Tag::forType($this->type)->findOrFail($this->editingTagId);
        $name = Str::of($this->editingName)->squish()->toString();
        $normalized = Str::lower($name);
        $this->validate(['editingName' => ['required', 'string', 'max:50']]);
        if (Tag::forType($this->type)->where('normalized_name', $normalized)->whereKeyNot($tag->id)->exists()) {
            $this->addError('editingName', 'A tag with this name already exists in this library.');
            return;
        }
        $tag->update(['name' => $name, 'normalized_name' => $normalized]);
        $this->reset(['editingTagId', 'editingName']);
        session()->flash('success', 'Tag renamed.');
    }

    public function merge(AuditLogger $audit): void
    {
        Gate::authorize('access-control.manage');
        $this->validate(['mergeSourceId' => ['required', 'integer'], 'mergeTargetId' => ['required', 'integer', 'different:mergeSourceId']]);
        $source = Tag::forType($this->type)->findOrFail($this->mergeSourceId);
        $target = Tag::forType($this->type)->findOrFail($this->mergeTargetId);
        $moved = DB::table('taggables')->where('tag_id', $source->id)->count();

        DB::transaction(function () use ($source, $target): void {
            DB::table('taggables')->where('tag_id', $source->id)->orderBy('id')->get()->each(function ($assignment) use ($target): void {
                DB::table('taggables')->insertOrIgnore([
                    'tag_id' => $target->id,
                    'taggable_type' => $assignment->taggable_type,
                    'taggable_id' => $assignment->taggable_id,
                    'created_at' => $assignment->created_at,
                    'updated_at' => now(),
                ]);
            });
            $source->delete();
        });
        $audit->log('merged', 'tags', "Merged {$source->name} into {$target->name}", $target, ['source_tag_id' => $source->id], ['moved_assignments' => $moved]);
        $this->reset(['mergeSourceId', 'mergeTargetId']);
        session()->flash('success', 'Tags merged.');
    }

    public function delete(int $tagId): void
    {
        Gate::authorize('access-control.manage');
        $tag = Tag::forType($this->type)->findOrFail($tagId);
        abort_if(DB::table('taggables')->where('tag_id', $tag->id)->exists(), 422, 'Detach or merge this tag before deleting it.');
        $tag->delete();
        session()->flash('success', 'Unused tag deleted.');
    }

    public function render()
    {
        $tags = Tag::forType($this->type)
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->withCount(['users', 'smallGroups', 'events', 'lessons'])
            ->orderBy('name')->paginate(12);

        return view('livewire.settings.manage-tags', [
            'tags' => $tags,
            'mergeTargets' => Tag::forType($this->type)->orderBy('name')->get(),
            'types' => Tag::TYPES,
        ]);
    }
}
