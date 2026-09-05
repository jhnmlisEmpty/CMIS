<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\PhilippineAddressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_assistant_can_register_a_member(): void
    {
        $response = $this->postJson('/api/member-registrations', [
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'gender' => 'female',
            'birthdate' => '1995-04-17',
            'phone' => '+63 917 123 4567',
            'address' => 'Quezon City, Metro Manila',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Registration received and pending approval.')
            ->assertJsonPath('data.status', 'pending_approval')
            ->assertJsonStructure(['data' => ['registration_id']]);

        $this->assertDatabaseHas('users', [
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'role' => User::ROLE_MEMBER,
            'status' => User::STATUS_INACTIVE,
        ]);
        $this->assertSame('1995-04-17', User::where('email', 'maria@example.com')->firstOrFail()->birthdate?->toDateString());
    }

    public function test_registration_cannot_set_a_privileged_role_or_active_status(): void
    {
        $this->postJson('/api/member-registrations', [
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'gender' => 'female',
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'maria@example.com',
            'role' => User::ROLE_MEMBER,
            'status' => User::STATUS_INACTIVE,
        ]);
    }

    public function test_registration_validates_required_profile_details(): void
    {
        $this->postJson('/api/member-registrations', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'gender']);
    }

    public function test_structured_address_is_built_from_psgc_codes(): void
    {
        $service = $this->mock(PhilippineAddressService::class);
        $service->shouldReceive('buildFullAddress')
            ->once()
            ->with('12 Hope Street', '0123456789', '012345678', '01234567', '012345')
            ->andReturn('12 Hope Street, Brgy. San Roque, Urdaneta City, Pangasinan, Ilocos Region');

        $this->postJson('/api/member-registrations', [
            'name' => 'Elena Ramos',
            'email' => 'elena@example.com',
            'gender' => 'female',
            'street_address' => '12 Hope Street',
            'barangay_code' => '0123456789',
            'city_code' => '012345678',
            'province_code' => '01234567',
            'region_code' => '012345',
        ])->assertCreated();

        $this->assertDatabaseHas('users', [
            'email' => 'elena@example.com',
            'address' => '12 Hope Street, Brgy. San Roque, Urdaneta City, Pangasinan, Ilocos Region',
        ]);
    }
}
