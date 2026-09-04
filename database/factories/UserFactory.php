<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Sample Metro Manila addresses with PSGC codes
        $addresses = [
            [
                'region_code' => '130000000',
                'province_code' => '137400000',
                'city_code' => '137400000',
                'barangay_code' => '137404009',
                'city_name' => 'Quezon City',
                'barangay_name' => 'Commonwealth',
            ],
            [
                'region_code' => '130000000',
                'province_code' => '133900000',
                'city_code' => '133900000',
                'barangay_code' => '133903010',
                'city_name' => 'Manila',
                'barangay_name' => 'Ermita',
            ],
            [
                'region_code' => '130000000',
                'province_code' => '137600000',
                'city_code' => '137600000',
                'barangay_code' => '137604020',
                'city_name' => 'Makati',
                'barangay_name' => 'Poblacion',
            ],
            [
                'region_code' => '130000000',
                'province_code' => '137400000',
                'city_code' => '137401000',
                'barangay_code' => '137401008',
                'city_name' => 'Pasig',
                'barangay_name' => 'Kapitolyo',
            ],
            [
                'region_code' => '130000000',
                'province_code' => '137500000',
                'city_code' => '137500000',
                'barangay_code' => '137503005',
                'city_name' => 'Mandaluyong',
                'barangay_name' => 'Addition Hills',
            ],
        ];

        $selectedAddress = fake()->randomElement($addresses);
        $streetAddress = fake()->buildingNumber() . ' ' . fake()->streetName();

        return [
            'uuid' => Str::uuid()->toString(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'gender' => fake()->randomElement(['male', 'female']),
            'birthdate' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'phone' => fake()->phoneNumber(),
            'address' => $streetAddress . ', Barangay ' . $selectedAddress['barangay_name'] . ', ' . $selectedAddress['city_name'] . ', Metro Manila',
            'region_code' => $selectedAddress['region_code'],
            'province_code' => $selectedAddress['province_code'],
            'city_code' => $selectedAddress['city_code'],
            'barangay_code' => $selectedAddress['barangay_code'],
            'street_address' => $streetAddress,
            'latitude' => fake()->latitude(14.4, 14.7),
            'longitude' => fake()->longitude(120.9, 121.1),
            'role' => 'member',
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Set the user as admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Set the user as pastor.
     */
    public function pastor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'pastor',
        ]);
    }

    /**
     * Set the user as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
