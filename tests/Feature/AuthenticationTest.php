<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/users')->assertRedirect(route('login'));
    }

    public function test_active_member_can_login_with_name_and_birthdate(): void
    {
        $user = User::factory()->create([
            'name' => 'Maria Dela Cruz',
            'birthdate' => '1991-04-17',
            'status' => User::STATUS_ACTIVE,
        ]);

        $response = $this->post(route('login.store'), [
            'name' => '  maria dela cruz  ',
            'birthdate' => '1991-04-17',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_member_details_are_rejected(): void
    {
        User::factory()->create([
            'name' => 'Joel Mendoza',
            'birthdate' => '1987-11-03',
        ]);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'name' => 'Joel Mendoza',
            'birthdate' => '1987-11-04',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('name');
        $this->assertGuest();
    }

    public function test_inactive_member_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'name' => 'Ana Reyes',
            'birthdate' => '1994-08-22',
        ]);

        $this->post(route('login.store'), [
            'name' => 'Ana Reyes',
            'birthdate' => '1994-08-22',
        ])->assertSessionHasErrors('name');

        $this->assertGuest();
    }

    public function test_authenticated_member_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
