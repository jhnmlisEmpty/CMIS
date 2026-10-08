<?php

namespace App\Livewire\Concerns;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

trait ManagesTags
{
    public string $tagSearch = '';

    public array $tag_ids = [];

    public array $pending_tags = [];

    abstract protected function tagType(): string;

    public function addTag(): void
    {
        $this->resetErrorBag('tagSearch');
        $name = Str::of($this->tagSearch)->squish()->toString();

        if ($name === '') {
            $this->addError('tagSearch', 'Enter a tag name.');

            return;
        }

        if (mb_strlen($name) > 50) {
            $this->addError('tagSearch', 'Tag names may not exceed 50 characters.');

            return;
        }

        $normalizedName = $this->normalizeTagName($name);
        $existingTag = Tag::forType($this->tagType())->where('normalized_name', $normalizedName)->first();

        if ($existingTag) {
            if (! in_array($existingTag->id, array_map('intval', $this->tag_ids), true)) {
                $this->tag_ids[] = $existingTag->id;
            }
        } elseif (! collect($this->pending_tags)->contains(
            fn (string $pendingTag): bool => $this->normalizeTagName($pendingTag) === $normalizedName
        )) {
            $this->pending_tags[] = $name;
        }

        $this->tagSearch = '';
    }

    public function removePendingTag(int $index): void
    {
        if (! array_key_exists($index, $this->pending_tags)) {
            return;
        }

        unset($this->pending_tags[$index]);
        $this->pending_tags = array_values($this->pending_tags);
    }

    public function removeSelectedTag(int $tagId): void
    {
        $this->tag_ids = array_values(array_filter(
            $this->tag_ids,
            fn ($selectedTagId): bool => (int) $selectedTagId !== $tagId,
        ));
    }

    protected function tagRules(): array
    {
        return [
            'tag_ids' => ['array'],
            'tag_ids.*' => ['integer', 'distinct', Rule::exists('tags', 'id')->where('type', $this->tagType())],
            'pending_tags' => ['array'],
            'pending_tags.*' => ['required', 'string', 'max:50'],
        ];
    }

    protected function availableTags(): Collection
    {
        $search = trim($this->tagSearch);

        return Tag::forType($this->tagType())
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereIn('id', $this->tag_ids);
            }))
            ->orderBy('name')
            ->get();
    }

    protected function selectedTags(): Collection
    {
        return Tag::forType($this->tagType())
            ->whereIn('id', $this->tag_ids)
            ->orderBy('name')
            ->get();
    }

    protected function resolveTagIds(array $tagIds, array $pendingTags): array
    {
        foreach ($pendingTags as $pendingTag) {
            $name = Str::of($pendingTag)->squish()->toString();
            $tag = Tag::firstOrCreate(
                ['type' => $this->tagType(), 'normalized_name' => $this->normalizeTagName($name)],
                ['name' => $name],
            );
            $tagIds[] = $tag->id;
        }

        return collect($tagIds)->map(fn ($tagId): int => (int) $tagId)->unique()->values()->all();
    }

    protected function syncTags(Model $taggable, array $tagIds, array $pendingTags): void
    {
        $taggable->tags()->sync($this->resolveTagIds($tagIds, $pendingTags));
    }

    protected function tagViewData(): array
    {
        return [
            'tags' => $this->availableTags(),
            'selectedTags' => $this->selectedTags(),
        ];
    }

    private function normalizeTagName(string $name): string
    {
        return Str::lower(Str::of($name)->squish()->toString());
    }
}
