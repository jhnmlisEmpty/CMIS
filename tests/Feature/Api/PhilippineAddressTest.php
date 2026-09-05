<?php

namespace Tests\Feature\Api;

use App\Services\PhilippineAddressService;
use Tests\TestCase;

class PhilippineAddressTest extends TestCase
{
    public function test_regions_are_available_to_the_registration_assistant(): void
    {
        $service = $this->mock(PhilippineAddressService::class);
        $service->shouldReceive('getRegions')->once()->andReturn([
            ['code' => '0100000000', 'name' => 'Ilocos Region'],
        ]);

        $this->getJson('/api/addresses/regions')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    ['code' => '0100000000', 'name' => 'Ilocos Region'],
                ],
            ]);
    }
}
