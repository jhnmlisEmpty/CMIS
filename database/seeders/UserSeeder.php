<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User - Manila
        User::create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Admin User',
            'email' => 'admin@cmis.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'gender' => 'male',
            'birthdate' => '1985-01-15',
            'phone' => '+63 912 345 6789',
            'address' => '123 Church Street, Barangay 659, Manila, Metro Manila',
            'region_code' => '130000000',
            'province_code' => '133900000',
            'city_code' => '133900000',
            'barangay_code' => '133903010',
            'street_address' => '123 Church Street',
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Create Pastor - Quezon City
        User::create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Pastor John',
            'email' => 'pastor@cmis.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'gender' => 'male',
            'birthdate' => '1975-06-20',
            'phone' => '+63 923 456 7890',
            'address' => '456 Faith Avenue, Barangay Commonwealth, Quezon City, Metro Manila',
            'region_code' => '130000000',
            'province_code' => '137400000',
            'city_code' => '137400000',
            'barangay_code' => '137404009',
            'street_address' => '456 Faith Avenue',
            'latitude' => 14.6760,
            'longitude' => 121.0437,
            'role' => User::ROLE_PASTOR,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Create Ministry Head - Makati
        User::create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Maria Santos',
            'email' => 'ministry@cmis.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'gender' => 'female',
            'birthdate' => '1990-03-10',
            'phone' => '+63 934 567 8901',
            'address' => '789 Grace Road, Barangay Poblacion, Makati, Metro Manila',
            'region_code' => '130000000',
            'province_code' => '137600000',
            'city_code' => '137600000',
            'barangay_code' => '137604020',
            'street_address' => '789 Grace Road',
            'latitude' => 14.5547,
            'longitude' => 121.0244,
            'role' => User::ROLE_MINISTRY_HEAD,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Create Small Group Leader - Pasig
        User::create([
            'uuid' => Str::uuid()->toString(),
            'name' => 'Pedro Cruz',
            'email' => 'leader@cmis.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'gender' => 'male',
            'birthdate' => '1988-09-25',
            'phone' => '+63 945 678 9012',
            'address' => '321 Hope Lane, Barangay Kapitolyo, Pasig, Metro Manila',
            'region_code' => '130000000',
            'province_code' => '137400000',
            'city_code' => '137401000',
            'barangay_code' => '137401008',
            'street_address' => '321 Hope Lane',
            'latitude' => 14.5764,
            'longitude' => 121.0851,
            'role' => User::ROLE_SMALL_GROUP_LEADER,
            'status' => User::STATUS_ACTIVE,
        ]);

        // Create 20 random members
        User::factory(20)->create();

        // Create a few inactive members
        User::factory(3)->inactive()->create();
    }
}
