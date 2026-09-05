<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhilippineAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MemberRegistrationController extends Controller
{
    /**
     * Store a member registration submitted by the public assistant.
     */
    public function store(Request $request, PhilippineAddressService $addressService): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^(?:\p{L}\p{M}*)+(?:[.\x{2019}\x{27}-](?:\p{L}\p{M}*)+)*(?:\s+(?:\p{L}\p{M}*)+(?:[.\x{2019}\x{27}-](?:\p{L}\p{M}*)+)*)+$/u'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'gender' => ['required', 'in:'.User::GENDER_MALE.','.User::GENDER_FEMALE],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'region_code' => ['nullable', 'string', 'max:50'],
            'province_code' => ['nullable', 'string', 'max:50'],
            'city_code' => ['nullable', 'string', 'max:50'],
            'barangay_code' => ['nullable', 'string', 'max:50'],
            'street_address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $validated['name'] = Str::of($validated['name'])->squish()->title()->toString();

        $structuredAddress = $addressService->buildFullAddress(
            $validated['street_address'] ?? null,
            $validated['barangay_code'] ?? null,
            $validated['city_code'] ?? null,
            $validated['province_code'] ?? null,
            $validated['region_code'] ?? null,
        );

        $member = User::create([
            ...$validated,
            'address' => $structuredAddress !== '' ? $structuredAddress : ($validated['address'] ?? null),
            'password' => Hash::make(Str::random(64)),
            'role' => User::ROLE_MEMBER,
            'status' => User::STATUS_INACTIVE,
        ]);

        return response()->json([
            'message' => 'Registration received and pending approval.',
            'data' => [
                'registration_id' => $member->uuid,
                'status' => 'pending_approval',
            ],
        ], 201);
    }
}
