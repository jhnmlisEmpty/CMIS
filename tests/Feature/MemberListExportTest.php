<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberListExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_download_filtered_member_list_csv(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
        ]);

        User::factory()->create([
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'role' => User::ROLE_MEMBER,
            'status' => User::STATUS_ACTIVE,
            'birthdate' => '1995-04-17',
            'address' => '123 Metro Manila Avenue, Barangay Commonwealth, Quezon City, Metro Manila',
        ]);

        User::factory()->create([
            'name' => 'Jose Ramos',
            'email' => 'jose@example.com',
            'role' => User::ROLE_MEMBER,
            'status' => User::STATUS_ACTIVE,
            'birthdate' => '1980-01-10',
            'address' => '456 Cebu Street, Cebu City, Cebu',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('users.export', [
                'roleFilter' => User::ROLE_MEMBER,
                'statusFilter' => User::STATUS_ACTIVE,
                'locationFilter' => 'Metro Manila',
                'birthdateFrom' => '1990-01-01',
                'birthdateTo' => '2005-12-31',
                'minAge' => 18,
                'maxAge' => 35,
            ]));

        $response->assertOk();
        $response->assertDownload();

        $this->assertNotNull($response->headers->get('content-disposition'));
        $this->assertStringContainsString('attachment; filename=', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.csv', $response->headers->get('content-disposition'));
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Maria Santos', $csv);
        $this->assertStringContainsString('Birthdate', $csv);
        $this->assertStringContainsString('1995-04-17', $csv);
        $this->assertStringContainsString('Age', $csv);
        $this->assertStringNotContainsString('Role', $csv);
        $this->assertStringNotContainsString('Status', $csv);
        $this->assertStringNotContainsString('Jose Ramos', $csv);
    }
}
