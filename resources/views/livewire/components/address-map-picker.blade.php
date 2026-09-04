<div class="app-subview map-picker location-picker">
    <div class="location-picker-heading">
        <div><span>01</span><div><h4>Address details</h4><p>Choose the area first, then refine the exact location.</p></div></div>
        <span class="location-picker-state">{{ $latitude && $longitude ? 'Location set' : 'Pin pending' }}</span>
    </div>

    <div class="location-fields">
        <!-- Region -->
        <div class="event-field">
            <label for="regionCode">Region</label>
            <select id="regionCode" 
                    wire:model.live="regionCode"
                    class="location-control">
                <option value="">Select Region</option>
                @foreach($regions as $region)
                    <option value="{{ $region['code'] }}">{{ $region['name'] }}</option>
                @endforeach
            </select>
        </div>

        <!-- Province -->
        <div class="event-field">
            <label for="provinceCode">Province</label>
            <select id="provinceCode" 
                    wire:model.live="provinceCode"
                    class="location-control"
                    @disabled(empty($provinces))>
                <option value="">{{ empty($provinces) ? 'Select Region first' : 'Select Province' }}</option>
                @foreach($provinces as $province)
                    <option value="{{ $province['code'] }}">{{ $province['name'] }}</option>
                @endforeach
            </select>
        </div>

        <!-- City/Municipality -->
        <div class="event-field">
            <label for="cityCode">City / municipality</label>
            <select id="cityCode" 
                    wire:model.live="cityCode"
                    class="location-control"
                    @disabled(empty($cities))>
                <option value="">{{ empty($cities) ? 'Select Province first' : 'Select City/Municipality' }}</option>
                @foreach($cities as $city)
                    <option value="{{ $city['code'] }}">{{ $city['name'] }}</option>
                @endforeach
            </select>
        </div>

        <!-- Barangay -->
        <div class="event-field">
            <label for="barangayCode">Barangay</label>
            <select id="barangayCode" 
                    wire:model.live="barangayCode"
                    class="location-control"
                    @disabled(empty($barangays))>
                <option value="">{{ empty($barangays) ? 'Select City first' : 'Select Barangay' }}</option>
                @foreach($barangays as $barangay)
                    <option value="{{ $barangay['code'] }}">{{ $barangay['name'] }}</option>
                @endforeach
            </select>
        </div>

        <!-- Street Address -->
        <div class="event-field location-street-field">
            <label for="streetAddress">Street address / house no. / building</label>
            <div class="location-search-control">
                <input type="text" 
                       id="streetAddress" 
                       wire:model="streetAddress"
                       wire:blur="geocodeStreetAddress"
                       class="location-control"
                       placeholder="e.g. 123 Rizal Street, Purok 1">
                <button type="button"
                        wire:click="geocodeStreetAddress"
                        class="location-search-button" aria-label="Locate this address">
                    <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>

    @if($fullAddress)
        <div class="location-address-preview">
            <x-heroicon-o-map-pin />
            <div><strong>Selected address</strong><p>{{ $fullAddress }}</p></div>
        </div>
    @endif

    <div class="location-map-section">
        <div class="location-map-heading"><div><span>02</span><div><h4>Exact map position</h4><p>Click the map or drag the marker to refine the pin.</p></div></div></div>
        
        @php $mapId = 'map-' . $this->getId(); @endphp
        
        <div 
            wire:ignore
            x-data="addressMapPicker(@js([
                'latitude' => $latitude,
                'longitude' => $longitude,
                'defaultLatitude' => $defaultLatitude,
                'defaultLongitude' => $defaultLongitude,
                'defaultZoom' => $defaultZoom,
                'mapId' => $mapId
            ]))"
            x-init="initMap()"
            @coordinates-geocoded.window="updateMarkerPosition($event.detail.latitude, $event.detail.longitude)"
            class="location-map-frame"
        >
            <div id="{{ $mapId }}" class="map-base location-map-canvas"></div>
        </div>
        
        <!-- Loading Overlay -->
        <div wire:loading.flex wire:target="updatedRegionCode, updatedProvinceCode, updatedCityCode, updatedBarangayCode, geocodeStreetAddress" 
             class="map-loading-overlay location-loading">
            <div>
                <x-heroicon-o-arrow-path class="animate-spin h-5 w-5" />
                <span>Locating...</span>
            </div>
        </div>
        
    </div>

    @if($latitude && $longitude)
        <div class="location-coordinates">
            <div><span>Latitude</span><strong>{{ number_format($latitude, 6) }}</strong></div>
            <div><span>Longitude</span><strong>{{ number_format($longitude, 6) }}</strong></div>
            <div>
                <span class="location-confirmed">
                    <x-heroicon-s-check />
                    Location set
                </span>
            </div>
        </div>
    @endif

    <!-- Hidden inputs for form submission -->
    <input type="hidden" name="latitude" value="{{ $latitude }}">
    <input type="hidden" name="longitude" value="{{ $longitude }}">
    <input type="hidden" name="address" value="{{ $fullAddress }}">
