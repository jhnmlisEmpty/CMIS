<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkMemberRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_api_creates_pending_members_atomically(): void
    {
        $response = $this->postJson('/api/member-registrations/bulk', [
            'members' => [
                [
                    'name' => 'Maria Santos',
                    'email' => 'maria@example.com',
                    'gender' => 'female',
                    'birthdate' => '1995-04-17',
                    'role' => User::ROLE_ADMIN,
                    'status' => User::STATUS_ACTIVE,
                ],
                [
                    'name' => 'Paolo Reyes',
                    'email' => 'paolo@example.com',
                    'gender' => 'male',
                    'phone' => '+63 917 111 2233',
                ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.created_count', 2)
            ->assertJsonPath('data.registrations.0.index', 0)
            ->assertJsonPath('data.registrations.0.status', 'pending_approval')
            ->assertJsonPath('data.registrations.1.index', 1)
            ->assertJsonStructure([
                'data' => [
                    'registrations' => [
                        ['index', 'registration_id', 'status'],
                        ['index', 'registration_id', 'status'],
                    ],
                ],
            ]);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', [
            'email' => 'maria@example.com',
            'role' => User::ROLE_MEMBER,
            'status' => User::STATUS_INACTIVE,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'paolo@example.com',
            'role' => User::ROLE_MEMBER,
            'status' => User::STATUS_INACTIVE,
        ]);
    }

    public function test_invalid_member_rejects_the_entire_batch(): void
    {
        $this->postJson('/api/member-registrations/bulk', [
            'members' => [
                [
                    'name' => 'Valid Member',
                    'email' => 'valid@example.com',
                    'gender' => 'female',
                ],
                [
                    'email' => 'invalid-email',
                    'gender' => 'male',
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['members.1.name', 'members.1.email']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_bulk_api_rejects_duplicate_emails_within_the_payload(): void
    {
        $this->postJson('/api/member-registrations/bulk', [
            'members' => [
                ['name' => 'First Member', 'email' => 'same@example.com', 'gender' => 'female'],
                ['name' => 'Second Member', 'email' => 'SAME@example.com', 'gender' => 'male'],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['members.0.email', 'members.1.email']);

        $this->assertDatabaseCount('users', 0);
    }
}
