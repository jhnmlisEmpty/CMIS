<div class="event-page member-page member-detail-page">
    <x-slot:headerTitle>Member Details</x-slot:headerTitle>

    <x-page-header
        :title="$user->name"
        subtitle="View the member profile, account details, and recorded location."
        :backRoute="route('users.index')"
        backLabel="Members">
        <x-slot:actions>
            <a href="{{ route('users.edit', $user) }}" class="event-button-secondary" wire:navigate><x-heroicon-o-pencil-square />Edit member</a>
        </x-slot:actions>
    </x-page-header>

    <section class="event-detail-hero member-detail-hero" aria-label="Member overview">
        @if($user->profile_photo_path)<img src="{{ route('profile-photo', ['filename' => basename($user->profile_photo_path)]) }}" alt="{{ $user->name }}" class="member-hero-avatar member-photo">@else<span class="member-hero-avatar" aria-hidden="true">{{ collect(explode(' ', $user->name))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('') }}</span>@endif
        <div class="event-detail-copy">
            <div class="member-hero-badges"><span @class(['group-status', 'is-active' => $user->status === 'active'])><i></i>{{ ucfirst($user->status) }}</span><span class="member-role-badge">{{ ucwords(str_replace('_', ' ', $user->role)) }}</span></div>
            <p>{{ $user->email }}</p>
            <div><x-heroicon-o-phone /><span>{{ $user->phone ?: 'No phone number provided' }}</span></div>
        </div>
        <div class="member-qr-card">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($user->uuid) }}" alt="QR code for {{ $user->name }}">
            <div><span>Member QR</span><code>{{ Str::limit($user->uuid, 13) }}</code></div>
        </div>
    </section>

    <div class="member-detail-grid">
        <section class="event-checkin-panel" aria-labelledby="personal-info-title">
            <div class="event-section-heading"><div><span class="event-section-index">01</span><h2 id="personal-info-title">Personal information</h2></div></div>
            <dl class="member-details-list">
                <div><dt>Full name</dt><dd>{{ $user->name }}</dd></div>
                <div><dt>Gender</dt><dd>{{ $user->gender ? ucfirst($user->gender) : 'Not provided' }}</dd></div>
                <div><dt>Birthdate</dt><dd>@if($user->birthdate){{ $user->birthdate->format('F j, Y') }} <small>{{ $user->age }} years old</small>@else Not provided @endif</dd></div>
                <div><dt>Email</dt><dd>{{ $user->email }}</dd></div>
                <div><dt>Phone</dt><dd>{{ $user->phone ?: 'Not provided' }}</dd></div>
                <div><dt>Member since</dt><dd>{{ $user->created_at?->format('F j, Y') ?? 'Not available' }}</dd></div>
            </dl>
        </section>

        <aside class="member-account-panel" aria-labelledby="account-info-title">
            <div class="event-section-heading"><div><span class="event-section-index">02</span><h2 id="account-info-title">Account</h2></div></div>
            <dl>
                <div><dt>Role</dt><dd>{{ ucwords(str_replace('_', ' ', $user->role)) }}</dd></div>
                <div><dt>Status</dt><dd><span @class(['group-status', 'is-active' => $user->status === 'active'])><i></i>{{ ucfirst($user->status) }}</span></dd></div>
                <div><dt>Record ID</dt><dd>#{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}</dd></div>
            </dl>
        </aside>
    </div>

    <section class="event-attendance-panel member-location-detail" aria-labelledby="member-location-detail-title">
        <div class="event-section-heading"><div><span class="event-section-index">03</span><h2 id="member-location-detail-title">Location</h2></div>@if($user->latitude && $user->longitude)<p>Map coordinates recorded</p>@endif</div>
        @if($user->latitude && $user->longitude)
            <div class="member-location-layout">
                <div class="member-address-card">
                    <span><x-heroicon-o-map-pin /></span>
                    <div><small>Recorded address</small><strong>{{ $user->address ?: 'Address not provided' }}</strong><dl><div><dt>Latitude</dt><dd>{{ number_format($user->latitude, 6) }}</dd></div><div><dt>Longitude</dt><dd>{{ number_format($user->longitude, 6) }}</dd></div></dl></div>
                </div>
                <div x-data="viewUserMap({ latitude: {{ $user->latitude }}, longitude: {{ $user->longitude }}, userName: @js($user->name) })" x-init="initMap()" wire:ignore>
                    <div id="view-user-map" class="member-detail-map"></div>
                </div>
            </div>
        @else
            <div class="event-empty-state event-empty-compact"><span class="event-empty-icon"><x-heroicon-o-map-pin /></span><h3>No location recorded</h3><p>Edit this member to add an address and map position.</p><a href="{{ route('users.edit', $user) }}" class="event-button-secondary" wire:navigate>Add location</a></div>
        @endif
    </section>
</div>

@script
<script>
    Alpine.data('viewUserMap', (config) => ({
        map: null, marker: null, latitude: config.latitude, longitude: config.longitude, userName: config.userName,
        initMap() {
            this.$nextTick(() => {
                if (typeof L === 'undefined') return;
                const mapContainer = document.getElementById('view-user-map');
                if (!mapContainer || mapContainer._leaflet_id) return;
                this.map = L.map('view-user-map').setView([this.latitude, this.longitude], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors', maxZoom: 19 }).addTo(this.map);
                const customIcon = L.divIcon({ html: `<x-heroicon-s-map-pin width="32" height="32" aria-hidden="true" />`, className: 'custom-marker', iconSize: [32, 32], iconAnchor: [16, 32], popupAnchor: [0, -32] });
                this.marker = L.marker([this.latitude, this.longitude], { icon: customIcon }).addTo(this.map);
                this.marker.bindPopup(`<div class="text-center"><strong>${this.userName}</strong></div>`).openPopup();
                setTimeout(() => this.map.invalidateSize(), 100);
            });
        }
    }));
</script>
@endscript
