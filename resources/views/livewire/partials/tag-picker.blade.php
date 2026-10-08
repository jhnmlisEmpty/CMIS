@php
    $tagLabel ??= 'Tags';
    $tagHelp ??= 'Select reusable tags or enter a new tag name, then choose Add tag.';
    $tagInputId ??= 'tag-search';
@endphp

<fieldset class="event-field event-field-wide group-tag-picker @error('tag_ids') is-invalid @enderror @error('tag_ids.*') is-invalid @enderror @error('pending_tags.*') is-invalid @enderror">
    <legend>{{ $tagLabel }}</legend>
    <p class="event-field-hint">{{ $tagHelp }}</p>

    @if($selectedTags->isNotEmpty() || count($pending_tags) > 0)
        <div class="group-tag-selection" aria-label="Selected tags">
            @foreach($selectedTags as $selectedTag)
                <span class="group-tag-chip">{{ $selectedTag->name }}<button type="button" wire:click="removeSelectedTag({{ $selectedTag->id }})" aria-label="Remove {{ $selectedTag->name }} tag"><x-heroicon-o-x-mark aria-hidden="true" /></button></span>
            @endforeach
            @foreach($pending_tags as $index => $pendingTag)
                <span class="group-tag-chip is-new">{{ $pendingTag }}<small>New</small><button type="button" wire:click="removePendingTag({{ $index }})" aria-label="Remove new {{ $pendingTag }} tag"><x-heroicon-o-x-mark aria-hidden="true" /></button></span>
            @endforeach
        </div>
    @endif

    <div class="group-tag-toolbar">
        <div class="group-leader-search">
            <x-heroicon-o-magnifying-glass aria-hidden="true" />
            <label class="sr-only" for="{{ $tagInputId }}">Search or create tags</label>
            <input id="{{ $tagInputId }}" type="search" wire:model.live.debounce.250ms="tagSearch" wire:keydown.enter.prevent="addTag" placeholder="Search or enter a new tag" autocomplete="off">
            @if($tagSearch !== '')<button type="button" wire:click="addTag">Add tag</button>@endif
        </div>
        <div class="group-leader-picker-meta"><span>{{ count($tag_ids) + count($pending_tags) }} {{ Str::plural('tag', count($tag_ids) + count($pending_tags)) }} selected</span>@if($tagSearch !== '')<span>{{ $tags->count() }} existing {{ Str::plural('match', $tags->count()) }}</span>@endif</div>
    </div>

    <div class="group-tag-options">
        @forelse($tags as $tag)
            <label><input type="checkbox" value="{{ $tag->id }}" wire:model.live="tag_ids"><span>{{ $tag->name }}</span></label>
        @empty
            <p class="event-field-hint">{{ $tagSearch !== '' ? 'No existing tags match. Choose Add tag to create it when this record is saved.' : 'No reusable tags yet. Enter the first tag above.' }}</p>
        @endforelse
    </div>

    @error('tagSearch')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
    @error('tag_ids')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
    @error('tag_ids.*')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
    @error('pending_tags.*')<p class="event-field-error" role="alert">{{ $message }}</p>@enderror
</fieldset>
