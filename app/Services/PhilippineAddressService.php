<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhilippineAddressService
{
    protected string $baseUrl = 'https://psgc.cloud/api';
    protected int $cacheDuration = 86400; // 24 hours in seconds

    public function getRegions(): array
    {
        return Cache::remember('psgc_regions', $this->cacheDuration, function () {
            $response = Http::get("{$this->baseUrl}/regions");
            
            if ($response->successful()) {
                return collect($response->json())
                    ->map(fn($item) => [
                        'code' => $item['code'],
                        'name' => $item['name'],
                    ])
                    ->sortBy('name')
                    ->values()
                    ->toArray();
            }
            
            return [];
        });
    }

    public function getProvinces(?string $regionCode = null): array
    {
        if (!$regionCode) {
            return [];
        }

        return Cache::remember("psgc_provinces_{$regionCode}", $this->cacheDuration, function () use ($regionCode) {
            $response = Http::get("{$this->baseUrl}/regions/{$regionCode}/provinces");
            
            if ($response->successful()) {
                return collect($response->json())
                    ->map(fn($item) => [
                        'code' => $item['code'],
                        'name' => $item['name'],
                    ])
                    ->sortBy('name')
                    ->values()
                    ->toArray();
            }
            
            return [];
        });
    }

    public function getCities(?string $provinceCode = null): array
    {
        if (!$provinceCode) {
            return [];
        }

        return Cache::remember("psgc_cities_{$provinceCode}", $this->cacheDuration, function () use ($provinceCode) {
            $response = Http::get("{$this->baseUrl}/provinces/{$provinceCode}/cities-municipalities");
            
            if ($response->successful()) {
                return collect($response->json())
                    ->map(fn($item) => [
                        'code' => $item['code'],
                        'name' => $item['name'],
                    ])
                    ->sortBy('name')
                    ->values()
                    ->toArray();
            }
            
            return [];
        });
    }

    public function getBarangays(?string $cityCode = null): array
    {
        if (!$cityCode) {
            return [];
        }

        return Cache::remember("psgc_barangays_{$cityCode}", $this->cacheDuration, function () use ($cityCode) {
            $response = Http::get("{$this->baseUrl}/cities-municipalities/{$cityCode}/barangays");
            
            if ($response->successful()) {
                return collect($response->json())
                    ->map(fn($item) => [
                        'code' => $item['code'],
                        'name' => $item['name'],
                    ])
                    ->sortBy('name')
                    ->values()
                    ->toArray();
            }
            
            return [];
        });
    }

    public function getRegionByCode(string $code): ?array
    {
        return Cache::remember("psgc_region_{$code}", $this->cacheDuration, function () use ($code) {
            $response = Http::get("{$this->baseUrl}/regions/{$code}");
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'code' => $data['code'],
                    'name' => $data['name'],
                ];
            }
            
            return null;
        });
    }

    public function getProvinceByCode(string $code): ?array
    {
        return Cache::remember("psgc_province_{$code}", $this->cacheDuration, function () use ($code) {
            $response = Http::get("{$this->baseUrl}/provinces/{$code}");
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'code' => $data['code'],
                    'name' => $data['name'],
                ];
            }
            
            return null;
        });
    }

    public function getCityByCode(string $code): ?array
    {
        return Cache::remember("psgc_city_{$code}", $this->cacheDuration, function () use ($code) {
            $response = Http::get("{$this->baseUrl}/cities-municipalities/{$code}");
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'code' => $data['code'],
                    'name' => $data['name'],
                ];
            }
            
            return null;
        });
    }

    public function getBarangayByCode(string $code): ?array
    {
        return Cache::remember("psgc_barangay_{$code}", $this->cacheDuration, function () use ($code) {
            $response = Http::get("{$this->baseUrl}/barangays/{$code}");
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'code' => $data['code'],
                    'name' => $data['name'],
                ];
            }
            
            return null;
        });
    }

    public function buildFullAddress(
        ?string $streetAddress,
        ?string $barangayCode,
        ?string $cityCode,
        ?string $provinceCode,
        ?string $regionCode
    ): string {
        $parts = [];

        if ($streetAddress) {
            $parts[] = $streetAddress;
        }

        if ($barangayCode) {
            $barangay = $this->getBarangayByCode($barangayCode);
            if ($barangay) {
                $parts[] = 'Brgy. ' . $barangay['name'];
            }
        }

        if ($cityCode) {
            $city = $this->getCityByCode($cityCode);
            if ($city) {
                $parts[] = $city['name'];
            }
        }

        if ($provinceCode) {
            $province = $this->getProvinceByCode($provinceCode);
            if ($province) {
                $parts[] = $province['name'];
            }
        }

        if ($regionCode) {
            $region = $this->getRegionByCode($regionCode);
            if ($region) {
                $parts[] = $region['name'];
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Geocode address using Google Geocoding API
     * Requires GOOGLE_MAPS_API_KEY in .env
     */
    public function geocodeAddress(string $address): ?array
    {
        if (empty($address)) {
            return null;
        }

        $apiKey = config('services.google.maps_api_key');
        
        if (empty($apiKey)) {
            // Fallback to Nominatim if no Google API key
            return $this->geocodeWithNominatim($address);
        }

        // Cache the geocoding result to avoid repeated API calls
        $cacheKey = 'geocode_' . md5($address);
        
        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($address, $apiKey) {
            try {
                $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $address . ', Philippines',
                    'key' => $apiKey,
                    'region' => 'ph', // Bias results to Philippines
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if ($data['status'] === 'OK' && !empty($data['results'])) {
                        $location = $data['results'][0]['geometry']['location'];
                        return [
                            'latitude' => (float) $location['lat'],
                            'longitude' => (float) $location['lng'],
                            'formatted_address' => $data['results'][0]['formatted_address'] ?? null,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Google Geocoding failed: ' . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Geocode address using OpenStreetMap Nominatim (free fallback)
     */
    public function geocodeWithNominatim(string $address): ?array
    {
        if (empty($address)) {
            return null;
        }

        $cacheKey = 'geocode_nominatim_' . md5($address);
        
        return Cache::remember($cacheKey, $this->cacheDuration, function () use ($address) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'ChurchManagementSystem/1.0'
                ])->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'json',
                    'q' => $address . ', Philippines',
                    'limit' => 1,
                    'addressdetails' => 1,
                ]);

                if ($response->successful()) {
                    $results = $response->json();
                    
                    if (!empty($results)) {
                        return [
                            'latitude' => (float) $results[0]['lat'],
                            'longitude' => (float) $results[0]['lon'],
                            'formatted_address' => $results[0]['display_name'] ?? null,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Nominatim Geocoding failed: ' . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Geocode using PSGC codes - builds full address and geocodes it
     */
    public function geocodeFromPSGCCodes(
        ?string $streetAddress,
        ?string $barangayCode,
        ?string $cityCode,
        ?string $provinceCode,
        ?string $regionCode
    ): ?array {
        $fullAddress = $this->buildFullAddress(
            $streetAddress,
            $barangayCode,
            $cityCode,
            $provinceCode,
            $regionCode
        );

        if (empty($fullAddress)) {
            return null;
        }

        return $this->geocodeAddress($fullAddress);
    }
}