</div>

@script
<script>
    Alpine.data('addressMapPicker', (config) => ({
        map: null,
        marker: null,
        latitude: config.latitude,
        longitude: config.longitude,
        defaultLatitude: config.defaultLatitude,
        defaultLongitude: config.defaultLongitude,
        defaultZoom: config.defaultZoom,
        mapId: config.mapId,

        initMap() {
            // Wait for DOM to be ready
            this.$nextTick(() => {
                // Wait for Leaflet to be available
                if (typeof L === 'undefined') {
                    console.error('Leaflet is not loaded');
                    return;
                }

                const mapContainer = document.getElementById(this.mapId);
                if (!mapContainer) {
                    console.error('Map container not found:', this.mapId);
                    return;
                }

                // Check if map already initialized on this container
                if (mapContainer._leaflet_id) {
                    return;
                }

                const initialLat = this.latitude ?? this.defaultLatitude;
                const initialLng = this.longitude ?? this.defaultLongitude;
                const initialZoom = this.latitude ? 15 : this.defaultZoom;

                // Initialize the map
                this.map = L.map(this.mapId).setView([initialLat, initialLng], initialZoom);

                // Add OpenStreetMap tiles
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                    maxZoom: 19
                }).addTo(this.map);

                // Add draggable marker if we have coordinates
                if (this.latitude && this.longitude) {
                    this.addMarker(this.latitude, this.longitude);
                }

                // Allow clicking on map to place/move marker
                this.map.on('click', (e) => {
                    this.placeMarker(e.latlng.lat, e.latlng.lng);
                });

                // Fix map rendering issues
                setTimeout(() => {
                    this.map.invalidateSize();
                }, 100);
            });
        },

        addMarker(lat, lng) {
            if (!this.map) return;
            
            // Remove existing marker if any
            if (this.marker) {
                this.map.removeLayer(this.marker);
            }

            // Create custom icon
            const customIcon = L.divIcon({
                html: `<x-heroicon-s-map-pin class="w-8 h-8 text-red-500 drop-shadow-lg" />`,
                className: 'custom-marker',
                iconSize: [32, 32],
                iconAnchor: [16, 32],
            });

            // Add marker
            this.marker = L.marker([lat, lng], {
                draggable: true,
                icon: customIcon
            }).addTo(this.map);

            // Handle marker drag
            this.marker.on('dragend', (e) => {
                const position = e.target.getLatLng();
                this.latitude = position.lat;
                this.longitude = position.lng;
                
                // Update Livewire component
                $wire.updateCoordinates(position.lat, position.lng);
            });
        },

        placeMarker(lat, lng) {
            this.latitude = lat;
            this.longitude = lng;
            this.addMarker(lat, lng);
            
            // Update Livewire component
            $wire.updateCoordinates(lat, lng);
        },

        updateMarkerPosition(lat, lng) {
            if (!this.map) return;
            
            this.latitude = lat;
            this.longitude = lng;

            if (this.marker) {
                this.marker.setLatLng([lat, lng]);
            } else {
                this.addMarker(lat, lng);
            }

            // Center map on new position with zoom
            this.map.setView([lat, lng], 16, {
                animate: true,
                duration: 0.5
            });
        }
    }));
</script>
@endscript

