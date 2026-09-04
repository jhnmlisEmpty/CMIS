<div class="app-subview map-component member-map">
    @if($showFilters)
        <section class="member-map-filters" aria-labelledby="map-filter-title">
            <div class="member-map-heading"><div><span>01</span><div><h4 id="map-filter-title">Find members</h4><p>Search or narrow the pins shown on the map.</p></div></div></div>
            <div class="member-map-filter-grid">
                <!-- Search -->
                <div class="event-field member-map-search">
                    <label for="search">Search</label>
                    <input type="text" 
                           id="search" 
                           wire:model.live.debounce.300ms="search"
                           class="member-map-control"
                           placeholder="Search by name, email, or address...">
                </div>

                <!-- Role Filter -->
                <div class="event-field">
                    <label for="roleFilter">Role</label>
                    <select id="roleFilter" 
                            wire:model.live="roleFilter"
                            class="member-map-control">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role }}">{{ ucwords(str_replace('_', ' ', $role)) }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="event-field">
                    <label for="statusFilter">Status</label>
                    <select id="statusFilter" 
                            wire:model.live="statusFilter"
                            class="member-map-control">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if($search || $roleFilter || $statusFilter)
                <div class="member-map-active-filters">
                    <span>Active filters</span>
                    @if($search)
                        <span class="member-map-filter-chip">
                            Search: {{ $search }}
                        </span>
                    @endif
                    @if($roleFilter)
                        <span class="member-map-filter-chip">
                            {{ ucwords(str_replace('_', ' ', $roleFilter)) }}
                        </span>
                    @endif
                    @if($statusFilter)
                        <span class="member-map-filter-chip">
                            {{ ucfirst($statusFilter) }}
                        </span>
                    @endif
                    <button type="button" 
                            wire:click="clearFilters"
                            class="member-map-clear">
                        Clear all
                    </button>
                </div>
            @endif
        </section>
    @endif

    <div class="member-map-summary">
        <x-heroicon-o-map-pin />
        <span>Showing <strong>{{ count($usersForMap) }}</strong> of {{ $totalWithLocation }} members with location data</span>
    </div>

    <section class="member-map-stage" aria-label="Member locations map">
        @if(count($usersForMap) > 0)
            @php $mapId = 'users-map-' . $this->getId(); @endphp
            
            <div 
                x-data="usersMapComponent(@js($usersForMap), @js($mapId))"
                x-init="initMap()"
                wire:ignore
                class="member-map-frame"
            >
                <div id="{{ $mapId }}" class="member-map-canvas" style="height: {{ $height }};"></div>
                
                <!-- Legend -->
                @if($showLegend)
                    <div class="member-map-legend">
                        <h5>Map key</h5>
                        <div>
                            <p><span class="is-admin"></span>Admin</p>
                            <p><span class="is-pastor"></span>Pastor</p>
                            <p><span class="is-ministry"></span>Ministry head</p>
                            <p><span class="is-leader"></span>Small group leader</p>
                            <p><span class="is-member"></span>Member</p>
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="event-empty-state member-map-empty">
                <x-heroicon-o-map-pin />
                <strong>No locations found</strong>
                <p>
                    @if($search || $roleFilter || $statusFilter !== 'active')
                        No members match your current filters. Try adjusting your search criteria.
                    @else
                        No members have location data yet. Add locations to members to see them on the map.
                    @endif
                </p>
            </div>
        @endif
    </section>

    @if($showMemberList && count($usersForMap) > 0)
        <section class="member-map-list" aria-labelledby="members-on-map-title">
            <div class="member-map-heading"><div><span>02</span><div><h4 id="members-on-map-title">Members on map</h4><p>{{ count($usersForMap) }} visible {{ \Illuminate\Support\Str::plural('location', count($usersForMap)) }}</p></div></div></div>
            <div class="member-map-list-grid">
                @foreach($users as $user)
                    <a href="{{ route('users.show', $user) }}" 
                       class="member-map-person"
                       wire:navigate>
                        @if($user->profile_photo_path)<img src="{{ route('profile-photo', ['filename' => basename($user->profile_photo_path)]) }}" alt="{{ $user->name }}" class="event-avatar member-photo">@else<span class="event-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>@endif
                        <div><strong>{{ $user->name }}</strong><small>{{ $user->address ?? 'No address' }}</small><code>{{ number_format($user->latitude, 4) }}, {{ number_format($user->longitude, 4) }}</code></div>
                        <x-heroicon-o-chevron-right />
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>

@script
<script>
    Alpine.data('usersMapComponent', (users, mapId) => ({
        map: null,
        markers: [],
        users: users,
        mapId: mapId,

        initMap() {
            this.$nextTick(() => {
                if (typeof L === 'undefined') {
                    console.error('Leaflet is not loaded');
                    return;
                }

                const mapContainer = document.getElementById(this.mapId);
                if (!mapContainer || mapContainer._leaflet_id) {
                    return;
                }

                // Initialize the map
                this.map = L.map(this.mapId);

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(this.map);

                // Add markers for all users
                this.addMarkers();

                // Fit map to show all markers
                if (this.markers.length > 0) {
                    const group = new L.featureGroup(this.markers);
                    this.map.fitBounds(group.getBounds().pad(0.1));
                } else {
                    // Default to Philippines center if no markers
                    this.map.setView([12.8797, 121.7740], 6);
                }

                // Fix map rendering
                setTimeout(() => {
                    this.map.invalidateSize();
                }, 100);
            });
        },

        getMarkerColor(role) {
            const colors = {
                'admin': '#ef4444',      // red-500
                'pastor': '#a855f7',     // purple-500
                'ministry_head': '#3b82f6', // blue-500
                'small_group_leader': '#22c55e', // green-500
                'member': '#6b7280'      // gray-500
            };
            return colors[role] || colors['member'];
        },

        addMarkers() {
            this.users.forEach(user => {
                const color = this.getMarkerColor(user.role);
                
                const customIcon = L.divIcon({
                    html: `<x-heroicon-s-map-pin class="w-8 h-8 drop-shadow-lg" fill="${color}" />`,
                    className: 'custom-marker',
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                    popupAnchor: [0, -32]
                });

                const marker = L.marker([user.latitude, user.longitude], {
                    icon: customIcon
                }).addTo(this.map);

                // Add name label as tooltip
                marker.bindTooltip(user.name, {
                    permanent: true,
                    direction: 'top',
                    offset: [0, -32],
                    className: 'user-name-label'
                });

                // Create popup content
                const popupContent = `
                    <div class="min-w-[200px]">
                        <div class="font-semibold text-gray-900 mb-1">${user.name}</div>
                        <div class="text-xs text-gray-500 mb-2">${user.role.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</div>
                        ${user.address ? `<div class="text-xs text-gray-600 mb-2">${user.address}</div>` : ''}
                        <div class="flex gap-2 mt-2">
                            <a href="${user.viewUrl}" class="text-xs text-blue-600 hover:underline">View Profile</a>
                            <a href="https://www.google.com/maps?q=${user.latitude},${user.longitude}" target="_blank" class="text-xs text-green-600 hover:underline">Directions</a>
                        </div>
                    </div>
                `;

                marker.bindPopup(popupContent);
                this.markers.push(marker);
            });
        }
    }));
</script>
@endscript
