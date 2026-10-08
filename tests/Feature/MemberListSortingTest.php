<?php

namespace Tests\Feature;

use App\Livewire\User\IndexUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberListSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_members_can_be_sorted_by_role_in_both_directions(): void
    {
        $admin = User::factory()->create([
            'name' => 'Administrator',
            'role' => User::ROLE_ADMIN,
        ]);
        User::factory()->create([
            'name' => 'Pastor User',
            'role' => User::ROLE_PASTOR,
        ]);
        User::factory()->create([
            'name' => 'Member User',
            'role' => User::ROLE_MEMBER,
        ]);

        Livewire::actingAs($admin)
            ->test(IndexUser::class)
            ->call('sort', 'role')
            ->assertHasNoErrors()
            ->assertViewHas('users', fn ($users) => $users->pluck('name')->all() === [
                'Administrator',
                'Member User',
                'Pastor User',
            ])
            ->call('sort', 'role')
            ->assertHasNoErrors()
            ->assertViewHas('users', fn ($users) => $users->pluck('name')->all() === [
                'Pastor User',
                'Member User',
                'Administrator',
            ]);
    }

    public function test_unknown_sort_columns_fall_back_to_the_default_sort(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(IndexUser::class)
            ->set('sortBy', 'not_a_column')
            ->assertHasNoErrors()
            ->assertViewHas('users', fn ($users) => $users->contains('id', $admin->id));
    }
}
