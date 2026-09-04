<?php

namespace App\Livewire\Components;

use App\Services\PhilippineAddressService;
use Livewire\Component;

class AddressMapPicker extends Component
{
    // PSGC Address Fields
    public string $regionCode = '';
    public string $provinceCode = '';
    public string $cityCode = '';
    public string $barangayCode = '';
    public string $streetAddress = '';
    
    // Coordinates
    public ?float $latitude = null;
    public ?float $longitude = null;
    
    // Full address string
    public string $fullAddress = '';
    
    // Dropdown options
    public array $regions = [];
    public array $provinces = [];
    public array $cities = [];
    public array $barangays = [];
    
    // Default center (Philippines)
    public float $defaultLatitude = 12.8797;
    public float $defaultLongitude = 121.7740;
    public int $defaultZoom = 6;

    public function mount(
        ?string $regionCode = null,
        ?string $provinceCode = null,
        ?string $cityCode = null,
        ?string $barangayCode = null,
        ?string $streetAddress = null,
        ?float $latitude = null,
        ?float $longitude = null
    ): void {
        $service = app(PhilippineAddressService::class);
        $this->regions = $service->getRegions();
        
        // Initialize with existing values if provided
        if ($regionCode) {
            $this->regionCode = $regionCode;
            $this->provinces = $service->getProvinces($regionCode);
        }
        
        if ($provinceCode) {
            $this->provinceCode = $provinceCode;
            $this->cities = $service->getCities($provinceCode);
        }
        
        if ($cityCode) {
            $this->cityCode = $cityCode;
            $this->barangays = $service->getBarangays($cityCode);
        }
        
        if ($barangayCode) {
            $this->barangayCode = $barangayCode;
        }
        
        if ($streetAddress) {
            $this->streetAddress = $streetAddress;
        }
        
        if ($latitude !== null && $longitude !== null) {
            $this->latitude = $latitude;
            $this->longitude = $longitude;
        }
        
        $this->buildAddress();
    }

    public function updatedRegionCode(): void
    {
        $this->provinceCode = '';
        $this->cityCode = '';
        $this->barangayCode = '';
        $this->cities = [];
        $this->barangays = [];

        if ($this->regionCode) {
            $service = app(PhilippineAddressService::class);
            $this->provinces = $service->getProvinces($this->regionCode);
        } else {
            $this->provinces = [];
        }
        
        $this->buildAddress();
        $this->geocodeAndUpdateMap();
    }

    public function updatedProvinceCode(): void
    {
        $this->cityCode = '';
        $this->barangayCode = '';
        $this->barangays = [];

        if ($this->provinceCode) {
            $service = app(PhilippineAddressService::class);
            $this->cities = $service->getCities($this->provinceCode);
        } else {
            $this->cities = [];
        }
        
        $this->buildAddress();
        $this->geocodeAndUpdateMap();
    }

    public function updatedCityCode(): void
    {
        $this->barangayCode = '';

        if ($this->cityCode) {
            $service = app(PhilippineAddressService::class);
            $this->barangays = $service->getBarangays($this->cityCode);
        } else {
            $this->barangays = [];
        }
        
        $this->buildAddress();
        $this->geocodeAndUpdateMap();
    }

    public function updatedBarangayCode(): void
    {
        $this->buildAddress();
        $this->geocodeAndUpdateMap();
    }

    public function updatedStreetAddress(): void
    {
        $this->buildAddress();
        // Don't auto-geocode on every keystroke for street address
    }

    /**
     * Called when user finishes typing street address (on blur or button click)
     */
    public function geocodeStreetAddress(): void
    {
        $this->geocodeAndUpdateMap();
    }

    /**
     * Called from JavaScript when user drags the marker
     */
    public function updateCoordinates(float $latitude, float $longitude): void
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        
        $this->emitAddressUpdated();
    }

    protected function buildAddress(): void
    {
        $service = app(PhilippineAddressService::class);
        $this->fullAddress = $service->buildFullAddress(
            $this->streetAddress,
            $this->barangayCode,
            $this->cityCode,
            $this->provinceCode,
            $this->regionCode
        );
        
        $this->dispatch('address-updated', address: $this->fullAddress);
        
        // Always emit address data to parent so it can be saved
        $this->emitAddressUpdated();
    }
    
    protected function emitAddressUpdated(): void
    {
        // Dispatch to parent component with current address data
        $this->dispatch('location-selected', 
            latitude: $this->latitude,
            longitude: $this->longitude,
            address: $this->fullAddress,
            regionCode: $this->regionCode,
            provinceCode: $this->provinceCode,
            cityCode: $this->cityCode,
            barangayCode: $this->barangayCode,
            streetAddress: $this->streetAddress
        );
    }

    protected function geocodeAndUpdateMap(): void
    {
        if (!$this->cityCode) {
            return;
        }

        $service = app(PhilippineAddressService::class);
        $coordinates = $service->geocodeFromPSGCCodes(
            $this->streetAddress,
            $this->barangayCode,
            $this->cityCode,
            $this->provinceCode,
            $this->regionCode
        );

        if ($coordinates) {
            $this->latitude = $coordinates['latitude'];
            $this->longitude = $coordinates['longitude'];
            
            // Dispatch event to update map marker position
            $this->dispatch('coordinates-geocoded', 
                latitude: $this->latitude, 
                longitude: $this->longitude
            );
            
            // Address is already emitted in buildAddress(), just need to emit updated coordinates
            $this->emitAddressUpdated();
        }
    }

    public function render()
    {
        return view('livewire.components.address-map-picker');
    }
}
