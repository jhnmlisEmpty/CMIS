<?php

namespace Tests\Feature;

use App\Livewire\Components\UsersMap;
use App\Models\SmallGroup;
use App\Models\SmallGroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UsersMapFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_filters_use_the_same_filtered_members_as_the_markers(): void
    {
        $leader = User::factory()->create([
            'latitude' => null,
            'longitude' => null,
        ]);

        $smallGroup = SmallGroup::create([
            'name' => 'Lingayen Community',
            'leader_id' => $leader->id,
            'status' => SmallGroup::STATUS_ACTIVE,
        ]);

        $matchingBirthdate = now()->subYears(30)->subMonth();

        $matching = User::factory()->create([
            'name' => 'Maria Santos',
            'address' => 'Lingayen, Pangasinan',
            'birthdate' => $matchingBirthdate->toDateString(),
            'role' => User::ROLE_PASTOR,
            'status' => User::STATUS_ACTIVE,
            'latitude' => 16.0218,
            'longitude' => 120.2319,
        ]);

        User::factory()->create([
            'name' => 'Jose Ramos',
            'address' => 'Cebu City, Cebu',
            'birthdate' => now()->subYears(50)->toDateString(),
            'role' => User::ROLE_MEMBER,
            'status' => User::STATUS_INACTIVE,
            'latitude' => 10.3157,
            'longitude' => 123.8854,
        ]);

        SmallGroupMember::create([
            'small_group_id' => $smallGroup->id,
            'user_id' => $matching->id,
            'status' => SmallGroupMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);

        $filterCases = [
            ['search' => 'Maria'],
            ['locationFilter' => 'Pangasinan'],
            [
                'birthdateFrom' => $matchingBirthdate->copy()->subYear()->toDateString(),
                'birthdateTo' => $matchingBirthdate->copy()->addYear()->toDateString(),
            ],
            ['minAge' => '29', 'maxAge' => '31'],
            ['roleFilter' => User::ROLE_PASTOR],
            ['statusFilter' => User::STATUS_ACTIVE],
            ['smallGroupFilter' => (string) $smallGroup->id],
        ];

        foreach ($filterCases as $filters) {
            $component = Livewire::test(UsersMap::class, ['statusFilter' => '']);

            foreach ($filters as $property => $value) {
                $component->set($property, $value);
            }

            $component
                ->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$matching->id])
                ->assertViewHas('usersForMap', fn ($users) => count($users) === 1 && $users[0]['id'] === $matching->id)
                ->assertDispatched('users-map-updated');
        }
    }
}
