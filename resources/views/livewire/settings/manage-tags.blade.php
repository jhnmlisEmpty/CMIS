<x-settings-shell active="tags" title="Tag management" description="Keep reusable labels clear without losing their record assignments.">
    <header class="settings-section-heading"><div><span class="event-eyebrow">Reusable labels</span><h2>{{ Str::headline($type) }} tags</h2></div><p>Deletion is available only when usage reaches zero.</p></header>
    <div class="settings-toolbar">
        <div class="settings-segmented">@foreach($types as $tagType)<button type="button" wire:click="$set('type', '{{ $tagType }}')" @class(['is-active' => $type === $tagType])>{{ Str::headline($tagType) }}</button>@endforeach</div>
        <label class="event-search"><x-heroicon-o-magnifying-glass /><span class="sr-only">Search tags</span><input type="search" wire:model.live.debounce.300ms="search" placeholder="Search tags"></label>
    </div>
    <div class="settings-tag-list">
        @forelse($tags as $tag)
            @php $usage = $tag->users_count + $tag->small_groups_count + $tag->events_count + $tag->lessons_count; @endphp
            <article wire:key="tag-{{ $tag->id }}">
                <div><span class="group-tag-badge">{{ $tag->name }}</span><small>{{ $usage }} {{ Str::plural('assignment', $usage) }}</small></div>
                <div class="settings-row-actions"><button type="button" wire:click="edit({{ $tag->id }})">Rename</button><button type="button" wire:click="$set('mergeSourceId', {{ $tag->id }})">Merge</button><button type="button" class="is-danger" wire:click="delete({{ $tag->id }})" @disabled($usage > 0) wire:confirm="Delete this unused tag permanently?">Delete</button></div>
            </article>
        @empty
            <div class="event-empty-state event-empty-compact"><h3>No tags found</h3><p>Tags are created from member, group, event, and lesson forms.</p></div>
        @endforelse
    </div>
    @if($tags->hasPages())<div class="event-pagination">{{ $tags->links() }}</div>@endif

    @if($editingTagId)
        <form wire:submit="rename" class="settings-inline-panel"><div><span class="event-eyebrow">Rename tag</span><label for="rename-tag" class="sr-only">New tag name</label><input id="rename-tag" wire:model="editingName" autofocus>@error('editingName')<p class="event-field-error">{{ $message }}</p>@enderror</div><div><button type="button" wire:click="$set('editingTagId', null)" class="event-button-secondary">Cancel</button><button class="event-button-primary">Save name</button></div></form>
    @endif
    @if($mergeSourceId)
        <form wire:submit="merge" class="settings-inline-panel"><div><span class="event-eyebrow">Merge tag</span><label for="merge-target">Move every assignment to</label><select id="merge-target" wire:model="mergeTargetId"><option value="">Choose destination</option>@foreach($mergeTargets->where('id', '!=', $mergeSourceId) as $target)<option value="{{ $target->id }}">{{ $target->name }}</option>@endforeach</select>@error('mergeTargetId')<p class="event-field-error">{{ $message }}</p>@enderror</div><div><button type="button" wire:click="$set('mergeSourceId', null)" class="event-button-secondary">Cancel</button><button class="event-button-primary" wire:confirm="Merge this tag and remove the source tag?">Merge tags</button></div></form>
    @endif
</x-settings-shell>
