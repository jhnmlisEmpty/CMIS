<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PhilippineAddressService;
use Illuminate\Http\JsonResponse;

class PhilippineAddressController extends Controller
{
    public function regions(PhilippineAddressService $service): JsonResponse
    {
        return $this->optionsResponse($service->getRegions());
    }

    public function provinces(string $regionCode, PhilippineAddressService $service): JsonResponse
    {
        return $this->optionsResponse($service->getProvinces($regionCode));
    }

    public function cities(string $provinceCode, PhilippineAddressService $service): JsonResponse
    {
        return $this->optionsResponse($service->getCities($provinceCode));
    }

    public function barangays(string $cityCode, PhilippineAddressService $service): JsonResponse
    {
        return $this->optionsResponse($service->getBarangays($cityCode));
    }

    private function optionsResponse(array $options): JsonResponse
    {
        return response()->json(['data' => $options]);
    }
}
