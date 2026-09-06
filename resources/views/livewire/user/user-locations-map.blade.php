<div class="event-page member-page member-map-page">
    <x-slot:headerTitle>Member Locations</x-slot:headerTitle>

    <x-page-header
        title="Member locations"
        subtitle="Filter mapped members by name, location, birthday, age, role, status, or small group."
        :backRoute="route('users.index')"
        backLabel="Members">
        <x-slot:actions>
            <a href="{{ route('users.create') }}" class="event-button-primary" wire:navigate><x-heroicon-o-plus />New member</a>
        </x-slot:actions>
    </x-page-header>

    <section class="group-workspace-summary member-map-summary" aria-label="Map guide">
        <div><span class="group-mark"><x-heroicon-o-map-pin /></span><div><span class="event-eyebrow">Location directory</span><strong>Explore the church community by area</strong></div></div>
        <p>Only members with saved map coordinates appear here.</p>
    </section>

    <section class="member-map-workspace" aria-labelledby="member-map-title">
        <div class="event-section-heading"><div><span class="event-section-index">01</span><h2 id="member-map-title">Map and member list</h2></div><p>Use the filters to refine results</p></div>
        <div class="member-map-component-wrap">
            <livewire:components.users-map :show-filters="true" :show-legend="true" :show-member-list="true" height="500px" />
        </div>
    </section>
</div>
