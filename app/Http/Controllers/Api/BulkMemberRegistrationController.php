<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhilippineAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BulkMemberRegistrationController extends Controller
{
    /**
     * Store a validated batch of pending member registrations.
     */
    public function store(Request $request, PhilippineAddressService $addressService): JsonResponse
    {
        $validated = $request->validate([
            'members' => ['required', 'array', 'min:1', 'max:50'],
            'members.*.name' => ['required', 'string', 'max:255', 'regex:/^(?:\p{L}\p{M}*)+(?:[.\x{2019}\x{27}-](?:\p{L}\p{M}*)+)*(?:\s+(?:\p{L}\p{M}*)+(?:[.\x{2019}\x{27}-](?:\p{L}\p{M}*)+)*)+$/u'],
            'members.*.email' => ['required', 'string', 'email', 'max:255', 'distinct:ignore_case', 'unique:users,email'],
            'members.*.gender' => ['required', 'in:'.User::GENDER_MALE.','.User::GENDER_FEMALE],
            'members.*.birthdate' => ['nullable', 'date', 'before:today'],
            'members.*.phone' => ['nullable', 'string', 'max:20'],
            'members.*.address' => ['nullable', 'string', 'max:500'],
            'members.*.region_code' => ['nullable', 'string', 'max:50'],
            'members.*.province_code' => ['nullable', 'string', 'max:50'],
            'members.*.city_code' => ['nullable', 'string', 'max:50'],
            'members.*.barangay_code' => ['nullable', 'string', 'max:50'],
            'members.*.street_address' => ['nullable', 'string', 'max:255'],
            'members.*.latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'members.*.longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $validated['members'] = collect($validated['members'])->map(function (array $member): array {
            $member['name'] = Str::of($member['name'])->squish()->title()->toString();
            return $member;
        })->all();

        $preparedMembers = collect($validated['members'])->map(function (array $member) use ($addressService): array {
            $structuredAddress = $addressService->buildFullAddress(
                $member['street_address'] ?? null,
                $member['barangay_code'] ?? null,
                $member['city_code'] ?? null,
                $member['province_code'] ?? null,
                $member['region_code'] ?? null,
            );

            return [
                ...$member,
                'address' => $structuredAddress !== '' ? $structuredAddress : ($member['address'] ?? null),
                'password' => Hash::make(Str::random(64)),
                'role' => User::ROLE_MEMBER,
                'status' => User::STATUS_INACTIVE,
            ];
        });

        $registrations = DB::transaction(function () use ($preparedMembers): array {
            return $preparedMembers
                ->map(function (array $member, int $index): array {
                    $createdMember = User::create($member);

                    return [
                        'index' => $index,
                        'registration_id' => $createdMember->uuid,
                        'status' => 'pending_approval',
                    ];
                })
                ->values()
                ->all();
        });

        return response()->json([
            'message' => count($registrations).' registrations received and pending approval.',
            'data' => [
                'created_count' => count($registrations),
                'registrations' => $registrations,
            ],
        ], 201);
    }
}
