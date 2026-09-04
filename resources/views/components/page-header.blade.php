@props(['title', 'subtitle' => null, 'badge' => null, 'badgeColor' => 'gray', 'backRoute' => null, 'backLabel' => 'Back'])
<section class="page-heading" aria-labelledby="page-title">
    <div class="page-heading-copy">
        @if($backRoute)
            <a href="{{ $backRoute }}" class="back-link" wire:navigate><x-heroicon-o-chevron-left aria-hidden="true" />{{ $backLabel }}</a>
        @endif
        <div class="page-title-row">
            <h1 id="page-title">{{ $title }}</h1>
            @if($badge)<span @class(['status-badge', 'status-badge-green' => $badgeColor === 'green', 'status-badge-yellow' => $badgeColor === 'yellow', 'status-badge-red' => $badgeColor === 'red', 'status-badge-blue' => $badgeColor === 'blue', 'status-badge-gray' => $badgeColor === 'gray'])>{{ $badge }}</span>@endif
        </div>
        @if($subtitle)<p>{{ $subtitle }}</p>@endif
    </div>
    @if(isset($actions))<div class="page-actions">{{ $actions }}</div>@endif
</section>
